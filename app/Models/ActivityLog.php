<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $fillable = [
        'id_user',
        'activity',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
