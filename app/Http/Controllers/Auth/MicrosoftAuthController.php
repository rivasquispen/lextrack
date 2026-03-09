<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class MicrosoftAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('graph')
            ->scopes([
                'openid',
                'profile',
                'email',
                'offline_access',
                'Files.ReadWrite.All',
                'Sites.ReadWrite.All',
            ])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $microsoftUser = Socialite::driver('graph')->stateless()->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('landing')->with('auth_error', 'No pudimos conectar con Microsoft. Intenta nuevamente.');
        }

        $email = $microsoftUser->getEmail();

        if (! $email) {
            return redirect()->route('landing')->with('auth_error', 'Tu cuenta de Microsoft no devuelve un correo válido.');
        }

        $displayName = $microsoftUser->getName() ?? $microsoftUser->getNickname() ?? $email;

        $user = User::firstOrNew(['email' => strtolower($email)]);
        $isNewUser = ! $user->exists;

        if (Schema::hasColumn('users', 'nombre')) {
            $user->nombre = $displayName;
        } else {
            $user->name = $displayName;
        }

        if ($isNewUser) {
            $user->password = Hash::make(Str::random(32));
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $user->email_verified_at = now();
        }

        if (Schema::hasColumn('users', 'token')) {
            $user->token = $microsoftUser->token ?? null;
        }

        if (Schema::hasColumn('users', 'refresh_token')) {
            $user->refresh_token = $microsoftUser->refreshToken ?? null;
        }

        if (Schema::hasColumn('users', 'token_expires_at') && $microsoftUser->expiresIn) {
            $user->token_expires_at = now()->addSeconds($microsoftUser->expiresIn);
        }

        if (Schema::hasColumn('users', 'active') && $isNewUser) {
            $user->active = false;
        }

        $user->save();

        if (method_exists($user, 'assignRole')) {
            if ($isNewUser && ! $user->hasRole('colaborador')) {
                $user->assignRole('colaborador');
            }

            $hasAdminAssigned = User::role('admin')->exists();

            if (! $hasAdminAssigned && ! $user->hasRole('admin')) {
                $user->assignRole('admin');
            }
        }

        if (Schema::hasColumn('users', 'active') && ! $user->active) {
            return redirect()->route('activation.pending')
                ->with('pending_email', $email);
        }

        if (Schema::hasColumn('users', 'last_login_at')) {
            $user->last_login_at = now();
            $user->save();
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
