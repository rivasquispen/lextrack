<?php

namespace App\Services;

use App\Mail\ApprovalReminderMail;
use App\Models\Approval;
use App\Models\ApprovalReminder;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ApprovalReminderService
{
    public function isAssignedSender(?User $user, Contract $contract): bool
    {
        return $user && (
            $user->hasRole('abogado')
            || ($user->hasRole('asesor') && (int) $contract->asesor_id === (int) $user->id)
        );
    }

    public function canRemind(?User $user, Contract $contract, ?ContractVersion $version): bool
    {
        return $this->isAssignedSender($user, $contract)
            && $version && (int) $version->contract_id === (int) $contract->id
            && in_array($contract->estado, [Contract::STATUS_APPROVAL, Contract::STATUS_OBSERVED], true)
            && $version->estado !== 'aprobado'
            && ! app(ApprovalReopeningService::class)->hasSignatureEvidence($contract);
    }

    public function send(User $sender, Contract $contract, Approval $approval, int $expectedVersionId): ApprovalReminder
    {
        abort_unless($this->isAssignedSender($sender, $contract), 403);

        $reminder = DB::transaction(function () use ($sender, $contract, $approval, $expectedVersionId) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            abort_unless($this->isAssignedSender($sender, $contract), 403);

            $version = $contract->versions()->orderByDesc('numero_version')->lockForUpdate()->first();
            $approval = Approval::query()->lockForUpdate()->findOrFail($approval->id);
            abort_unless($contract->versions()->whereKey($approval->version_id)->exists(), 404);

            if (! $version || (int) $version->id !== (int) $approval->version_id || (int) $version->id !== $expectedVersionId) {
                $this->reject('Solo puedes enviar recordatorios de la versión vigente. Recarga el contrato.');
            }
            if (! $this->canRemind($sender, $contract, $version)) {
                $this->reject('El contrato ya no permite enviar recordatorios de aprobación.');
            }
            if ($approval->estado !== 'pendiente' || $approval->aprobado_at !== null) {
                $this->reject('Este usuario ya no tiene una aprobación pendiente.');
            }

            $recent = ApprovalReminder::where('approval_id', $approval->id)
                ->where('created_at', '>', now()->subHours(24))->latest('id')->first();
            if ($recent) {
                $this->reject('Ya se registró un intento de recordatorio para este aprobador durante las últimas 24 horas.');
            }

            $recipient = $approval->user()->first();
            if (! $recipient || ! filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                $this->reject('El aprobador no tiene un correo válido para recibir el recordatorio.');
            }
            $assignedAt = $approval->assigned_at ?? $approval->created_at;
            if (! $approval->is_unlocked || ! $approval->unlocked_at || $approval->unlocked_at->isFuture()) {
                $this->reject('El aprobador debe estar habilitado y tener una fecha de habilitación válida para recibir el recordatorio.');
            }
            $seconds = $approval->totalEnabledSeconds();
            $days = intdiv($seconds, 86400);

            $reminder = ApprovalReminder::create([
                'contract_id' => $contract->id, 'version_id' => $version->id, 'approval_id' => $approval->id,
                'sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'pending_days' => $days,
                'status' => 'sending',
                'snapshot' => [
                    'contract_code' => $contract->codigo, 'contract_title' => $contract->titulo,
                    'sender_name' => trim((string) $sender->nombre) ?: $sender->email,
                    'recipient_name' => trim((string) $recipient->nombre) ?: $recipient->email,
                    'recipient_email' => $recipient->email, 'assigned_at' => $assignedAt?->toISOString(),
                    'enabled_seconds' => $seconds, 'unlocked_at' => $approval->unlocked_at->toISOString(),
                ],
            ]);
            if (! $reminder->exists) {
                throw new RuntimeException('No se pudo registrar el intento antes del envío.');
            }

            return $reminder;
        });

        try {
            $sent = Mail::to($reminder->snapshot['recipient_email'])->send(new ApprovalReminderMail($reminder));
            if ($sent === null) {
                throw new RuntimeException('El transporte no confirmó el envío.');
            }
        } catch (Throwable $exception) {
            Log::warning('Falló el envío de un recordatorio de aprobación.', [
                'reminder_id' => $reminder->id, 'contract_id' => $reminder->contract_id,
                'exception_type' => get_class($exception),
            ]);
            try {
                if (! $reminder->update(['status' => 'failed', 'failure_type' => get_class($exception)])) {
                    throw new RuntimeException('No se pudo guardar el resultado del intento.');
                }
            } catch (Throwable $persistenceException) {
                Log::error('No se pudo registrar el fallo del recordatorio.', ['reminder_id' => $reminder->id]);
            }
            $this->reject('No se pudo confirmar el envío del recordatorio. El intento quedó registrado; revisa el log antes de volver a intentar cuando transcurran 24 horas.');
        }

        try {
            if (! $reminder->update(['status' => 'sent', 'sent_at' => now()])) {
                throw new RuntimeException('No se pudo guardar la confirmación del envío.');
            }
        } catch (Throwable $exception) {
            Log::error('Recordatorio enviado, pero no se pudo actualizar su auditoría.', ['reminder_id' => $reminder->id]);
            $this->reject('El transporte aceptó el correo, pero no se pudo actualizar su estado. Revisa el log; no repitas el envío.');
        }

        Log::info('Recordatorio de aprobación aceptado por el transporte.', [
            'reminder_id' => $reminder->id, 'contract_id' => $reminder->contract_id,
            'sender_id' => $reminder->sender_id, 'recipient_id' => $reminder->recipient_id,
            'pending_days' => $reminder->pending_days, 'mailer' => config('mail.default'),
        ]);

        return $reminder;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['approval_reminder' => $message]);
    }
}
