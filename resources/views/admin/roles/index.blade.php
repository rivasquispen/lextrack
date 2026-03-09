<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Panel</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Roles y permisos</h1>
        </div>
    </x-slot>

    @php
        $oldForm = old('form');
        $oldRoleId = old('role_id');
    @endphp

    <div class="px-4 py-8 lg:px-10 space-y-6">
        @if (session('status'))
            <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-2xl px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-rose-50 text-rose-700 border border-rose-200 rounded-2xl px-4 py-3 space-y-1">
                <p class="font-semibold">No se pudo completar la acción:</p>
                <ul class="text-sm list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Crear nuevo rol</h2>
                    <p class="text-sm text-slate-500">Define roles internos y asocia los permisos disponibles.</p>
                </div>
                <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="form" value="create">
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Nombre interno</label>
                        <input type="text" name="name" value="{{ $oldForm === 'create' ? old('name') : '' }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Ej: abogado" required>
                        @if ($errors->has('name') && $oldForm === 'create')
                            <p class="text-xs text-rose-600 mt-1">{{ $errors->first('name') }}</p>
                        @endif
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-slate-600">Permisos asignados</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($permissions as $permission)
                                @php
                                    $createSelected = $oldForm === 'create'
                                        ? collect(old('permissions', []))->contains($permission->name)
                                        : false;
                                @endphp
                                <label class="inline-flex items-center gap-2 text-xs font-medium {{ $createSelected ? 'text-primary' : 'text-slate-500' }}">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded border-slate-300" @checked($createSelected)>
                                    {{ \Illuminate\Support\Str::headline($permission->name) }}
                                </label>
                            @endforeach
                        </div>
                        @if (($errors->has('permissions') || $errors->has('permissions.*')) && $oldForm === 'create')
                            <p class="text-xs text-rose-600">Selecciona permisos válidos.</p>
                        @endif
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-lex btn-lex-primary">Crear rol</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-dark">Permisos disponibles</h2>
                        <p class="text-sm text-slate-500">Estos permisos controlan el acceso al panel administrativo.</p>
                    </div>
                    <a href="{{ route('admin.permissions.index') }}" class="btn-lex btn-lex-secondary text-sm">Administrar permisos</a>
                </div>
                <ul class="space-y-3">
                    @forelse ($permissions as $permission)
                        <li class="flex items-center justify-between border border-slate-100 rounded-2xl px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-700">{{ \Illuminate\Support\Str::headline($permission->name) }}</p>
                                <p class="text-xs text-slate-500">Identificador: <span class="font-mono text-slate-600">{{ $permission->name }}</span></p>
                            </div>
                            <span class="text-[11px] uppercase tracking-[0.3em] text-primary">Activo</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No hay permisos registrados.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-panel p-6 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Roles registrados</h2>
                    <p class="text-sm text-slate-500">Actualiza permisos o elimina roles que ya no necesites.</p>
                </div>
            </div>

            <div class="space-y-5">
                @forelse ($roles as $role)
                    @php
                        $isEditingThisRole = $oldForm === 'update' && (string) $oldRoleId === (string) $role->id;
                        $rolePermissions = $isEditingThisRole
                            ? collect(old('permissions', []))
                            : $role->permissions->pluck('name');
                    @endphp
                    <div class="border border-slate-100 rounded-2xl p-5 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xl font-semibold text-primary-dark">{{ ucfirst($role->name) }}</p>
                                <p class="text-sm text-slate-500">{{ $role->users_count }} {{ \Illuminate\Support\Str::plural('usuario', $role->users_count) }} asignado{{ $role->users_count === 1 ? '' : 's' }}</p>
                            </div>
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('¿Eliminar el rol {{ $role->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-700 disabled:opacity-50" @disabled($role->name === 'admin' || $role->users_count > 0)>
                                    Eliminar
                                </button>
                            </form>
                        </div>
                        <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="form" value="update">
                            <input type="hidden" name="role_id" value="{{ $role->id }}">
                            <div>
                                <label class="text-sm font-semibold text-slate-600">Nombre interno</label>
                                <input type="text" name="name" value="{{ $isEditingThisRole ? old('name') : $role->name }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                @if ($errors->has('name') && $isEditingThisRole)
                                    <p class="text-xs text-rose-600 mt-1">{{ $errors->first('name') }}</p>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-600 mb-2">Permisos asociados</p>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $checked = $rolePermissions->contains($permission->name);
                                        @endphp
                                        <label class="inline-flex items-center gap-2 text-xs font-medium {{ $checked ? 'text-primary' : 'text-slate-500' }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded border-slate-300" @checked($checked)>
                                            {{ \Illuminate\Support\Str::headline($permission->name) }}
                                        </label>
                                    @endforeach
                                </div>
                                @if (($errors->has('permissions') || $errors->has('permissions.*')) && $isEditingThisRole)
                                    <p class="text-xs text-rose-600 mt-1">Selecciona permisos válidos.</p>
                                @endif
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="btn-lex btn-lex-secondary">Actualizar rol</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay roles registrados todavía.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
