<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContractNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Contract $contract)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->contract->loadMissing('creator', 'category', 'lawyer');

        $message = (new MailMessage())
            ->subject('Nuevo contrato registrado: '.$this->contract->codigo)
            ->view('mail.contracts.new-contract', [
                'contract' => $this->contract,
                'user' => $notifiable,
                'ctaUrl' => route('dashboard'),
            ]);

        $monitoringBcc = config('mail.monitoring_bcc');
        if (is_string($monitoringBcc) && trim($monitoringBcc) !== '') {
            $message->bcc($monitoringBcc);
        }

        return $message;
    }
}
