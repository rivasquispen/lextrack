<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Panel</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Empresas del grupo</h1>
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

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Registrar empresa</h2>
                    <p class="text-sm text-slate-500">Incorpora nuevas razones sociales del grupo.</p>
                </div>
                <form action="{{ route('admin.companies.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Nombre comercial</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Ej: HumanovaLab" required>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Razón social</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-600">RUC / Identificador fiscal</label>
                        <input type="text" name="ruc" value="{{ old('ruc') }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-lex btn-lex-primary">Guardar empresa</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-dark">Empresas</h2>
                        <p class="text-sm text-slate-500">{{ $companies->count() }} registros.</p>
                    </div>
                </div>
                <div class="space-y-4">
                    @forelse ($companies as $company)
                        <div class="border border-slate-100 rounded-2xl p-4 space-y-3">
                            <form action="{{ route('admin.companies.update', $company) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Nombre</label>
                                        <input type="text" name="nombre" value="{{ $company->nombre }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                    </div>
                                    <div>
                                        <label class="text-xs uppercase tracking-[0.3em] text-slate-400">RUC</label>
                                        <input type="text" name="ruc" value="{{ $company->ruc }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Razón social</label>
                                        <input type="text" name="razon_social" value="{{ $company->razon_social }}" class="mt-1 w-full rounded-xl border-slate-200">
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <p>{{ $company->users_count }} usuarios</p>
                                    <button type="submit" class="text-primary font-semibold">Guardar</button>
                                </div>
                            </form>
                            <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" onsubmit="return confirm('¿Eliminar la empresa {{ $company->nombre }}?');" class="flex justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-600" @disabled($company->users_count > 0)>Eliminar empresa</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay empresas registradas.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
