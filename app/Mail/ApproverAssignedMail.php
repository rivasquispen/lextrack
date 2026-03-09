<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApproverAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract, public string $recipientRole, public ?string $ctaUrl = null)
    {
        $this->ctaUrl = $ctaUrl ?? route('contracts.show', $this->contract);
    }

    public function build(): self
    {
        $subject = $this->recipientRole === 'approver'
            ? 'Se te asignó como aprobador en Lextrack'
            : 'Tu contrato ingresó a revisión';

        $introLine = $this->recipientRole === 'approver'
            ? 'Has sido designado como aprobador para este contrato. Revisa el documento y registra tu decisión.'
            : 'Los aprobadores ya fueron asignados y el contrato pasó a estado de revisión.';

        $closingLine = $this->recipientRole === 'approver'
            ? 'Gracias por mantener al día el flujo de aprobaciones.'
            : 'Puedes ingresar al contrato para seguir el avance del flujo.';

        $recipientName = $this->recipientRole === 'approver'
            ? ($this->contract->approvals->firstWhere('user_id', auth()->id())?->user->nombre ?? '')
            : ($this->contract->creator->nombre ?? '');

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
