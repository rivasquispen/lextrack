<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Administración</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Editar template</h1>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10">
        <div class="bg-white rounded-2xl shadow-panel p-6">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary-dark">Actualizar plantilla</h2>
                <p class="text-sm text-slate-500">Modifica los datos del template seleccionado.</p>
            </header>

            @include('admin.templates._form', [
                'action' => route('admin.templates.update', $template),
                'method' => 'PUT',
                'submitLabel' => 'Actualizar template',
                'template' => $template,
            ])
        </div>
    </div>
</x-app-layout>
