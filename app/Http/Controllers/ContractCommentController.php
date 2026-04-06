<?php

namespace App\Http\Controllers;

use App\Mail\ContractCommentCreatedMail;
use App\Models\Contract;
use App\Models\ContractComment;
use App\Models\ContractVersion;
use App\Services\ContractMailRecipients;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContractCommentController extends Controller
{
    public function __construct(private ContractMailRecipients $mailRecipients)
    {
    }

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

        $comment = ContractComment::create([
            'contract_id' => $contract->id,
            'contract_version_id' => $data['contract_version_id'] ?? null,
            'user_id' => $user->id,
            'mensaje' => $data['mensaje'],
        ]);

        $version = null;
        if (! empty($data['contract_version_id'])) {
            $version = ContractVersion::with(['approvals.user:id,nombre,email'])
                ->where('contract_id', $contract->id)
                ->find($data['contract_version_id']);
        }

        $comment->loadMissing([
            'user:id,nombre,email',
            'version:id,contract_id,numero_version',
        ]);
        $contract->loadMissing(['category:id,nombre']);

        $ctaUrl = route('contracts.show', $contract);
        $recipients = $this->mailRecipients->forContract($contract, $version, $user->id);

        if ($recipients->isEmpty()) {
            $monitoringBcc = config('mail.monitoring_bcc');

            if (is_string($monitoringBcc) && trim($monitoringBcc) !== '') {
                Mail::to($monitoringBcc)
                    ->send(new ContractCommentCreatedMail($contract, $comment, $user, $ctaUrl));
            }
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)
                ->send(new ContractCommentCreatedMail($contract, $comment, $recipient, $ctaUrl));
        }

        return back()->with('status', 'Comentario agregado.');
    }

    private function canViewContract($user, Contract $contract): bool
    {
        return app(ContractController::class)->canViewContract($user, $contract);
    }
}
