<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LogAuthentication
{
    public function handle(Login|Failed|Logout $event): void
    {
        $ip = request()->ip();
        $time = Carbon::now()->toDateTimeString();
        $userAgent = request()->userAgent();

        if ($event instanceof Login) {
            Log::channel('auth')->info('LOGIN SUCCESS', [
                'username' => $event->user->username,
                'email' => $event->user->email ?? 'N/A',
                'role' => $event->user->role,
                'user_id' => $event->user->id,
                'time' => $time,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'guard' => $event->guard,
            ]);
        } elseif ($event instanceof Failed) {
            // PERBAIKAN: Handle case dimana user bisa null
            $logData = [
                'username' => $event->credentials['username'] ?? 'unknown',
                'role' => $event->credentials['role'] ?? 'unknown',
                'time' => $time,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'guard' => $event->guard ?? 'api',
            ];

            // Tambahkan info user jika ada (password salah tapi user ketemu)
            if ($event->user) {
                $logData['user_id'] = $event->user->id;
                $logData['reason'] = 'Wrong password';
            } else {
                $logData['reason'] = 'User not found';
            }

            Log::channel('auth')->warning('LOGIN FAILED', $logData);
            
        } elseif ($event instanceof Logout) {
            Log::channel('auth')->info('LOGOUT', [
                'username' => $event->user->username,
                'email' => $event->user->email ?? 'N/A',
                'role' => $event->user->role,
                'user_id' => $event->user->id,
                'time' => $time,
                'ip' => $ip,
                'guard' => $event->guard,
            ]);
        }
    }
}