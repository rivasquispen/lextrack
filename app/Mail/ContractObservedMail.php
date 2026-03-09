<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractObservedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ContractVersion $version,
        public ?User $recipient = null,
        public ?string $ctaUrl = null,
        public ?string $observerName = null
    ) {
        $this->ctaUrl = $ctaUrl ?? route('contracts.versions.show', [$this->contract, $this->version]);
    }

    public function build(): self
    {
        return $this->subject('Contrato observado en Lextrack')
            ->view('mail.contracts.contract-observed')
            ->with([
                'contract' => $this->contract,
                'version' => $this->version,
                'recipient' => $this->recipient,
                'ctaUrl' => $this->ctaUrl,
                'observerName' => $this->observerName,
            ]);
    }
}
