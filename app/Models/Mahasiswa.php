<?php

namespace App\Models;

use App\Models\Dokumen;
use App\Models\TugasAkhir;
use App\Models\ProgramStudi;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mahasiswa extends Model
{
    use HasFactory;

    protected $table = 'mahasiswa';

    protected $fillable = ['user_id', 'nim', 'prodi_id', 'portofolio'];

    public function setNimAttribute($value)
    {
        $this->attributes['nim'] = Crypt::encryptString($value);
    }

    public function setPortofolioAttribute($value)
    {
        $this->attributes['portofolio'] = Crypt::encryptString($value);
    }

    public function getNimAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function getPortofolioAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[data rusak]';
        }
    }

    public function generateIntegrityHash()
    {
        $nimEncrypted = $this->attributes['nim'] ?? '';
        $portofolioEncrypted = $this->attributes['portofolio'] ?? '';

        $payload = json_encode([
            'nim' => $nimEncrypted,
            'portofolio' => $portofolioEncrypted
        ]);

        $this->attributes['integrity_hash'] = hash_hmac('sha256', $payload, config('app.key'));
    }

    public function isDataIntact()
    {
        $nimEncrypted = $this->attributes['nim'] ?? '';
        $portofolioEncrypted = $this->attributes['portofolio'] ?? '';

        $payload = json_encode([
            'nim' => $nimEncrypted,
            'portofolio' => $portofolioEncrypted
        ]);

        $expectedHash = hash_hmac('sha256', $payload, config('app.key'));
        $currentHash = $this->attributes['integrity_hash'] ?? null;

        return $expectedHash === $currentHash;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id', 'id');
    }

    public function dokumen()
{
    return $this->hasMany(
        Dokumen::class,
        'mahasiswa_id', // FK di tabel dokumen
        'id'            // PK di tabel mahasiswa
    );
}

    public function tugasAkhir()
    {
        return $this->hasMany(TugasAkhir::class, 'id_mahasiswa');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            $user->generateIntegrityHash();
        });

        static::updating(function ($user) {
            $user->generateIntegrityHash();
        });
    }

    // Relasi ke dosen pembimbing
   public function dosenPembimbing()
{
    return $this->hasOne(AjuanDospem::class)
                ->where('status', 'diterima');
}

    // Relasi ke bimbingan
    public function bimbingans()
    {
        return $this->hasMany(Bimbingan::class, 'mahasiswa_id');
    }
}
