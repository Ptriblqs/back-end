<?php

namespace App\Models;

use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TugasAkhir extends Model
{
    use HasFactory;

    protected $table = 'tugas_akhir';
    
    protected $fillable = [
        'id_mahasiswa',
        'id_dosen', 
        'judul_tugas',
        'tenggat_waktu',
        'deskripsi'
    ];

    public function mahasiswa(){
        return $this->belongsTo(Mahasiswa::class, 'id_mahasiswa', 'id');

    }
    public function dosen(){
        return $this->belongsTo(Dosen::class, 'id_dosen', 'id');
    }
}
