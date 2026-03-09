<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Panel</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Administrar permisos</h1>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10 space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('admin.roles.index') }}" class="text-sm text-slate-500 hover:text-primary flex items-center gap-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                </svg>
                Volver a roles
            </a>
        </div>

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
            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Nuevo permiso</h2>
                    <p class="text-sm text-slate-500">Crea identificadores que luego podrás asignar a los roles.</p>
                </div>

                <form action="{{ route('admin.permissions.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Nombre interno</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Ej: manage-contracts" required>
                        @error('name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-lex btn-lex-primary">Crear permiso</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-dark">Permisos registrados</h2>
                        <p class="text-sm text-slate-500">Lista completa de identificadores disponibles.</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">{{ $permissions->count() }} en total</span>
                </div>
                <div class="divide-y divide-slate-100 mt-4">
                    @forelse ($permissions as $permission)
                        <div class="py-4 flex flex-col gap-3">
                            <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" class="flex flex-col gap-2 md:flex-row md:items-center md:gap-3">
                                @csrf
                                @method('PUT')
                                <div class="flex-1">
                                    <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Nombre</label>
                                    <input type="text" name="name" value="{{ $permission->name }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" class="text-sm font-semibold text-primary">Guardar</button>
                                </div>
                            </form>
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-slate-500">Asignado a {{ $permission->roles_count }} roles</p>
                                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" onsubmit="return confirm('¿Eliminar el permiso {{ $permission->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-600 font-semibold" @disabled($permission->roles_count > 0)>
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No hay permisos registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
