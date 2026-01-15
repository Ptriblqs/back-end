<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi'; // nama tabel sesuai database

    protected $fillable = [
        'id_user',
        'pesan',
        'jenis',
        'is_read',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Format waktu untuk response API
    public function getFormattedTimeAttribute()
    {
        return Carbon::parse($this->created_at)
            ->locale('id')
            ->isoFormat('HH:mm – DD MMMM YYYY');
    }
}