<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekDospemMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Pastikan user login
        if (!$user) {
            return response()->json([
                'message' => 'Anda harus login terlebih dahulu.'
            ], 401);
        }

        // 2. Pastikan user adalah mahasiswa
        if ($user->role !== 'mahasiswa') {
            return response()->json([
                'message' => 'Akses ini hanya untuk mahasiswa.'
            ], 403);
        }

        // 3. Cek apakah mahasiswa sudah punya dosen pembimbing
        if (!$user->dospem_id) {
            return response()->json([
                'message' => 'Anda belum memiliki dosen pembimbing.'
            ], 403);
        }

        // Lolos, lanjutkan request
        return $next($request);
    }
}
