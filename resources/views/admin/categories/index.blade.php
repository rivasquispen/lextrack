<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Panel</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Categorías y subcategorías</h1>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10 space-y-6">
        @if (session('status'))
            <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-2xl px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <div class="bg-white rounded-2xl p-6 shadow-panel space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Categorías</h2>
                    <p class="text-sm text-slate-500">Gestiona los grupos principales de contratos.</p>
                </div>

                <form action="{{ route('admin.categories.store') }}" method="POST" class="flex gap-3">
                    @csrf
                    <input type="text" name="nombre" placeholder="Nombre de categoría" class="flex-1 rounded-xl border-slate-200" required>
                    <button class="btn-lex btn-lex-primary text-sm">Agregar</button>
                </form>

                <div class="divide-y divide-slate-100">
                    @foreach ($categories as $category)
                        <div class="py-3 flex items-center justify-between gap-3">
                            <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="flex-1 flex gap-2 items-center">
                                @csrf
                                @method('PUT')
                                <input type="text" name="nombre" value="{{ $category->nombre }}" class="flex-1 rounded-xl border-slate-200" required>
                                <button class="text-xs text-primary font-semibold">Guardar</button>
                            </form>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('¿Eliminar categoría?');">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-rose-600">Eliminar</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-panel space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Subcategorías</h2>
                    <p class="text-sm text-slate-500">Relacionadas a cada categoría.</p>
                </div>

                <form action="{{ route('admin.subcategories.store') }}" method="POST" class="grid gap-3 md:grid-cols-2">
                    @csrf
                    <input type="text" name="nombre" placeholder="Nombre de subcategoría" class="rounded-xl border-slate-200 md:col-span-1" required>
                    <select name="category_id" class="rounded-xl border-slate-200 md:col-span-1" required>
                        <option value="">Selecciona categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->nombre }}</option>
                        @endforeach
                    </select>
                    <div class="md:col-span-2 flex justify-end">
                        <button class="btn-lex btn-lex-primary text-sm">Agregar</button>
                    </div>
                </form>

                <div class="space-y-3">
                    @foreach ($categories as $category)
                        <div class="border border-slate-100 rounded-2xl">
                            <div class="px-4 py-2 bg-slate-50 rounded-t-2xl text-sm font-semibold text-slate-600">{{ $category->nombre }}</div>
                            <div class="divide-y divide-slate-100">
                                @forelse ($category->subcategories as $subcategory)
                                    <div class="px-4 py-3 flex items-center justify-between gap-3">
                                        <form action="{{ route('admin.subcategories.update', $subcategory) }}" method="POST" class="flex items-center gap-2 flex-1">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="nombre" value="{{ $subcategory->nombre }}" class="flex-1 rounded-xl border-slate-200" required>
                                            <input type="hidden" name="category_id" value="{{ $category->id }}">
                                            <button class="text-xs text-primary font-semibold">Guardar</button>
                                        </form>
                                        <form action="{{ route('admin.subcategories.destroy', $subcategory) }}" method="POST" onsubmit="return confirm('¿Eliminar subcategoría?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs text-rose-600">Eliminar</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="px-4 py-3 text-sm text-slate-500">Sin subcategorías.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
