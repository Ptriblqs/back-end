<?php

namespace App\Models;

use App\Models\Dosen;
use App\Models\Bimbingan;
use App\Models\Mahasiswa;
use Laravel\Sanctum\HasApiTokens;
use App\Models\AccountRecoveryToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\AccountBlockedNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'nama_lengkap',
        'foto_profil',
        'email',
        'password',
        'role',
        'integrity_hash',
        'login_attempts',
        'last_failed_login',
        'is_blocked',
        'blocked_at',
        'blocked_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Tambahkan casts untuk kolom baru
    protected $casts = [
        'last_failed_login' => 'datetime',
        'blocked_at' => 'datetime',
        'is_blocked' => 'boolean',
    ];

    // Enkripsi
    public function setNamaLengkapAttribute($value)
    {
        if ($value) {
            $this->attributes['nama_lengkap'] = Crypt::encryptString($value);
        }
    }

    public function setEmailAttribute($value)
    {
        if ($value) {
            $this->attributes['email'] = Crypt::encryptString($value);
        }
    }

    public function getNamaLengkapAttribute($value)
    {
        if (! $value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[data rusak]';
        }
    }

    public function getEmailAttribute($value)
    {
        if (! $value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[data rusak]';
        }
    }

    public function setPasswordAttribute($value)
    {
        if (! empty($value) && ! Hash::info($value)['algo']) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    // Hash Integritas
    public function generateIntegrityHash()
    {
        $nama = $this->nama_lengkap ?? '';
        $email = $this->email ?? '';
        $payload = json_encode([
            'username' => $this->username,
            'nama_lengkap' => $nama,
            'email' => $email,
            'role' => $this->role,
        ]);
        $this->attributes['integrity_hash'] = hash_hmac('sha256', $payload, config('app.key'));
    }

    // Cek Integritas
    public function isDataIntact()
    {
        $nama = $this->nama_lengkap ?? '';
        $email = $this->email ?? '';
        $payload = json_encode([
            'username' => $this->username,
            'nama_lengkap' => $nama,
            'email' => $email,
            'role' => $this->role,
        ]);

        return hash_hmac('sha256', $payload, config('app.key')) === ($this->attributes['integrity_hash'] ?? null);
    }
    /**
     * Increment login attempts
     */
    public function incrementLoginAttempts(): void
    {

        $this->increment('login_attempts');
        $this->update(['last_failed_login' => now()]);

        // Blokir jika sudah 5 kali percobaan
        if ($this->login_attempts >= 5) {
            $this->blockAccount('Too many failed login attempts');
        }
    }

    /**
     * Reset login attempts setelah login berhasil
     */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'last_failed_login' => null,
        ]);
    }

    /**
     * Blokir akun
     */
    public function blockAccount(string $reason = 'Security reason'): void
    {
        $this->update([
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $reason,
        ]);

        // Revoke semua token yang ada
        $this->tokens()->delete();

        // Generate recovery token
        $recoveryToken = AccountRecoveryToken::createForUser($this);

        // Kirim email notifikasi
        try {
            $this->notify(new AccountBlockedNotification($recoveryToken));

            \Log::info('Account blocked email sent', [
                'user_id' => $this->id,
                'username' => $this->username,
                'email' => $this->email,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send account blocked email', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unblock akun (untuk admin)
     */
    public function unblockAccount(): void
    {
        $this->update([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
            'login_attempts' => 0,
            'last_failed_login' => null,
        ]);
    }

    public function isBlocked(): bool
    {
        if ($this->is_blocked && $this->blocked_at) {
            // Auto unblock setelah 24 jam (opsional, bisa dihapus jika tidak diinginkan)
            if ($this->blocked_at->addHours(24)->isPast()) {
                $this->unblockAccount();

                return false;
            }
        }

        return $this->is_blocked;
    }

    /**
     * Get remaining login attempts
     */
    public function getRemainingAttempts(): int
    {
        return max(0, 5 - $this->login_attempts);
    }

    // ============================================
    // END: Login Attempt Tracking Methods
    // ============================================

    // Relasi
    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class);
    }

    public function dosen()
    {
        return $this->hasOne(Dosen::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function tugasAkhirMahasiswa()
    {
        return $this->hasManyThrough(
            TugasAkhir::class, // model tujuan
            Mahasiswa::class,  // model perantara
            'user_id',         // FK tabel mahasiswa
            'id_mahasiswa',    // FK tabel tugas_akhir
            'id',              // PK tabel users
            'id'               // PK tabel mahasiswa
        );
    }
    public function tugasAkhirDosen()
    {
        return $this->hasManyThrough(
            TugasAkhir::class,
            Dosen::class,
            'user_id',
            'id_dosen',
            'id',
            'id'
        );
    }

    public function recoveryTokens()
    {
        return this->hasMany(AccountRecoveryToken::class);
    }
    
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->generateIntegrityHash();
            $user->saveQuietly(); // simpan tanpa trigger event berulang
        });

        static::updated(function ($user) {
            // Jangan regenerate hash jika hanya update login_attempts atau is_blocked
            $excludedFields = ['login_attempts', 'last_failed_login', 'is_blocked', 'blocked_at', 'blocked_reason', 'updated_at'];

            if (! $user->isDirty($excludedFields) || $user->isDirty(array_diff(array_keys($user->getDirty()), $excludedFields))) {
                $user->generateIntegrityHash();
                $user->saveQuietly();
            }
        });
    }
}
