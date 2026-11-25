<?php

namespace App\Services;

use App\Mail\ApprovalUnlockedMail;
use App\Models\Approval;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApprovalAccessService
{
    public function isManager(?User $user, Contract $contract): bool
    {
        return $user && ($user->hasRole('abogado')
            || ($user->hasRole('asesor') && (int) $contract->asesor_id === (int) $user->id));
    }

    public function canManage(?User $user, Contract $contract, ?ContractVersion $version): bool
    {
        return $this->isManager($user, $contract) && $version
            && (int) $version->contract_id === (int) $contract->id
            && in_array($contract->estado, [Contract::STATUS_CREATED, Contract::STATUS_ASSIGNED, Contract::STATUS_APPROVAL, Contract::STATUS_OBSERVED], true)
            && $version->estado !== 'aprobado'
            && ! app(ApprovalReopeningService::class)->hasSignatureEvidence($contract);
    }

    public function setUnlocked(User $actor, Contract $contract, Approval $approval, int $versionId, bool $unlocked): array
    {
        abort_unless($this->isManager($actor, $contract), 403);
        $changed = DB::transaction(function () use ($actor, $contract, $approval, $versionId, $unlocked) {
            [$contract, $version, $approval] = $this->lockContext($actor, $contract, $approval, $versionId);
            if ($approval->estado === 'aprobado' || $approval->aprobado_at !== null) {
                $this->reject('No puedes cambiar el candado de un usuario que ya aprobó.');
            }
            if ((bool) $approval->is_unlocked === $unlocked) {
                return false;
            }
            if ($unlocked && ! filter_var($approval->user?->email, FILTER_VALIDATE_EMAIL)) {
                $this->reject('El aprobador no tiene un correo válido para recibir la habilitación.');
            }
            if ($unlocked) {
                $approval->unlocked_at = now();
            } else {
                $approval->pauseEnabledClock();
            }
            $approval->is_unlocked = $unlocked;
            $approval->save();

            return true;
        });

        $sent = null;
        if ($changed && $unlocked) {
            try {
                $contract->loadMissing(['category', 'creator', 'advisor']);
                $approval->loadMissing('user');
                $sent = Mail::to($approval->user->email)->send(new ApprovalUnlockedMail($contract, $approval->user, $actor)) !== null;
                if (! $sent) {
                    throw new \RuntimeException('El transporte no confirmó el correo.');
                }
            } catch (Throwable $exception) {
                $sent = false;
                Log::warning('No se pudo confirmar el correo de habilitación de aprobación.', [
                    'contract_id' => $contract->id, 'approval_id' => $approval->id, 'exception_type' => $exception::class,
                ]);
            }
        }

        return ['changed' => $changed, 'is_unlocked' => $unlocked, 'mail_sent' => $sent];
    }

    public function move(User $actor, Contract $contract, Approval $approval, int $versionId, string $direction): void
    {
        abort_unless($this->isManager($actor, $contract), 403);
        abort_unless(in_array($direction, ['left', 'right'], true), 422);
        DB::transaction(function () use ($actor, $contract, $approval, $versionId, $direction) {
            [$contract, $version, $approval] = $this->lockContext($actor, $contract, $approval, $versionId);
            // The relation already orders by position/id; repeating them breaks SQL Server.
            $ordered = $version->approvals()->lockForUpdate()->get();
            $ids = $ordered->modelKeys();
            $index = array_search($approval->id, $ids, true);
            $neighbor = $index + ($direction === 'left' ? -1 : 1);
            if (! isset($ids[$neighbor])) {
                return;
            }
            [$ids[$index], $ids[$neighbor]] = [$ids[$neighbor], $ids[$index]];
            foreach ($ids as $position => $id) {
                Approval::whereKey($id)->update(['position' => $position + 1]);
            }
        });
    }

    private function lockContext(User $actor, Contract $contract, Approval $approval, int $versionId): array
    {
        $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
        abort_unless($this->isManager($actor, $contract), 403);
        $version = $contract->versions()->orderByDesc('numero_version')->lockForUpdate()->first();
        $approval = Approval::query()->lockForUpdate()->findOrFail($approval->id);
        abort_unless($contract->versions()->whereKey($approval->version_id)->exists(), 404);
        if (! $version || (int) $version->id !== (int) $approval->version_id || (int) $version->id !== $versionId) {
            $this->reject('Solo puedes administrar los aprobadores de la versión vigente. Recarga el contrato.');
        }
        if (! $this->canManage($actor, $contract, $version)) {
            $this->reject('El contrato ya no permite modificar los candados ni el orden.');
        }

        return [$contract, $version, $approval];
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['approval_access' => $message]);
    }
}
