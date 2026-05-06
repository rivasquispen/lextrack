<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesMonitoringBcc;
use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApproverAssignedMail extends Mailable
{
    use AppliesMonitoringBcc, Queueable, SerializesModels;

    public function __construct(public Contract $contract, public string $recipientRole, public ?string $ctaUrl = null)
    {
        $this->ctaUrl = $ctaUrl ?? route('contracts.show', $this->contract);
    }

    public function build(): self
    {
        $this->applyMonitoringBcc();

        $subject = match ($this->recipientRole) {
            'approver' => 'Se te asignó como aprobador en Lextrack',
            'creator' => 'Tu contrato ingresó a revisión',
            'lawyer' => 'Se asignaron aprobadores a un contrato en Lextrack',
            default => 'Actualización de contrato',
        };

        $introLine = match ($this->recipientRole) {
            'approver' => 'Has sido designado como aprobador para este contrato. Revisa el documento y registra tu decisión.',
            'creator' => 'Los aprobadores ya fueron asignados y el contrato pasó a estado de revisión.',
            'lawyer' => 'Se asignaron aprobadores a este contrato. Puedes ingresar para seguir el flujo de revisión.',
            default => 'Hay una actualización relacionada a este contrato.',
        };

        $closingLine = match ($this->recipientRole) {
            'approver' => 'Gracias por mantener al día el flujo de aprobaciones.',
            'creator' => 'Puedes ingresar al contrato para seguir el avance del flujo.',
            'lawyer' => 'Puedes ingresar al contrato para revisar el avance y las siguientes acciones.',
            default => 'Gracias por usar Lextrack.',
        };

        return $this->subject($subject)
            ->view('mail.contracts.approver-assigned')
            ->with([
                'contract' => $this->contract,
                'introLine' => $introLine,
                'closingLine' => $closingLine,
                'recipientRole' => $this->recipientRole,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
