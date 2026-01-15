<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountRecoveryToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AccountRecoveryController extends Controller
{
    /**1
     * Verify token recovery
     */
    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $recoveryToken = AccountRecoveryToken::where('token', $request->token)->first();

        if (!$recoveryToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid.',
            ], 404);
        }

        if (!$recoveryToken->isValid()) {
            return response()->json([
                'success' => false,
                'message' => $recoveryToken->is_used 
                    ? 'Token sudah pernah digunakan.' 
                    : 'Token sudah kadaluarsa.',
            ], 400);
        }

        $user = $recoveryToken->user;

        return response()->json([
            'success' => true,
            'message' => 'Token valid.',
            'data' => [
                'username' => $user->username,
                'email' => $user->email,
                'blocked_at' => $user->blocked_at,
            ],
        ]);
    }

    /**
     * Reset password dan unblock account
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => [
                'sometimes',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ]);

        $recoveryToken = AccountRecoveryToken::where('token', $request->token)->first();

        if (!$recoveryToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid.',
            ], 404);
        }

        if (!$recoveryToken->isValid()) {
            return response()->json([
                'success' => false,
                'message' => $recoveryToken->is_used 
                    ? 'Token sudah pernah digunakan.' 
                    : 'Token sudah kadaluarsa.',
            ], 400);
        }

        $user = $recoveryToken->user;

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Unblock account
        $user->unblockAccount();

        // Tandai token sebagai sudah digunakan
        $recoveryToken->markAsUsed($request->ip());

        // Log aktivitas
        Log::channel('auth')->info('ACCOUNT RECOVERED', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request->ip(),
            'time' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset dan akun telah dibuka kembali. Silakan login dengan password baru Anda.',
        ]);
    }

    /**
     * Resend recovery email (jika user minta kirim ulang)
     */
    public function resendRecoveryEmail(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'email' => 'required|email',
        ]);

        $user = User::where('username', $request->username)
            ->where('is_blocked', true)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan atau tidak dalam status terblokir.',
            ], 404);
        }

        // Verifikasi email (decrypt dulu karena ter-encrypt)
        if ($user->email !== $request->email) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak sesuai dengan data akun.',
            ], 400);
        }

        // Generate token baru dan kirim email
        $recoveryToken = AccountRecoveryToken::createForUser($user);
        $user->notify(new \App\Notifications\AccountBlockedNotification($recoveryToken));

        Log::channel('auth')->info('RECOVERY EMAIL RESENT', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email recovery telah dikirim ulang. Silakan cek inbox Anda.',
        ]);
    }
}