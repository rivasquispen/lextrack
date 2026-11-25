<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesMonitoringBcc;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApprovalUnlockedMail extends Mailable
{
    use AppliesMonitoringBcc, Queueable, SerializesModels;

    public function __construct(public Contract $contract, public User $recipient, public User $sender) {}

    public function build(): self
    {
        $this->applyMonitoringBcc();

        return $this->subject('Ya puedes revisar y aprobar el contrato '.$this->contract->codigo.' en Lextrack')
            ->view('mail.contracts.approver-assigned')->with([
                'headerTitle' => 'Ya puedes aprobar el contrato',
                'introLine' => 'Hola, '.($this->recipient->nombre ?: $this->recipient->email).'. '.($this->sender->nombre ?: $this->sender->email).' habilitó tu participación. Ya puedes revisar el documento, cargar una revisión y registrar tu aprobación.',
                'closingLine' => 'Ingresa al contrato para revisar su versión vigente y realizar la acción correspondiente.',
                'recipientRole' => 'approver', 'ctaUrl' => route('contracts.show', $this->contract),
            ]);
    }
}
