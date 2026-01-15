<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginIncident extends Model
{
    protected $fillable = [
        'ip_address',
        'username',
        'role',
        'type',
        'description'
    ];
}
