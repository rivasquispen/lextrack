<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Perfil</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Información del usuario</h1>
        </div>
    </x-slot>

    @php
        $roles = $user->roles->pluck('name')->map(fn ($role) => ucfirst($role))->implode(', ');
        $company = $user->company;
        $country = $user->country;
    @endphp

    <div class="px-4 py-8 lg:px-10 space-y-6">
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4 lg:col-span-2">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Datos generales</p>
                    <h2 class="text-xl font-semibold text-primary-dark">Identificación</h2>
                </div>
                <dl class="grid gap-4 md:grid-cols-2 text-sm text-slate-600">
                    <div>
                        <dt class="font-semibold text-slate-500">Nombre completo</dt>
                        <dd class="mt-1 text-slate-900">{{ $user->nombre ?? 'No registrado' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Correo corporativo</dt>
                        <dd class="mt-1 text-slate-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Cargo</dt>
                        <dd class="mt-1 text-slate-900">{{ $user->cargo ?? 'No especificado' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Roles asignados</dt>
                        <dd class="mt-1 text-slate-900">{{ $roles ?: 'Sin rol asignado' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Actividad</p>
                    <h2 class="text-xl font-semibold text-primary-dark">Estado de acceso</h2>
                </div>
                <dl class="space-y-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <dt class="font-semibold text-slate-500">Estado</dt>
                        <dd class="px-3 py-1 rounded-full text-xs font-semibold {{ $user->active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                            {{ $user->active ? 'Activo' : 'Inactivo' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Último acceso</dt>
                        <dd class="mt-1 text-slate-900">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? 'Sin registro' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Cuenta creada</dt>
                        <dd class="mt-1 text-slate-900">{{ optional($user->created_at)->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Organización</p>
                <h2 class="text-xl font-semibold text-primary-dark">Información corporativa</h2>
            </div>
            <dl class="grid gap-4 md:grid-cols-3 text-sm text-slate-600">
                <div>
                    <dt class="font-semibold text-slate-500">Empresa</dt>
                    <dd class="mt-1 text-slate-900">{{ $company->nombre ?? 'No asignada' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">Razon social</dt>
                    <dd class="mt-1 text-slate-900">{{ $company->razon_social ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">RUC</dt>
                    <dd class="mt-1 text-slate-900">{{ $company->ruc ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500">País</dt>
                    <dd class="mt-1 text-slate-900">{{ $country->nombre ?? 'No especificado' }}</dd>
                </div>
            </dl>
        </section>

        <section class="bg-white rounded-2xl shadow-panel p-6">
            <p class="text-sm text-slate-500">
                La información de tu cuenta es administrada por el área de TI y por los administradores de Lextrack. Si necesitas actualizar algún dato o solicitar la baja de tu acceso, comunícate con el equipo responsable enviando un correo a <a href="mailto:soporte@medifarma.com.pe" class="text-primary font-semibold">soporte@medifarma.com.pe</a>.
            </p>
        </section>
    </div>
</x-app-layout>
