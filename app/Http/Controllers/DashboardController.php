<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contract;
use Illuminate\Http\Request;
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

        $contractRecords = $contracts->map(function (Contract $contract) use ($user, $isLawyer) {
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

            return [
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
