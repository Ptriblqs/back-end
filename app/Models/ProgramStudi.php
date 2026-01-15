<?php

namespace App\Models;

use App\Models\Jurusan;
use App\Models\MahasiswaProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramStudi extends Model
{
    use HasFactory;

     protected $table = 'program_studis';
     protected $fillable = ['nama_prodi'];

 // Accessor: agar bisa akses dengan nama_program_studi
    public function getNamaProgramStudiAttribute()
    {
        return $this->attributes['nama_prodi'];
    }

    // Relasi ke Mahasiswa
    public function mahasiswa()
    {
        return $this->hasMany(Mahasiswa::class, 'prodi_id');
    }

    // Relasi ke Dosen
    public function dosen()
    {
        return $this->hasMany(Dosen::class, 'prodi_id');
    }
}
