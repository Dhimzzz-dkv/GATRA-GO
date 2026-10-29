<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountPasswordResetNotification extends Notification
{
    public function __construct(public string $token)
    {
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
     * Build the password reset email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.users.expire', 60);
        $path = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);
        $resetUrl = rtrim((string) config('app.url'), '/') . $path;

        return (new MailMessage)
            ->subject('Atur ulang kata sandi ' . config('app.name'))
            ->greeting("Halo {$notifiable->name},")
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun ' . config('app.name') . ' Anda.')
            ->action('Atur Ulang Kata Sandi', $resetUrl)
            ->line("Tautan ini hanya dapat digunakan sekali dan berlaku selama {$minutes} menit.")
            ->line('Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini.');
    }
}
