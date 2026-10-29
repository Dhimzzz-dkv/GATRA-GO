<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationNotification extends Notification
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
     * Build the account activation email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.users.expire', 60);
        $path = route('account.activation.show', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);
        $activationUrl = rtrim((string) config('app.url'), '/') . $path;

        return (new MailMessage)
            ->subject('Aktivasi akun ' . config('app.name'))
            ->greeting("Halo {$notifiable->name},")
            ->line('Super Admin telah membuat akun ' . config('app.name') . ' untuk Anda.')
            ->line('Gunakan tombol berikut untuk menetapkan kata sandi dan mengaktifkan akun Anda.')
            ->action('Aktifkan Akun', $activationUrl)
            ->line("Tautan ini hanya dapat digunakan sekali dan berlaku selama {$minutes} menit.")
            ->line('Jika Anda tidak mengenali permintaan ini, abaikan email ini.');
    }
}
