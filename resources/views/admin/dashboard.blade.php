<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Panel</p>
                <h1 class="text-2xl font-semibold text-primary-dark">Resumen administrativo</h1>
            </div>
            @can('manage-users')
                <a href="{{ route('admin.users.index') }}" class="btn-lex btn-lex-primary text-sm">Gestionar usuarios</a>
            @endcan
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10 space-y-6">
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white rounded-2xl p-6 shadow-panel">
                <p class="text-sm text-slate-500">Contratos totales</p>
                <p class="text-3xl font-semibold text-primary-dark">{{ number_format($totalContracts) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-panel">
                <p class="text-sm text-slate-500">Marcas activas</p>
                <p class="text-3xl font-semibold text-primary-dark">{{ number_format($activeBrands) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-panel">
                <p class="text-sm text-slate-500">Usuarios activas</p>
                <p class="text-3xl font-semibold text-primary-dark">0</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-panel">
                <p class="text-sm text-slate-500">Usuarios activas</p>
                <p class="text-3xl font-semibold text-primary-dark">0</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-panel md:col-span-4">
                <p class="text-sm text-slate-500 mb-3">Contratos por estado</p>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach ($statusCounts as $status => $count)
                        <div class="rounded-xl border border-slate-100 p-3 text-center">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $status }}</p>
                            <p class="text-xl font-semibold text-primary">{{ $count }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-panel">
            <h2 class="text-lg font-semibold text-primary-dark mb-4">Top 5 creadores</h2>
            <div class="space-y-3">
                @forelse ($topCreators as $creator)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 p-4">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $creator->nombre ?? $creator->name ?? 'Sin nombre' }}</p>
                            <p class="text-sm text-slate-500">{{ $creator->email }}</p>
                        </div>
                        <span class="text-sm font-semibold text-primary">{{ $creator->created_contracts_count }} contratos</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aún no hay contratos registrados.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
