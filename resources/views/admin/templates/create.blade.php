<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Administración</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Nuevo template</h1>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10">
        <div class="bg-white rounded-2xl shadow-panel p-6">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary-dark">Registrar plantilla</h2>
                <p class="text-sm text-slate-500">Completa los campos para crear una nueva plantilla vinculada a su subcategoría.</p>
            </header>

            @include('admin.templates._form', [
                'action' => route('admin.templates.store'),
                'method' => 'POST',
                'submitLabel' => 'Crear template',
            ])
        </div>
    </div>
</x-app-layout>
