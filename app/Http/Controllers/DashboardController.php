<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $isLawyer = $user->hasRole('abogado');

        $contractsQuery = Contract::with([
            'category:id,nombre',
            'lawyer:id,nombre',
            'advisor:id,nombre',
            'creator:id,nombre',
            'latestVersion' => fn ($query) => $query->select('contract_versions.id', 'contract_versions.contract_id', 'contract_versions.numero_version', 'contract_versions.estado'),
            'latestVersion.approvals' => fn ($query) => $query->where('is_unlocked', true)->where('estado', 'pendiente')->whereNull('aprobado_at'),
            'latestVersion.approvals.user:id,nombre,email',
            'approvals' => fn ($query) => $query->where('user_id', $user->id),
            'signers' => fn ($query) => $query->where('user_id', $user->id),
        ]);

        if (! $isLawyer) {
            $contractsQuery->where(function ($query) use ($user) {
                $query->where('creado_por', $user->id)
                    ->orWhere('abogado_id', $user->id)
                    ->orWhere('asesor_id', $user->id)
                    ->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                        $approvalQuery->where('approvals.user_id', $user->id);
                    })
                    ->orWhereHas('signers', function ($signerQuery) use ($user) {
                        $signerQuery->where('contract_signers.user_id', $user->id);
                    });
            });
        }

        $contracts = $contractsQuery
            ->orderByDesc('updated_at')
            ->get();

        // Batch the signature check to avoid querying once for every contract row.
        $signedContractIds = Contract::query()->whereKey($contracts->modelKeys())
            ->where(function ($query) {
                $query->where(fn ($q) => $q->whereNotNull('document_signed')->where('document_signed', '!=', ''))
                    ->orWhereHas('signers', fn ($q) => $q->whereNotNull('firmado_at')->orWhere('estado', 'firmado'))
                    ->orWhereExists(function ($q) {
                        $q->select(DB::raw(1))->from('signatures')
                            ->join('contract_versions', 'contract_versions.id', '=', 'signatures.version_id')
                            ->whereColumn('contract_versions.contract_id', 'contracts.id')
                            ->where(fn ($s) => $s->where('signatures.firmado', true)->orWhereNotNull('signatures.firmado_at')
                                ->orWhere(fn ($d) => $d->whereNotNull('signatures.documento_firmado')->where('signatures.documento_firmado', '!=', '')));
                    });
            })->pluck('contracts.id')->flip();

        $contractRecords = $contracts->map(function (Contract $contract) use ($user, $isLawyer, $signedContractIds) {
            $roles = [];

            if ((int) $contract->creado_por === (int) $user->id) {
                $roles[] = 'creador';
            }

            if ($contract->abogado_id && (int) $contract->abogado_id === (int) $user->id) {
                $roles[] = 'abogado';
            }

            if ($contract->approvals->isNotEmpty()) {
                $roles[] = 'aprobador';
            }

            if ($contract->signers->isNotEmpty()) {
                $roles[] = 'firmante';
            }

            if ($contract->asesor_id && (int) $contract->asesor_id === (int) $user->id) {
                $roles[] = 'asesor';
            }

            if ($isLawyer && empty($roles)) {
                $roles[] = 'observador';
            }

            $pendingApprovers = in_array($contract->estado, [Contract::STATUS_APPROVAL, Contract::STATUS_OBSERVED], true)
                && $contract->latestVersion && $contract->latestVersion->estado !== 'aprobado'
                && ! $signedContractIds->has($contract->id)
                    ? $contract->latestVersion->approvals
                    : collect();

            return [
                'pending_approvers' => $pendingApprovers,
                'contract' => $contract,
                'roles' => $roles,
            ];
        });

        if (! $isLawyer) {
            $contractRecords = $contractRecords->filter(fn ($record) => ! empty($record['roles']))->values();
        } else {
            $contractRecords = $contractRecords->values();
        }

        $roleFilters = [
            'creador' => 'Creados por mí',
            'asesor' => 'A mi cargo',
            'aprobador' => 'Debo aprobar',
        ];

        if ($isLawyer) {
            $roleFilters['observador'] = 'Supervisión legal';
        }

        $categories = Category::orderBy('nombre')->get(['id', 'nombre']);

        $categoryCounts = $contractRecords
            ->groupBy(fn ($record) => $record['contract']->categoria_id ?? '__none__')
            ->map(fn ($group) => $group->count());

        return view('contracts.index', [
            'roleFilters' => $roleFilters,
            'contractRecords' => $contractRecords,
            'statusLabels' => Contract::STATUS_LABELS,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'hasUncategorized' => $categoryCounts->has('__none__'),
        ]);
    }
}
