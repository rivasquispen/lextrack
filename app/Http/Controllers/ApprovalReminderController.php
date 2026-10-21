<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Contract;
use App\Services\ApprovalReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalReminderController extends Controller
{
    public function store(Request $request, Contract $contract, Approval $approval, ApprovalReminderService $service): RedirectResponse
    {
        abort_unless($service->isAssignedSender($request->user(), $contract), 403);
        $data = $request->validate(['version_id' => ['required', 'integer']]);
        $service->send($request->user(), $contract, $approval, (int) $data['version_id']);

        return back()->with('status', 'El recordatorio fue procesado con el servicio de correo configurado. Se registró el envío; podrás enviar otro para esta aprobación dentro de 24 horas.');
    }
}
