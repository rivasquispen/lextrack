<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalReopening;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\ApprovalReopened;
use App\Services\ApprovalReopeningService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApprovalReopeningController extends Controller
{
    public function index(Request $request, ApprovalReopeningService $service): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $search = trim((string) $request->input('q', ''));
        $contracts = $service->eligibleContracts()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('codigo', 'like', '%'.$search.'%')->orWhere('titulo', 'like', '%'.$search.'%');
                if (filter_var($search, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
                    $query->orWhere('id', $search);
                }
            }))
            ->with('latestVersion.approvals.user:id,nombre,email')
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.approval-reopenings.index', [
            'contracts' => $contracts, 'search' => $search,
            'lawyers' => User::role('abogado')->orderBy('nombre')->get(['id', 'nombre', 'email']),
            'reopenings' => ApprovalReopening::orderByDesc('id')->paginate(20, ['*'], 'history_page')->withQueryString(),
        ]);
    }

    public function store(Request $request, Contract $contract, Approval $approval, ApprovalReopeningService $service): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'version_id' => ['required', 'integer'],
            'approval_date' => ['required', 'string', 'max:60'],
            'lawyer_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'coordination_reference' => ['required', 'string', 'min:5', 'max:1000'],
            'confirmation' => ['accepted'],
        ]);

        $event = $service->reopen($request->user(), $contract, $approval, $data);
        $event->load(['approver', 'lawyer']);
        $notificationFailed = false;
        foreach (collect([$event->approver, $event->lawyer])->filter()->unique('id') as $recipient) {
            try {
                $recipient->notify(new ApprovalReopened($event));
            } catch (Throwable $exception) {
                $notificationFailed = true;
                Log::warning('No se pudo notificar una reapertura de aprobación.', [
                    'reopening_id' => $event->id, 'recipient_id' => $recipient->id,
                ]);
            }
        }

        return redirect()->route('admin.approval-reopenings.index', ['q' => $contract->id])
            ->with('status', 'Aprobación reabierta. El contrato, sus documentos y versiones no se modificaron.'
                .($notificationFailed ? ' No se pudieron completar las notificaciones; coordina directamente con los involucrados.' : ' Se notificó al aprobador y al abogado coordinador.'));
    }
}
