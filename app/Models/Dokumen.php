<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    use HasFactory;

    protected $table = 'dokumen';

    protected $fillable = [
        'mahasiswa_id',
        'dosen_id',
        'judul',
        'bab',
        'deskripsi',
        'file_path',
        'status', // Menunggu, Revisi, Disetujui
        'catatan_revisi',
        'file_revisi_path',
        'tanggal_upload',
        'tanggal_revisi',
        'revisi',
    ];

    protected $casts = [
        'tanggal_upload' => 'datetime',
        'tanggal_revisi' => 'datetime',
        'revisi' => 'integer',
    ];

   public function mahasiswa()
{
    return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
}

    public function dosen()
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }
}