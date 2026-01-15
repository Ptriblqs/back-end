<?php

namespace App\Models;

use App\Models\TugasAkhir;
use App\Models\AjuanDospem; // ✅ TAMBAHKAN INI
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dosen extends Model
{
    use HasFactory;

    protected $table = 'dosen';

    protected $fillable = [
        'user_id',
        'nik',
        'bidang_keahlian',
        'prodi_id',
        'no_telepon',
    ];

    // Enkripsi otomatis saat set NIK
    public function setNikAttribute($value)
    {
        $this->attributes['nik'] = Crypt::encryptString($value);
    }

    // Auto-decrypt saat ambil NIK
    public function getNikAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[data rusak]';
        }
    }

    // Enkripsi No Telepon
    public function setNoTeleponAttribute($value)
    {
        $this->attributes['no_telepon'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getNoTeleponAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[data rusak]';
        }
    }

    // 🔒 Fungsi hash integritas
    public function generateIntegrityHash()
    {
        $nik = $this->nik ?? '';
        $noTeleponEncrypted = $this->attributes['no_telepon'] ?? '';

        $payload = json_encode([
            'nik' => $nik,
            'prodi_id' => $this->prodi_id ?? null,
            'no_telepon' => $noTeleponEncrypted,
        ]);
        $this->attributes['integrity_hash'] = hash_hmac('sha256', $payload, config('app.key'));
    }

    // Cek Integritas
    public function isDataIntact()
    {
        $nik = $this->nik ?? '';
        $noTeleponEncrypted = $this->attributes['no_telepon'] ?? '';

        $payload = json_encode([
            'nik' => $nik,
            'prodi_id' => $this->prodi_id ?? null,
            'no_telepon' => $noTeleponEncrypted,
        ]);
        return hash_hmac('sha256', $payload, config('app.key')) === ($this->attributes['integrity_hash'] ?? null);
    }

    // ============================================
    // RELASI
    // ============================================

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Relasi ke Program Studi
    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id', 'id');
    }

    // Relasi ke Tugas Akhir
    public function tugasAkhir()
    {
        return $this->hasMany(TugasAkhir::class, 'id_dosen', 'id');
    }

    // Relasi ke mahasiswa bimbingan
    public function mahasiswaBimbingan()
    {
        return $this->hasMany(Mahasiswa::class, 'dosen_pembimbing_id');
    }

    // Relasi ke bimbingan
    public function bimbingans()
    {
        return $this->hasMany(Bimbingan::class, 'dosen_id');
    }

    // ✅ TAMBAHKAN RELASI INI UNTUK AJUAN DOSPEM
    public function ajuanDospem()
    {
        return $this->hasMany(AjuanDospem::class, 'dosen_id', 'id');
    }

    // Hook boot untuk auto-generate integritas hash
    protected static function boot()
    {
        parent::boot();

        static::created(function ($dosen) {
            $dosen->generateIntegrityHash();
            $dosen->saveQuietly();
        });

        static::updated(function ($dosen) {
            $dosen->generateIntegrityHash();
            $dosen->saveQuietly();
        });
    }

    // Accessor untuk nama lengkap
    public function getNamaLengkapAttribute()
    {
        return $this->user->nama_lengkap ?? '-';
    }
}