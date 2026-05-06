<?php

namespace App\Mail;

use App\Mail\Concerns\AppliesMonitoringBcc;
use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdvisorAssignedMail extends Mailable
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
            'advisor' => 'Se te asignó un contrato en Lextrack',
            'creator' => 'Nuevo asesor asignado a tu contrato',
            'lawyer' => 'Se asignó un asesor a un contrato en Lextrack',
            default => 'Actualización de contrato',
        };

        $introLine = match ($this->recipientRole) {
            'advisor' => 'Has sido designado como asesor para este contrato. Revisa los detalles y coordina los siguientes pasos.',
            'creator' => 'Se asignó un asesor a tu contrato para ayudarte en el proceso. Puedes coordinar directamente con él para los ajustes necesarios.',
            'lawyer' => 'Se asignó un asesor a este contrato. Puedes ingresar para dar seguimiento y mantener visibilidad del flujo.',
            default => 'Hay una actualización relacionada a este contrato.',
        };

        $closingLine = match ($this->recipientRole) {
            'advisor' => 'Gracias por apoyar el flujo de contratos. Te avisaremos ante cualquier novedad.',
            'creator' => 'Puedes ingresar al contrato para revisar el avance junto al asesor asignado.',
            'lawyer' => 'Puedes ingresar al contrato para revisar el avance y las siguientes acciones.',
            default => 'Gracias por usar Lextrack.',
        };

        $recipientName = match ($this->recipientRole) {
            'advisor' => $this->contract->advisor->nombre ?? $this->contract->advisor->email ?? '',
            'creator' => $this->contract->creator->nombre ?? $this->contract->creator->email ?? '',
            default => '',
        };

        return $this->subject($subject)
            ->view('mail.contracts.advisor-assigned')
            ->with([
                'contract' => $this->contract,
                'introLine' => $introLine,
                'closingLine' => $closingLine,
                'recipientName' => $recipientName,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
