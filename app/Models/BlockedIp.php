<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'failed_attempts',
        'blocked_until',
        'reason'
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
    ];
}
