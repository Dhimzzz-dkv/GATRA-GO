<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorOtpNotification extends Notification
{
    public function __construct(
        public string $otp,
        public int $expiresInMinutes = 10
    ) {
    }

    /**
     * Get the delivery channels for the notification.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the 2FA OTP verification mail.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $recipientName = $notifiable->official_name ?? $notifiable->name ?? 'Pengguna';

        return (new MailMessage)
            ->subject("Kode Verifikasi 2FA (OTP) — {$appName}")
            ->greeting("Halo {$recipientName},")
            ->line("Anda menerima email ini karena ada upaya masuk ke akun dengan hak akses Super Admin pada sistem {$appName}.")
            ->line('Gunakan kode One-Time Password (OTP) berikut untuk menyelesaikan proses masuk:')
            ->line("**{$this->otp}**")
            ->line("Kode verifikasi ini berlaku selama {$this->expiresInMinutes} menit dan hanya dapat digunakan satu kali.")
            ->line('PENTING: Jangan berikan kode ini kepada siapa pun. Jika Anda tidak merasa mencoba masuk, segera amankan akun Anda.');
    }
}
