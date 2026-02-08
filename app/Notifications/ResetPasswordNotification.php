<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Restablecer Contraseña - Workout App')
            ->greeting('¡Hola '.$notifiable->name.'!')
            ->line('Recibiste este email porque solicitaste restablecer tu contraseña.')
            ->action('Restablecer Contraseña', $resetUrl)
            ->line('Este enlace expirará en '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutos.')
            ->line('Si no solicitaste restablecer tu contraseña, puedes ignorar este email.');
    }

    /**
     * Genera la URL para restablecer la contraseña
     * Usa una landing page web que intenta abrir la app con deep link
     */
    protected function resetUrl(object $notifiable): string
    {
        // URL web que detecta y redirige a la app
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], false));
    }
}
