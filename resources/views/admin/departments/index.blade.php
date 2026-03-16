<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Administración</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Departamentos</h1>
        </div>
    </x-slot>

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

        <div class="grid gap-6 md:grid-cols-2">
            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Crear departamento</h2>
                    <p class="text-sm text-slate-500">Relaciona cada departamento con su empresa.</p>
                </div>
                <form action="{{ route('admin.departments.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Empresa</label>
                        <select name="company_id" class="mt-1 w-full rounded-xl border-slate-200">
                            <option value="">Sin empresa asociada</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-lex btn-lex-primary text-sm">Guardar departamento</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Listado</h2>
                    <p class="text-sm text-slate-500">{{ $departments->count() }} departamentos configurados.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($departments as $department)
                        <div class="py-4 space-y-3">
                            <form action="{{ route('admin.departments.update', $department) }}" method="POST" class="grid gap-3 md:grid-cols-2 md:items-end">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Nombre</label>
                                    <input type="text" name="nombre" value="{{ $department->nombre }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                </div>
                                <div>
                                    <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Empresa</label>
                                    <select name="company_id" class="mt-1 w-full rounded-xl border-slate-200">
                                        <option value="">Sin empresa asociada</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected($department->company_id == $company->id)>{{ $company->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-2 flex justify-end">
                                    <button type="submit" class="text-sm font-semibold text-primary">Guardar</button>
                                </div>
                            </form>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>
                                    Empresa: {{ $department->company->nombre ?? 'No definida' }} · Usuarios: {{ $department->users_count }}
                                </span>
                                <form action="{{ route('admin.departments.destroy', $department) }}" method="POST" onsubmit="return confirm('¿Eliminar el departamento {{ $department->nombre }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600" @disabled($department->users_count > 0)>Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aún no se han creado departamentos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
