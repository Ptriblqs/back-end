<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AjuanDospem extends Model
{
    use HasFactory;

    protected $table = 'ajuan_dospem';

    protected $fillable = [
        'user_id',
        'nim',
        'program_studis_id',
        'dosen_id',
        'dosen_nik',
        'dosen_nama',
        'alasan',
        'judul_ta',
        'deskripsi_ta',
        'portofolio',
        'status',
        'catatan_dosen',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Enkripsi NIK Dosen
    public function setDosenNikAttribute($value)
    {
        $this->attributes['dosen_nik'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDosenNikAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[data rusak]';
        }
    }

    // Enkripsi NIM
    public function setNimAttribute($value)
    {
        $this->attributes['nim'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getNimAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[data rusak]';
        }
    }

    // ============================================
    // RELASI - FIXED
    // ============================================

    /**
     * Relasi ke User (mahasiswa) - INI YANG BENAR
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke Mahasiswa (untuk ambil NIM)
     * HANYA SELECT nim saja karena data lain ada di users
     */
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'user_id', 'user_id')
            ->select(['id', 'user_id', 'nim', 'prodi_id']);
    }

    /**
     * Relasi ke Dosen
     */
    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    /**
     * Relasi ke Program Studi
     */
    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studis_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeDiterima($query)
    {
        return $query->where('status', 'diterima');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', 'ditolak');
    }

    public function scopeByMahasiswa($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDosen($query, $dosenId)
    {
        return $query->where('dosen_id', $dosenId);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'menunggu' => 'Menunggu',
            'diterima' => 'Diterima',
            'ditolak' => 'Ditolak',
            default => 'Tidak Diketahui'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'menunggu' => 'warning',
            'diterima' => 'success',
            'ditolak' => 'danger',
            default => 'secondary'
        };
    }

    // ============================================
    // METHODS
    // ============================================

    public function approve($catatanDosen = null)
    {
        $this->update([
            'status' => 'diterima',
            'catatan_dosen' => $catatanDosen,
            'reviewed_at' => now(),
        ]);
    }

    public function reject($catatanDosen = null)
    {
        $this->update([
            'status' => 'ditolak',
            'catatan_dosen' => $catatanDosen,
            'reviewed_at' => now(),
        ]);
    }

    public function isMenunggu()
    {
        return $this->status === 'menunggu';
    }

    public function isDiterima()
    {
        return $this->status === 'diterima';
    }

    public function isDitolak()
    {
        return $this->status === 'ditolak';
    }
}