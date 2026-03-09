<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContractCommentController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! ($this->canViewContract($user, $contract))) {
            abort(403);
        }

        $data = $request->validate([
            'mensaje' => ['required', 'string'],
            'contract_version_id' => ['nullable', 'exists:contract_versions,id'],
        ]);

        ContractComment::create([
            'contract_id' => $contract->id,
            'contract_version_id' => $data['contract_version_id'] ?? null,
            'user_id' => $user->id,
            'mensaje' => $data['mensaje'],
        ]);

        return back()->with('status', 'Comentario agregado.');
    }

    private function canViewContract($user, Contract $contract): bool
    {
        return app(ContractController::class)->canViewContract($user, $contract);
    }
}
