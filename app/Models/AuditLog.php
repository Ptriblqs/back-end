<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'role',
        'action',
        'status',
        'ip_address',
        'user_agent'
    ];
}
