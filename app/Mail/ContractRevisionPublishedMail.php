<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesMonitoringBcc;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractRevisionPublishedMail extends Mailable
{
    use AppliesMonitoringBcc, Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ContractVersion $version,
        public ?User $recipient = null,
        public ?User $actor = null,
        public ?string $ctaUrl = null
    ) {
        $this->ctaUrl = $ctaUrl ?? route('contracts.show', $this->contract);
    }

    public function build(): self
    {
        $this->applyMonitoringBcc();

        return $this->subject('Nueva revisión publicada en Lextrack')
            ->view('mail.contracts.revision-published')
            ->with([
                'contract' => $this->contract,
                'version' => $this->version,
                'recipient' => $this->recipient,
                'actor' => $this->actor,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
