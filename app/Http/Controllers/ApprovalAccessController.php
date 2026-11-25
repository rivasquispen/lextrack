<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Contract;
use App\Services\ApprovalAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalAccessController extends Controller
{
    public function update(Request $request, Contract $contract, Approval $approval, ApprovalAccessService $service): RedirectResponse
    {
        abort_unless($service->isManager($request->user(), $contract), 403);
        $data = $request->validate(['version_id' => ['required', 'integer'], 'is_unlocked' => ['required', 'boolean']]);
        $result = $service->setUnlocked($request->user(), $contract, $approval, (int) $data['version_id'], (bool) $data['is_unlocked']);
        if ($result['mail_sent'] === false) {
            return redirect()->route('contracts.show', $contract)->withFragment('approval-flow')->withErrors(['approval_access' => 'El candado quedó abierto, pero no se pudo confirmar el correo. Revisa el log. Guardar nuevamente el candado abierto no reenvía la notificación.']);
        }

        return redirect()->route('contracts.show', $contract)->withFragment('approval-flow')->with('status', ! $result['changed'] ? 'El candado ya tenía ese estado.' : ($result['is_unlocked'] ? 'Aprobador habilitado. El transporte de correo aceptó la notificación.' : 'Aprobador bloqueado.'));
    }

    public function move(Request $request, Contract $contract, Approval $approval, ApprovalAccessService $service): RedirectResponse
    {
        abort_unless($service->isManager($request->user(), $contract), 403);
        $data = $request->validate(['version_id' => ['required', 'integer'], 'direction' => ['required', 'in:left,right']]);
        $service->move($request->user(), $contract, $approval, (int) $data['version_id'], $data['direction']);

        return redirect()->route('contracts.show', $contract)->withFragment('approval-flow')->with('status', 'Orden visual de aprobadores actualizado.');
    }
}
