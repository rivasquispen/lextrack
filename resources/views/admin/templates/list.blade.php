<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Administración</p>
                <h1 class="text-2xl font-semibold text-primary-dark">Templates</h1>
            </div>
            <a href="{{ route('admin.templates.create') }}" class="btn-lex btn-lex-primary text-sm">Nuevo template</a>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10">
        @if (session('status'))
            <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-2xl px-4 py-3 mb-4">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Categoría</th>
                            <th class="px-4 py-3">Actualizado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($templates as $template)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-800">{{ $template->nombre }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $template->subcategory->nombre ?? '—' }}</td>
                                <td class="px-4 py-4 text-slate-500 text-xs">{{ optional($template->updated_at)->diffForHumans() }}</td>
                                <td class="px-4 py-4 text-right space-x-2">
                                    <a href="{{ route('admin.templates.edit', $template) }}" class="text-sm text-primary font-semibold">Editar</a>
                                    <form action="{{ route('admin.templates.destroy', $template) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar template?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm text-rose-600">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Aún no hay templates registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $templates->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
