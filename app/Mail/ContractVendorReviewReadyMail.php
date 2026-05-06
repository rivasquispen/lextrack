<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesMonitoringBcc;
use App\Models\Contract;
use App\Models\ContractVersionHistory;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractVendorReviewReadyMail extends Mailable
{
    use AppliesMonitoringBcc, Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ContractVersionHistory $history,
        public ?User $recipient = null,
        public ?User $actor = null,
        public ?string $ctaUrl = null
    ) {
        $this->ctaUrl = $ctaUrl ?? route('contracts.show', $this->contract);
    }

    public function build(): self
    {
        $this->applyMonitoringBcc();

        return $this->subject('Revisión conforme para envío al proveedor en Lextrack')
            ->view('mail.contracts.vendor-review-ready')
            ->with([
                'contract' => $this->contract,
                'history' => $this->history,
                'recipient' => $this->recipient,
                'actor' => $this->actor,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
