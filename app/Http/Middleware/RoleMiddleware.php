<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Jika belum login
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Jika user role kosong, fallback
        if (!isset($user->role)) {
            return response()->json(['message' => 'User role undefined.'], 403);
        }

        // Cek role user
        if (!in_array($user->role, $roles)) {
            // Debug info tambahan
            return response()->json([
                'message' => 'Forbidden. Required role: ' . implode(', ', $roles) . '. Your role: ' . $user->role,
                'roles_expected' => $roles,
                'role_actual' => $user->role
            ], 403);
        }

        return $next($request);
    }
}
