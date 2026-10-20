<?php

namespace App\Notifications;

use App\Models\ApprovalReopening;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalReopened extends Notification
{
    public function __construct(public ApprovalReopening $reopening) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aprobación reabierta en Lextrack')
            ->greeting('Hola '.($notifiable->nombre ?? '').',')
            ->line('Un administrador reabrió una aprobación individual, previa coordinación con el abogado registrado.')
            ->line('Contrato: '.$this->reopening->snapshot['contract_code'].' — '.$this->reopening->snapshot['contract_title'])
            ->line('Versión: '.$this->reopening->version_number)
            ->line('Aprobador: '.$this->reopening->snapshot['approver'])
            ->line('Motivo: '.$this->reopening->reason)
            ->line('El documento y la versión se mantienen. La aprobación individual vuelve a estar pendiente.')
            ->action('Revisar contrato', route('contracts.show', $this->reopening->contract_id));
    }
}
