<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\AjuanDospem;
use App\Models\ProgramStudi;
use App\Models\BlockedIp;
use App\Services\SecurityService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Models\PasswordOtp;
use App\Mail\ResetPasswordOtpMail;
class AuthController extends Controller
{
    /**
     * ========================================
     * REGISTER
     * ========================================
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:users,username',
            'nama_lengkap' => 'required',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:mahasiswa,dosen,admin',
            'program_studis' => 'nullable|integer|exists:program_studis,id',
            'portofolio' => 'nullable|string',
            'bidang_keahlian' => 'nullable|string',
            'foto_profil' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create([
            'username' => $validated['username'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
            'role' => $validated['role'],
            'foto_profil' => $validated['foto_profil'] ?? null,
        ]);

        $profile = null;

        // =========================
        // MAHASISWA
        // =========================
        if ($validated['role'] === 'mahasiswa') {

            $request->validate([
                'program_studis' => 'required|integer|exists:program_studis,id'
            ]);

            if (Mahasiswa::where('nim', $validated['username'])->exists()) {
                $user->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'NIM sudah digunakan'
                ], 422);
            }

            $profile = Mahasiswa::create([
                'user_id'    => $user->id,
                'nim'        => $validated['username'],
                'prodi_id'   => $validated['program_studis'],
                'portofolio' => $validated['portofolio'] ?? '',
            ]);
        }

        // =========================
        // DOSEN
        // =========================
        elseif ($validated['role'] === 'dosen') {

            $request->validate([
                'program_studis' => 'required|integer|exists:program_studis,id',
            ]);

            if (Dosen::where('nik', $validated['username'])->exists()) {
                $user->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'NIK sudah digunakan'
                ], 422);
            }

            $profile = Dosen::create([
                'user_id'         => $user->id,
                'nik'             => $validated['username'],
                'prodi_id'        => $validated['program_studis'],
                'bidang_keahlian' => $validated['bidang_keahlian'] ?? '',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Register berhasil',
            'user' => [
                'id'           => $user->id,
                'username'     => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'email'        => $user->email,
                'role'         => $user->role,
                'prodi_id'     => $profile->prodi_id ?? null,
                'profile_id'   => $profile->id ?? null,
                'mahasiswa_id' => $validated['role'] === 'mahasiswa' ? $profile->id : null,
                'dosen_id'     => $validated['role'] === 'dosen' ? $profile->id : null,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * ========================================
     * LOGIN
     * ========================================
     */
    public function login(Request $request)
    {
        $request->validate([
            'role' => 'required|in:mahasiswa,dosen,admin',
            'username' => 'required',
            'password' => 'required',
        ]);

        $ip = $request->ip();

        // =========================
        // CEK IP DIBLOKIR
        // =========================
        $blocked = BlockedIp::where('ip_address', $ip)
            ->where('blocked_until', '>', now())
            ->first();

        if ($blocked) {
            SecurityService::audit([
                'username' => $request->username,
                'role' => $request->role,
                'action' => 'login_blocked',
                'status' => 'blocked',
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'IP Anda diblokir sementara. Coba lagi nanti.'
            ], 403);
        }

        $user = User::where('username', $request->username)
            ->where('role', $request->role)
            ->first();

        // =========================
        // LOGIN GAGAL
        // =========================
        if (!$user || !Hash::check($request->password, $user->password)) {

            SecurityService::failedLogin(
                $ip,
                $request->username,
                $request->role
            );

            SecurityService::audit([
                'username' => $request->username,
                'role' => $request->role,
                'action' => 'login_failed',
                'status' => 'failed',
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
            ]);

            Log::warning('Login gagal', [
                'username' => $request->username,
                'role' => $request->role,
                'ip' => $ip,
                'user_agent' => $request->userAgent(),
                'time' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'NIK / NIM atau password salah.',
            ], 401);
        }

        event(new Login('api', $user, false));

        // =========================
        // RESET BLOK IP
        // =========================
        SecurityService::resetIp($ip);

        SecurityService::audit([
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'action' => 'login_success',
            'status' => 'success',
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
        ]);

        // =========================
        // DEFAULT NILAI
        // =========================
        $prodiId = null;
        $profileId = null;
        $prodiData = null;
        $statusPembimbing = 'belum_ajukan';
        $mahasiswaId = null;
        $dosenId = null;

        // =========================
        // MAHASISWA LOGIN
        // =========================
        if ($user->role === 'mahasiswa') {

            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if ($mahasiswa) {
                $profileId = $mahasiswa->id;
                $mahasiswaId = $mahasiswa->id;
                $prodiId = $mahasiswa->prodi_id;

                $prodiData = ProgramStudi::select('id', 'nama_prodi')
                    ->where('id', $prodiId)
                    ->first();

                Log::info('Login Mahasiswa:', [
                    'user_id' => $user->id,
                    'mahasiswa_id' => $mahasiswaId,
                    'profile_id' => $profileId,
                    'prodi_id' => $prodiId,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data mahasiswa tidak ditemukan. Silakan hubungi administrator.',
                ], 400);
            }

            $ajuan = AjuanDospem::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ajuan) {
                $statusPembimbing = $ajuan->status ?? 'belum_ajukan';
            }
        }

        // =========================
        // DOSEN LOGIN
        // =========================
        elseif ($user->role === 'dosen') {

            $dosen = Dosen::where('user_id', $user->id)->first();

            if ($dosen) {
                $profileId = $dosen->id;
                $dosenId = $dosen->id;
                $prodiId = $dosen->prodi_id;

                $prodiData = ProgramStudi::select('id', 'nama_prodi')
                    ->where('id', $prodiId)
                    ->first();

                Log::info('Login Dosen:', [
                    'user_id' => $user->id,
                    'dosen_id' => $dosenId,
                    'profile_id' => $profileId,
                    'prodi_id' => $prodiId,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dosen tidak ditemukan. Silakan hubungi administrator.',
                ], 400);
            }
        }

        // =========================
        // ADMIN LOGIN
        // =========================
        elseif ($user->role === 'admin') {
            Log::info('Login Admin:', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role' => $user->role,
                'user_id' => $user->id,
                'profile_id' => $profileId,
                'mahasiswa_id' => $mahasiswaId,
                'dosen_id' => $dosenId,
                'prodi_id' => $prodiId,
                'program_studi' => $prodiData,
                'status_pembimbing' => $statusPembimbing,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * ========================================
     * LOGOUT
     * ========================================
     */
    public function logout(Request $request)
    {
        SecurityService::audit([
            'user_id' => $request->user()->id,
            'username' => $request->user()->username,
            'role' => $request->user()->role,
            'action' => 'logout',
            'status' => 'success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        event(new Logout('api', $request->user()));
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ], 200);
    }

    /**
     * ========================================
     * GET CURRENT USER (ME)
     * ========================================
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $profile = null;
        $mahasiswaId = null;
        $dosenId = null;
        $prodiId = null;

        if ($user->role === 'mahasiswa') {
            $profile = Mahasiswa::where('user_id', $user->id)->first();
            if ($profile) {
                $mahasiswaId = $profile->id;
                $prodiId = $profile->prodi_id;
            }
        } elseif ($user->role === 'dosen') {
            $profile = Dosen::where('user_id', $user->id)->first();
            if ($profile) {
                $dosenId = $profile->id;
                $prodiId = $profile->prodi_id;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role' => $user->role,
                'foto_profil' => $user->foto_profil,
                'mahasiswa_id' => $mahasiswaId,
                'dosen_id' => $dosenId,
                'prodi_id' => $prodiId,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ], 200);
    }

    
    /* =====================================================
     | FORGOT PASSWORD (EMAIL ENCRYPTED)
     ===================================================== */
   public function forgotPassword(Request $request)
{
    $request->validate(['email' => 'required|email']);
    $inputEmail = $request->email;

    $user = User::whereNotNull('email')->get()
        ->first(function ($u) use ($inputEmail) {
            $needle = trim(strtolower($inputEmail));

            $accessorEmail = $u->email ?? null;
            if ($accessorEmail && trim(strtolower($accessorEmail)) === $needle) {
                return true;
            }

            $raw = $u->getAttributes()['email'] ?? null;
            if (! $raw) {
                return false;
            }

            if (trim(strtolower($raw)) === $needle) {
                return true;
            }

            try {
                $dec = Crypt::decryptString($raw);
                return trim(strtolower($dec)) === $needle;
            } catch (\Exception $e) {
                return false;
            }
        });

    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'Email tidak ditemukan'
        ], 404);
    }

    PasswordOtp::where('email', $inputEmail)->delete();

    $otp = rand(100000, 999999);

    PasswordOtp::create([
        'email' => $inputEmail,
        'otp' => $otp,
        'expired_at' => now()->addMinutes(5),
    ]);

    Mail::to($inputEmail)->send(new ResetPasswordOtpMail($otp));

    return response()->json([
        'success' => true,
        'message' => 'OTP dikirim ke email'
    ]);
}

    /* =====================================================
     | VERIFY OTP
     ===================================================== */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
        ]);

        $valid = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expired_at', '>', now())
            ->exists();

        return response()->json([
            'success' => $valid,
            'message' => $valid ? 'OTP valid' : 'OTP salah / kadaluarsa'
        ], $valid ? 200 : 400);
    }

    /* =====================================================
     | RESET PASSWORD
     ===================================================== */
   public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required',
        'password' => 'required|min:6|confirmed',
    ]);

    $otp = PasswordOtp::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('expired_at', '>', now())
        ->first();

    if (! $otp) {
        return response()->json([
            'success' => false,
            'message' => 'OTP tidak valid'
        ], 400);
    }

    $user = User::whereNotNull('email')->get()
        ->first(function ($u) use ($request) {
            $needle = trim(strtolower($request->email));

            $accessorEmail = $u->email ?? null;
            if ($accessorEmail && trim(strtolower($accessorEmail)) === $needle) {
                return true;
            }

            $raw = $u->getAttributes()['email'] ?? null;
            if (! $raw) {
                return false;
            }

            if (trim(strtolower($raw)) === $needle) {
                return true;
            }

            try {
                $dec = Crypt::decryptString($raw);
                return trim(strtolower($dec)) === $needle;
            } catch (\Exception $e) {
                return false;
            }
        });

    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ], 404);
    }

    $user->password = Hash::make($request->password);
    $user->save();

    $otp->delete();

    return response()->json([
        'success' => true,
        'message' => 'Password berhasil diubah'
    ]);
}
}
