<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthLog extends Model
{
    protected $fillable = [
        'user_id','event','ip','user_agent','session_id',
        'location','reason','suspicious','meta'
    ];
    protected $casts = ['meta'=>'array','suspicious'=>'boolean'];
}

