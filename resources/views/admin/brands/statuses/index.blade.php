<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Marcas</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Estados de solicitud</h1>
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
                    <h2 class="text-lg font-semibold text-primary-dark">Agregar estado</h2>
                    <p class="text-sm text-slate-500">Define las etapas disponibles para las solicitudes de marca.</p>
                </div>
                <form action="{{ route('admin.brand-statuses.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Nombre</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="en_tramite" required>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Color (Tailwind)</label>
                            <input type="text" name="color" value="{{ old('color') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="sky">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Orden</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="mt-1 w-full rounded-xl border-slate-200" min="0" max="9999">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Descripción</label>
                        <textarea name="description" class="mt-1 w-full rounded-xl border-slate-200" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_default" value="1" class="rounded" @checked(old('is_default'))>
                        Marcar como estado por defecto
                    </label>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-lex btn-lex-primary">Registrar estado</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-dark">Listado</h2>
                        <p class="text-sm text-slate-500">{{ $statuses->count() }} estados configurados.</p>
                    </div>
                </div>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($statuses as $status)
                        <div class="py-4 space-y-3">
                            <form action="{{ route('admin.brand-statuses.update', $status) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-3 md:grid-cols-[2fr,1fr]">
                                    <div>
                                        <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Nombre</label>
                                        <input type="text" name="name" value="{{ $status->name }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                    </div>
                                    <div>
                                        <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Slug</label>
                                        <input type="text" name="slug" value="{{ $status->slug }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                                    </div>
                                </div>
                                <div class="grid gap-3 md:grid-cols-3">
                                    <div>
                                        <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Color</label>
                                        <input type="text" name="color" value="{{ $status->color }}" class="mt-1 w-full rounded-xl border-slate-200">
                                    </div>
                                    <div>
                                        <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Orden</label>
                                        <input type="number" name="sort_order" value="{{ $status->sort_order }}" class="mt-1 w-full rounded-xl border-slate-200">
                                    </div>
                                    <div>
                                        <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Por defecto</label>
                                        <select name="is_default" class="mt-1 w-full rounded-xl border-slate-200">
                                            <option value="0">No</option>
                                            <option value="1" @selected($status->is_default)>Sí</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Descripción</label>
                                    <textarea name="description" rows="2" class="mt-1 w-full rounded-xl border-slate-200">{{ $status->description }}</textarea>
                                </div>
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <p>{{ $status->brands_count }} marcas asociadas</p>
                                    <button type="submit" class="font-semibold text-primary">Guardar</button>
                                </div>
                            </form>
                            <div class="flex justify-end">
                                <form action="{{ route('admin.brand-statuses.destroy', $status) }}" method="POST" onsubmit="return confirm('¿Eliminar el estado {{ $status->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-rose-600" @disabled($status->brands_count > 0 || $status->is_default)>Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-slate-500">Aún no has registrado estados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
