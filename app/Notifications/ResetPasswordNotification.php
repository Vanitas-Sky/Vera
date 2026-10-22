<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recuperación de Contraseña - Vera ERP')
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->line('Tu código / token de recuperación es:')
            ->line('**' . $this->token . '**')
            ->line('Ingresa este código en tu aplicación móvil junto con tu nueva contraseña.')
            ->line('Si no solicitaste este cambio, puedes ignorar este correo de forma segura.')
            ->salutation('Equipo de Seguridad de Vera ERP');
    }
}
