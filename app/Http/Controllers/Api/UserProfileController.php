<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserProfileController extends Controller
{
    public function showProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isDataIntact()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data integritas user rusak atau tidak valid.'
            ], 409);
        }

        $profileData = [
            "username" => $user->username,
            "nama_lengkap" => $user->nama_lengkap,
            "email" => $user->email,
            "foto_profil" => $user->foto_profil ? url('storage/' . $user->foto_profil) : null,
            "role" => $user->role
        ];

        if ($user->role === "mahasiswa") {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if ($mahasiswa && !$mahasiswa->isDataIntact()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data integritas mahasiswa rusak atau tidak valid.'
                ], 409);
            }

            if ($mahasiswa) {
                $profileData["nim"] = $mahasiswa->nim;
                $profileData["prodi_id"] = $mahasiswa->prodi_id;
                $profileData["program_studi"] = $mahasiswa->programStudi ? $mahasiswa->programStudi->nama_program_studi : null;
                $profileData["portofolio"] = $mahasiswa->portofolio;
            }
        } elseif ($user->role === 'dosen') {
            $dosen = Dosen::where('user_id', $user->id)->first();

            if ($dosen && !$dosen->isDataIntact()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data integritas dosen rusak atau tidak valid.'
                ], 409);
            }

            if ($dosen) {
                $profileData["nik"] = $dosen->nik;
                $profileData["prodi_id"] = $dosen->prodi_id;
                $profileData["program_studi"] = $dosen->programStudi ? $dosen->programStudi->nama_program_studi : null;
                $profileData["no_telepon"] = $dosen->no_telepon;
                $profileData["bidang_keahlian"] = $dosen->bidang_keahlian;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $profileData
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Validasi dasar
        $rules = [
            'nama_lengkap' => "sometimes|string|max:255",
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => [
                'sometimes',
                'nullable',
                'confirmed',
                PasswordRule::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'password_confirmation' => ['sometimes', 'nullable', 'required_with:password'],
            'foto_profil' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:2048',
            'program_studi' => 'sometimes|nullable|integer|exists:program_studis,id',
        ];

        // Validasi khusus per role
        if ($user->role === 'mahasiswa') {
            $rules['nim'] = ['sometimes', 'string', Rule::unique('mahasiswa', 'nim')->ignore($user->mahasiswa->id ?? null)];
    
        } elseif ($user->role === 'dosen') {
            $rules['nik'] = ['sometimes', 'string', Rule::unique('dosen', 'nik')->ignore($user->dosen->id ?? null)];
            $rules['no_telepon'] = 'sometimes|string|nullable';
            $rules['bidang_keahlian'] = 'sometimes|string|nullable';
        }

        $validated = $request->validate($rules);

        // Update password jika ada
        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Handle upload foto profil
        if ($request->hasFile('foto_profil')) {
            // Hapus foto lama jika ada
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }

            // Upload foto baru
            $path = $request->file('foto_profil')->store('profile_photos', 'public');
            $validated['foto_profil'] = $path;
        }

        // Update data user
        $user->fill(array_intersect_key($validated, array_flip(['nama_lengkap', 'email', 'password', 'foto_profil'])));
        $user->save();

        // Update data role-specific
        if ($user->role === 'mahasiswa') {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            if ($mahasiswa) {
                $mahasiswaData = [];
                if (isset($validated['nim'])) $mahasiswaData['nim'] = $validated['nim'];
                if (isset($validated['portofolio'])) $mahasiswaData['portofolio'] = $validated['portofolio'];
                if (isset($validated['program_studi'])) $mahasiswaData['prodi_id'] = $validated['program_studi'];

                if (!empty($mahasiswaData)) {
                    $mahasiswa->update($mahasiswaData);
                }
            }
        } elseif ($user->role === 'dosen') {
            $dosen = Dosen::where('user_id', $user->id)->first();
            if ($dosen) {
                $dosenData = [];
                if (isset($validated['nik'])) $dosenData['nik'] = $validated['nik'];
                if (isset($validated['no_telepon'])) $dosenData['no_telepon'] = $validated['no_telepon'];
                if (isset($validated['bidang_keahlian'])) $dosenData['bidang_keahlian'] = $validated['bidang_keahlian'];
                if (isset($validated['program_studi'])) $dosenData['prodi_id'] = $validated['program_studi'];

                if (!empty($dosenData)) {
                    $dosen->update($dosenData);
                }
            }
        }

        // Refresh data
        $user->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'foto_profil' => $user->foto_profil ? url('storage/' . $user->foto_profil) : null,
            ]
        ]);
    }
}