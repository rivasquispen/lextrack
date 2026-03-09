<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract, public ?User $recipient = null, public ?string $ctaUrl = null)
    {
        $this->ctaUrl = $ctaUrl ?? route('contracts.show', $this->contract);
    }

    public function build(): self
    {
        return $this->subject('Contrato aprobado en Lextrack')
            ->view('mail.contracts.contract-approved')
            ->with([
                'contract' => $this->contract,
                'recipient' => $this->recipient,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
