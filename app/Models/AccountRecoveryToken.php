<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountRecoveryToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'is_used',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate token recovery baru
     */
    public static function createForUser(User $user): self
    {
        // Hapus token lama yang belum digunakan
        self::where('user_id', $user->id)
            ->where('is_used', false)
            ->delete();

        // Buat token baru
        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(24), // Berlaku 24 jam
        ]);
    }

    /**
     * Cek apakah token masih valid
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * Tandai token sebagai sudah digunakan
     */
    public function markAsUsed(string $ipAddress = null): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }
}