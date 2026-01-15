<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $fillable = ['user_id', 'judul', 'isi', 'attachment', 'tgl_mulai', 'tgl_selesai']; // ✅ Perbaiki syntax
    protected $table = 'pengumuman';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}