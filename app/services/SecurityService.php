<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\LoginIncident;
use Carbon\Carbon;

class SecurityService
{
    public static function audit(array $data): void
    {
        AuditLog::create($data);
    }

    public static function failedLogin(string $ip, ?string $username, ?string $role): void
    {
        $blocked = BlockedIp::firstOrCreate(
            ['ip_address' => $ip],
            ['failed_attempts' => 0]
        );

        $blocked->increment('failed_attempts');

        if ($blocked->failed_attempts >= 5) {
            $blocked->update([
                'blocked_until' => Carbon::now()->addMinutes(30),
                'reason' => 'Brute force login'
            ]);

            LoginIncident::create([
                'ip_address' => $ip,
                'username' => $username,
                'role' => $role,
                'type' => 'brute_force',
                'description' => 'IP diblokir karena 5x login gagal'
            ]);
        }
    }

    public static function resetIp(string $ip): void
    {
        BlockedIp::where('ip_address', $ip)->delete();
    }
}
