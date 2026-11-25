<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesMonitoringBcc;
use App\Models\Approval;
use App\Models\ApprovalReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApprovalReminderMail extends Mailable
{
    use AppliesMonitoringBcc, Queueable, SerializesModels;

    public function __construct(public ApprovalReminder $reminder) {}

    public function build(): self
    {
        $this->applyMonitoringBcc();
        $days = $this->reminder->pending_days;
        $elapsed = $days === 0 ? 'menos de un día' : ($days === 1 ? '1 día' : $days.' días');

        if (array_key_exists('enabled_seconds', $this->reminder->snapshot)) {
            $elapsed = Approval::formatEnabledSeconds((int) $this->reminder->snapshot['enabled_seconds']);
        }

        return $this->subject('Recordatorio: contrato '.$this->reminder->snapshot['contract_code'].' pendiente de aprobación')
            ->view('mail.contracts.approval-reminder')
            ->with([
                'snapshot' => $this->reminder->snapshot, 'elapsed' => $elapsed,
                'ctaUrl' => route('contracts.show', $this->reminder->contract_id),
            ]);
    }
}
