<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\ApprovalReopening;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalReopeningService
{
    public function eligibleContracts(): Builder
    {
        return Contract::query()
            ->where('estado', Contract::STATUS_APPROVAL)
            ->where(fn ($query) => $query->whereNull('document_signed')->orWhere('document_signed', ''))
            ->whereDoesntHave('signers', fn ($query) => $query
                ->where(fn ($query) => $query->whereNotNull('firmado_at')->orWhere('estado', 'firmado')))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('signatures')
                    ->join('contract_versions', 'contract_versions.id', '=', 'signatures.version_id')
                    ->whereColumn('contract_versions.contract_id', 'contracts.id')
                    ->where(fn ($query) => $query->where('signatures.firmado', true)
                        ->orWhereNotNull('signatures.firmado_at')
                        ->orWhere(fn ($query) => $query->whereNotNull('signatures.documento_firmado')
                            ->where('signatures.documento_firmado', '!=', '')));
            })
            ->whereHas('latestVersion', fn ($query) => $query
                ->where('estado', '!=', 'aprobado')
                ->whereHas('approvals', fn ($query) => $query->where('estado', 'aprobado')->whereNotNull('aprobado_at'))
                ->whereHas('approvals', fn ($query) => $query->where('estado', 'pendiente')));
    }

    public function hasSignatureEvidence(Contract $contract): bool
    {
        return filled($contract->document_signed)
            || $contract->estado === Contract::STATUS_SIGNED
            || $contract->signers()->where(fn ($query) => $query->whereNotNull('firmado_at')->orWhere('estado', 'firmado'))->exists()
            || DB::table('signatures')->join('contract_versions', 'contract_versions.id', '=', 'signatures.version_id')
                ->where('contract_versions.contract_id', $contract->id)
                ->where(fn ($query) => $query->where('signatures.firmado', true)
                    ->orWhereNotNull('signatures.firmado_at')
                    ->orWhere(fn ($query) => $query->whereNotNull('signatures.documento_firmado')->where('signatures.documento_firmado', '!=', '')))
                ->exists();
    }

    public function reopen(User $admin, Contract $contract, Approval $approval, array $data): ApprovalReopening
    {
        abort_unless($admin->hasRole('admin'), 403);

        return DB::transaction(function () use ($admin, $contract, $approval, $data) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $version = $contract->versions()->orderByDesc('numero_version')->lockForUpdate()->first();
            $approval = Approval::query()->lockForUpdate()->findOrFail($approval->id);

            abort_unless($version && $contract->versions()->whereKey($approval->version_id)->exists(), 404);

            if ($contract->estado !== Contract::STATUS_APPROVAL || $this->hasSignatureEvidence($contract)) {
                $this->reject('Solo se permite corregir contratos en aprobación y sin evidencia de firma.');
            }

            if ((int) $approval->version_id !== (int) $version->id || (int) $data['version_id'] !== (int) $version->id) {
                $this->reject('La aprobación no corresponde a la versión vigente. Recarga el listado.');
            }

            if ($version->estado === 'aprobado' || ! $version->approvals()->where('estado', 'pendiente')->exists()) {
                $this->reject('La versión ya completó sus aprobaciones. Debe utilizarse el procedimiento del abogado.');
            }

            if ($approval->estado !== 'aprobado' || ! $approval->aprobado_at
                || $approval->aprobado_at->toISOString() !== $data['approval_date']) {
                $this->reject('La aprobación cambió desde que abriste la pantalla. Recarga antes de continuar.');
            }

            $lawyer = User::find($data['lawyer_id']);
            if (! $lawyer || ! $lawyer->hasRole('abogado')) {
                $this->reject('Selecciona un usuario con rol abogado para registrar la coordinación.');
            }

            $approval->load('user');
            $event = ApprovalReopening::create([
                'contract_id' => $contract->id, 'version_id' => $version->id,
                'approval_id' => $approval->id, 'approver_id' => $approval->user_id,
                'admin_id' => $admin->id, 'lawyer_id' => $lawyer->id,
                'version_number' => $version->numero_version, 'previous_status' => $approval->estado,
                'previous_approved_at' => $approval->aprobado_at,
                'reason' => $data['reason'], 'coordination_reference' => $data['coordination_reference'],
                'snapshot' => [
                    'contract_code' => $contract->codigo, 'contract_title' => $contract->titulo,
                    'contract_status' => $contract->estado, 'version_status' => $version->estado,
                    'approval_date' => $approval->aprobado_at->toISOString(),
                    'assigned_at' => $approval->assigned_at?->toISOString(),
                    'approver' => $approval->user?->nombre ?? $approval->user?->email,
                    'admin' => $admin->nombre ?? $admin->email, 'lawyer' => $lawyer->nombre ?? $lawyer->email,
                ],
                'created_at' => now(),
            ]);

            $approval->update(['estado' => 'pendiente', 'aprobado_at' => null, 'unlocked_at' => $approval->is_unlocked ? now() : null]);

            return $event;
        });
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['approval' => $message]);
    }
}
