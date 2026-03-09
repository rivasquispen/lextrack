<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Marcas</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Tipos de marca</h1>
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
                    <h2 class="text-lg font-semibold text-primary-dark">Nuevo tipo</h2>
                    <p class="text-sm text-slate-500">Clasifica tus marcas por tipo (denominativa, mixta, figurativa, etc.).</p>
                </div>
                <form action="{{ route('admin.brand-types.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Nombre</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Ej: Denominativa" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-lex btn-lex-primary">Registrar tipo</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-dark">Listado</h2>
                        <p class="text-sm text-slate-500">{{ $brandTypes->count() }} tipos configurados.</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 mt-4">
                    @forelse ($brandTypes as $brandType)
                        <div class="py-3 space-y-3">
                            <form action="{{ route('admin.brand-types.update', $brandType) }}" method="POST" class="flex items-center gap-3">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $brandType->name }}" class="flex-1 rounded-xl border-slate-200" required>
                                <button type="submit" class="text-sm font-semibold text-primary">Guardar</button>
                            </form>
                            <div class="flex items-center justify-end text-sm">
                                <form action="{{ route('admin.brand-types.destroy', $brandType) }}" method="POST" onsubmit="return confirm('¿Eliminar el tipo {{ $brandType->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">Aún no se han registrado tipos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
