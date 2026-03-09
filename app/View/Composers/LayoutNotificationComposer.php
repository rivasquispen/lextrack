<?php

namespace App\View\Composers;

use App\Models\Approval;
use App\Models\Contract;
use App\Models\ContractSigner;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LayoutNotificationComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        $menu = $this->buildMenu($user);
        $userName = $this->resolveUserName($user);
        $userInitials = $this->resolveInitials($userName);
        $notificationGroups = $this->buildNotificationGroups($user);
        $notificationCount = $notificationGroups->sum(fn ($group) => $group['items']->count());

        $view->with([
            'menu' => $menu,
            'userName' => $userName,
            'userInitials' => $userInitials,
            'notificationGroups' => $notificationGroups,
            'notificationCount' => $notificationCount,
        ]);
    }

    private function buildMenu(?User $user): array
    {
        return collect([
            [
                'label' => 'Contratos',
                'route' => route('dashboard'),
                'active' => request()->routeIs(['contracts.*', 'dashboard', 'flows.*', 'contracts.vers*']),
            ],
            [
                'label' => 'Marcas',
                'route' => route('brands.index'),
                'active' => request()->routeIs('brands.*'),
                'visible' => $user?->hasRole('marcas'),
            ],
        ])->filter(function ($item) {
            return $item['visible'] ?? true;
        })->values()->all();
    }

    private function resolveUserName(?User $user): string
    {
        if (! $user) {
            return 'Usuario';
        }

        return $user->nombre
            ?? $user->name
            ?? ($user->email ?? 'Usuario');
    }

    private function resolveInitials(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return 'US';
        }

        return mb_strtoupper(mb_substr($trimmed, 0, 2, 'UTF-8'), 'UTF-8');
    }

    private function buildNotificationGroups(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        $groups = collect();

        if ($user->hasRole('abogado')) {
            $unassignedContracts = $this->lawyerContractsWithoutAssignments($user);
            if ($unassignedContracts->isNotEmpty()) {
                $groups->push([
                    'key' => 'lawyer_unassigned',
                    'title' => 'Contratos sin asesor ni aprobador',
                    'items' => $unassignedContracts->map(fn ($contract) => [
                        'title' => $contract->codigo ?? 'Contrato',
                        'description' => $contract->titulo ?? 'Sin título',
                        'meta' => 'Creado '.optional($contract->created_at)->diffForHumans(),
                        'url' => route('contracts.show', $contract),
                        'tone' => 'primary',
                    ]),
                ]);
            }

            $pendingSignatures = $this->lawyerApprovedContractsPendingSignature($user);
            if ($pendingSignatures->isNotEmpty()) {
                $groups->push([
                    'key' => 'lawyer_signatures',
                    'title' => 'Contratos aprobados pendientes de firma',
                    'items' => $pendingSignatures->map(function ($contract) {
                        $pendingSigners = $contract->signers
                            ->map(fn ($signer) => $signer->user?->nombre ?? $signer->user?->name)
                            ->filter()
                            ->values();

                        $signerLabel = $pendingSigners->isNotEmpty()
                            ? 'Firmantes pendientes: '.$pendingSigners->implode(', ')
                            : 'Firmantes pendientes: asigna quienes deben firmar';

                        return [
                            'title' => $contract->codigo ?? 'Contrato',
                            'description' => $contract->titulo ?? 'Sin título',
                            'meta' => $signerLabel,
                            'url' => route('contracts.show', $contract),
                            'tone' => 'neutral',
                        ];
                    }),
                ]);
            }
        }

        if ($user->hasRole('asesor')) {
            $advisorContracts = $this->advisorPendingContracts($user);
            if ($advisorContracts->isNotEmpty()) {
                $groups->push([
                    'key' => 'advisor_pending',
                    'title' => 'Contratos bajo mi asesoría',
                    'items' => $advisorContracts->map(fn ($contract) => [
                        'title' => $contract->codigo ?? 'Contrato',
                        'description' => $contract->titulo ?? 'Sin título',
                        'meta' => 'Estado actual: '.($contract->status_label ?? 'En progreso'),
                        'url' => route('contracts.show', $contract),
                        'tone' => 'default',
                    ]),
                ]);
            }
        }

        if ($user->hasRole('aprobador')) {
            $approvals = $this->approverPendingApprovals($user);
            if ($approvals->isNotEmpty()) {
                $approverItems = $approvals->map(function ($approval) {
                    return [
                        'title' => $approval->contract_codigo ?? 'Contrato',
                        'description' => ($approval->contract_titulo ?? 'Sin título').' · Versión '.($approval->version_number ?? '–'),
                        'meta' => 'Asignado '.optional($approval->assigned_at)->diffForHumans(),
                        'url' => route('contracts.show', ['contract' => $approval->contract_id]),
                        'tone' => 'amber',
                    ];
                });

                $groups->push([
                    'key' => 'approvals',
                    'title' => 'Aprobaciones pendientes',
                    'items' => $approverItems,
                ]);
            }
        }

        if ($user->hasRole('colaborador')) {
            $createdContracts = $this->creatorPendingContracts($user);
            if ($createdContracts->isNotEmpty()) {
                $groups->push([
                    'key' => 'creator_pending',
                    'title' => 'Mis contratos por aprobar',
                    'items' => $createdContracts->map(fn ($contract) => [
                        'title' => $contract->codigo ?? 'Contrato',
                        'description' => $contract->titulo ?? 'Sin título',
                        'meta' => 'Estado actual: '.($contract->status_label ?? 'En progreso'),
                        'url' => route('contracts.show', $contract),
                        'tone' => 'default',
                    ]),
                ]);
            }
        }

        $signerAlerts = $this->userPendingSignatures($user);
        if ($signerAlerts->isNotEmpty()) {
            $signerItems = $signerAlerts->map(function ($signer) {
                $contract = $signer->contract;

                if (! $contract) {
                    return null;
                }

                return [
                    'title' => $contract->codigo ?? 'Contrato',
                    'description' => $contract->titulo ?? 'Sin título',
                    'meta' => 'Asignado '.optional($signer->created_at)->diffForHumans(),
                    'url' => route('contracts.show', $contract),
                    'tone' => 'default',
                ];
            })->filter()->values();

            if ($signerItems->isNotEmpty()) {
                $groups->push([
                    'key' => 'signer_pending',
                    'title' => 'Firmas pendientes',
                    'items' => $signerItems,
                ]);
            }
        }

        return $groups->values();
    }

    private function lawyerContractsWithoutAssignments(User $user)
    {
        return Contract::query()
            ->where('abogado_id', $user->id)
            ->whereNull('asesor_id')
            ->whereDoesntHave('approvals', function ($query) {
                $query->whereNull('aprobado_at');
            })
            ->latest('created_at')
            ->take(10)
            ->get(['id', 'codigo', 'titulo', 'created_at']);
    }

    private function lawyerApprovedContractsPendingSignature(User $user)
    {
        return Contract::query()
            ->with(['signers' => function ($query) {
                $query->whereNull('firmado_at')
                    ->with(['user:id,nombre,name']);
            }])
            ->where('abogado_id', $user->id)
            ->where('estado', Contract::STATUS_APPROVED)
            ->whereHas('signers', function ($query) {
                $query->whereNull('firmado_at');
            })
            ->latest('updated_at')
            ->take(10)
            ->get(['id', 'codigo', 'titulo', 'updated_at']);
    }

    private function advisorPendingContracts(User $user)
    {
        return Contract::query()
            ->where('asesor_id', $user->id)
            ->whereNotIn('estado', [Contract::STATUS_APPROVED, Contract::STATUS_SIGNED])
            ->latest('updated_at')
            ->take(10)
            ->get(['id', 'codigo', 'titulo', 'estado', 'updated_at']);
    }

    private function creatorPendingContracts(User $user)
    {
        return Contract::query()
            ->where('creado_por', $user->id)
            ->whereNotIn('estado', [Contract::STATUS_APPROVED, Contract::STATUS_SIGNED])
            ->latest('updated_at')
            ->take(10)
            ->get(['id', 'codigo', 'titulo', 'estado', 'updated_at']);
    }

    private function approverPendingApprovals(User $user)
    {
        return Approval::query()
            ->select([
                'approvals.id',
                'approvals.assigned_at',
                'approvals.version_id',
                'contracts.id as contract_id',
                'contracts.codigo as contract_codigo',
                'contracts.titulo as contract_titulo',
                'contract_versions.numero_version as version_number',
            ])
            ->join('contract_versions', 'contract_versions.id', '=', 'approvals.version_id')
            ->join('contracts', 'contracts.id', '=', 'contract_versions.contract_id')
            ->whereNull('contract_versions.deleted_at')
            ->whereNull('contracts.deleted_at')
            ->where('approvals.user_id', $user->id)
            ->whereNull('approvals.aprobado_at')
            ->orderByDesc('approvals.assigned_at')
            ->take(10)
            ->get();
    }

    private function userPendingSignatures(User $user)
    {
        return ContractSigner::query()
            ->where('user_id', $user->id)
            ->whereNull('firmado_at')
            ->with(['contract:id,codigo,titulo'])
            ->latest('created_at')
            ->take(10)
            ->get(['id', 'contract_id', 'created_at']);
    }
}
