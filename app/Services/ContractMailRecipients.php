<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Support\Collection;

class ContractMailRecipients
{
    public function forContract(Contract $contract, ?ContractVersion $version = null, ?int $excludeUserId = null): Collection
    {
        $contract->loadMissing([
            'creator:id,nombre,email',
            'lawyer:id,nombre,email',
            'advisor:id,nombre,email',
            'signers.user:id,nombre,email',
        ]);

        if ($version) {
            $version->loadMissing(['approvals.user:id,nombre,email']);
        }

        $recipients = collect([$contract->creator, $contract->lawyer, $contract->advisor])
            ->merge($version?->approvals?->pluck('user') ?? collect())
            ->merge($contract->signers->pluck('user'))
            ->filter(function ($recipient) use ($excludeUserId) {
                if (! $recipient || ! $recipient->email) {
                    return false;
                }

                if ($excludeUserId && (int) $recipient->id === $excludeUserId) {
                    return false;
                }

                return true;
            })
            ->unique(fn ($recipient) => strtolower((string) $recipient->email))
            ->values();

        if ($this->shouldIncludeLawyers($contract, $version)) {
            $lawyers = User::role('abogado')
                ->get(['id', 'nombre', 'email']);

            $recipients = $recipients
                ->merge($lawyers)
                ->filter(function ($recipient) use ($excludeUserId) {
                    if (! $recipient || ! $recipient->email) {
                        return false;
                    }

                    if ($excludeUserId && (int) $recipient->id === $excludeUserId) {
                        return false;
                    }

                    return true;
                })
                ->unique(fn ($recipient) => strtolower((string) $recipient->email))
                ->values();
        }

        return $recipients;
    }

    private function shouldIncludeLawyers(Contract $contract, ?ContractVersion $version = null): bool
    {
        if ($contract->lawyer_id || $contract->abogado_id || $contract->asesor_id) {
            return false;
        }

        $hasApprovers = $version
            ? $version->approvals->isNotEmpty()
            : $contract->approvals()->exists();

        return ! $hasApprovers;
    }
}
