<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing('roles', 'countries', 'company');

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort(403, 'La actualización de perfil está deshabilitada.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort(403, 'La eliminación de cuentas no está permitida en este módulo.');
    }
}
