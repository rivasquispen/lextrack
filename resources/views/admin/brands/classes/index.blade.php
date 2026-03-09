<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Marcas</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Clases de Niza</h1>
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

        <div class="grid gap-6 lg:grid-cols-[1fr,1.2fr]">
            <div class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Registrar clase</h2>
                    <p class="text-sm text-slate-500">Define las clases con su número y descripción oficial.</p>
                </div>
                <form action="{{ route('admin.brand-classes.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid gap-3 lg:grid-cols-[120px,1fr]">
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Número</label>
                            <input type="number" name="number" value="{{ old('number') }}" min="1" max="255" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Ej: 35" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Descripción</label>
                            <input type="text" name="description" value="{{ old('description') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Servicios de publicidad" required>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-lex btn-lex-primary">Agregar clase</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-dark">Listado</h2>
                        <p class="text-sm text-slate-500">{{ $brandClasses->count() }} clases configuradas.</p>
                    </div>
                </div>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($brandClasses as $brandClass)
                        <div class="py-3 space-y-3">
                            <form action="{{ route('admin.brand-classes.update', $brandClass) }}" method="POST" class="grid gap-3 lg:grid-cols-[120px,1fr,auto]">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Número</label>
                                    <input type="number" name="number" value="{{ $brandClass->number }}" min="1" max="255" class="mt-1 w-full rounded-xl border-slate-200" required>
                                </div>
                                <div>
                                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Descripción</label>
                                    <input type="text" name="description" value="{{ $brandClass->description }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                </div>
                                <div class="flex items-end justify-end">
                                    <button type="submit" class="text-sm font-semibold text-primary">Guardar</button>
                                </div>
                            </form>
                            <div class="flex items-center justify-end text-sm">
                                <form action="{{ route('admin.brand-classes.destroy', $brandClass) }}" method="POST" onsubmit="return confirm('¿Eliminar la clase {{ $brandClass->number }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">Aún no has registrado clases.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
