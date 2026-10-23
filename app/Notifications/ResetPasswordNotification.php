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
            ->subject('Solicitud de actualización de credenciales - Vera ERP')
            ->greeting('Estimado/a ' . ($notifiable->name ?? 'Usuario') . ',')
            ->line('Hemos recibido una solicitud para actualizar la clave de acceso asociada a tu cuenta institucional en Vera ERP.')
            ->line('Tu código de autorización para el sistema web o la aplicación móvil es:')
            ->line('**' . $this->token . '**')
            ->line('Este código es de un solo uso y expirará automáticamente transcurridos 60 minutos.')
            ->line('Si tú no solicitaste este cambio, no es necesario realizar ninguna acción; tus credenciales continuarán protegidas.')
            ->salutation('Atentamente, Departamento de Seguridad Operativa - Vera ERP');
    }
}
