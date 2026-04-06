<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesMonitoringBcc;
use App\Models\Contract;
use App\Models\ContractComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractCommentCreatedMail extends Mailable
{
    use AppliesMonitoringBcc, Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ContractComment $comment,
        public ?User $recipient = null,
        public ?string $ctaUrl = null
    ) {
        $this->ctaUrl = $ctaUrl ?? route('contracts.show', $this->contract);
    }

    public function build(): self
    {
        $this->applyMonitoringBcc();

        return $this->subject('Nuevo comentario en contrato de Lextrack')
            ->view('mail.contracts.comment-created')
            ->with([
                'contract' => $this->contract,
                'comment' => $this->comment,
                'recipient' => $this->recipient,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
