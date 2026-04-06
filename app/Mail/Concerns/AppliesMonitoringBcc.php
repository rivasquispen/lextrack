<?php

namespace App\Mail\Concerns;

trait AppliesMonitoringBcc
{
    protected function applyMonitoringBcc(): void
    {
        $monitoringBcc = config('mail.monitoring_bcc');

        if (is_string($monitoringBcc) && trim($monitoringBcc) !== '') {
            $this->bcc($monitoringBcc);
        }
    }
}
