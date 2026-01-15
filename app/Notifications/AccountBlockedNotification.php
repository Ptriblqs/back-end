<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\AccountRecoveryToken;

class AccountBlockedNotification extends Notification
{
    use Queueable;

    protected AccountRecoveryToken $recoveryToken;

    public function __construct(AccountRecoveryToken $recoveryToken)
    {
        $this->recoveryToken = $recoveryToken;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/api/account-recovery/verify?token=' . $this->recoveryToken->token);
        
        return (new MailMessage)
            ->subject('Akun Anda Telah Diblokir')
            ->greeting('Halo, ' . $notifiable->nama_lengkap)
            ->line('Akun Anda telah diblokir karena terlalu banyak percobaan login yang gagal.')
            ->line('Jika ini adalah Anda, silakan klik tombol di bawah untuk mereset password dan membuka kembali akun Anda.')
            ->line('Jika ini bukan Anda, segera hubungi administrator karena kemungkinan ada yang mencoba mengakses akun Anda.')
            ->action('Reset Password & Buka Akun', $url)
            ->line('Link ini akan kadaluarsa dalam 24 jam.')
            ->line('**Username:** ' . $notifiable->username)
            ->line('**Waktu Diblokir:** ' . $notifiable->blocked_at->format('d/m/Y H:i:s'))
            ->salutation('Terima kasih, Tim Keamanan ' . config('app.name'));
    }
}