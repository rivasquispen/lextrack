<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Marcas</p>
                <h1 class="text-2xl font-semibold text-primary-dark">Seguimiento de solicitudes</h1>
            </div>
            <a href="{{ route('brands.create') }}" class="inline-flex items-center gap-2 btn-lex btn-lex-primary text-sm">
                <span class="text-sm">+</span>
                Agregar nueva solicitud
            </a>
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

        <div class="flex flex-col gap-6 lg:flex-row">
            <aside class="lg:w-72 space-y-4">
                <div class="bg-white rounded-2xl shadow-panel p-5">
                    <p class="text-sm text-slate-500">Total de marcas</p>
                    <p class="text-3xl font-semibold text-primary-dark">{{ number_format($brands->total()) }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-panel p-5">
                    <h2 class="text-sm font-semibold text-primary-dark">Estados</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($statusSummary as $summary)
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                                <span class="text-sm text-slate-600">{{ $summary['label'] }}</span>
                                <span class="text-sm font-semibold text-primary">{{ number_format($summary['count']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Aún no hay estados configurados.</p>
                        @endforelse
                    </div>
                </div>
            </aside>

            <section class="flex-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-panel p-4 space-y-4">
                    <form method="GET" class="grid gap-3 lg:grid-cols-3">
                        <div>
                            <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Estado</label>
                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200">
                                <option value="">Todos</option>
                                @foreach ($statusOptions as $slug => $label)
                                    <option value="{{ $slug }}" @selected(($filters['status'] ?? '') === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Tipo</label>
                            <select name="brand_type_id" class="mt-1 w-full rounded-xl border-slate-200">
                                <option value="">Todos</option>
                                @foreach ($brandTypes as $type)
                                    <option value="{{ $type->id }}" @selected(($filters['brand_type_id'] ?? '') == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs uppercase tracking-[0.2em] text-slate-400">País</label>
                            <select name="brand_country_id" class="mt-1 w-full rounded-xl border-slate-200">
                                <option value="">Todos</option>
                                @foreach ($brandCountries as $country)
                                    <option value="{{ $country->id }}" @selected(($filters['brand_country_id'] ?? '') == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <!--<label class="text-xs uppercase tracking-[0.2em] text-slate-400">Buscar</label>-->
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Nombre, titular o certificado">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="btn-lex btn-lex-primary flex-1">Filtrar</button>
                            <a href="{{ route('brands.index') }}" class="btn-lex flex-1 text-center">Limpiar</a>
                        </div>
                    </form>
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                        <p>Mostrando {{ $brands->firstItem() ?? 0 }}-{{ $brands->lastItem() ?? 0 }} de {{ $brands->total() }} marcas</p>
                        <a href="{{ route('brands.export', request()->query()) }}" class="inline-flex items-center gap-2 text-primary font-semibold">
                            <span class="text-lg">⇩</span> Descargar Excel
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-panel overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-semibold text-primary-dark">Listado de marcas</h2>
                    <p class="text-sm text-slate-500">Historial completo de solicitudes y renovaciones.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500 uppercase text-xs tracking-[0.2em]">
                        <tr>
                            <th class="px-6 py-3">Marca</th>
                            <th class="px-6 py-3">Registro</th>
                            <th class="px-6 py-3">Clases</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Vigencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($brands as $brand)
                            @php
                                $classes = $brand->classes?->pluck('number')->filter()->values() ?? collect();
                                $statusColor = $brand->status_color;
                            @endphp
                            <tr class="hover:bg-primary/5">
                                <td class="px-6 py-4 align-top">
                                    <a href="{{ route('brands.show', $brand) }}" class="text-base font-semibold text-primary-dark hover:underline">{{ $brand->display_name }}</a>
                                    <p class="text-sm text-slate-500">Titular: {{ $brand->display_holder }}</p>
                                    <p class="text-sm text-slate-500">Tipo: {{ $brand->display_type }}</p>
                                    <p class="text-xs text-slate-400 mt-1">Creado por: {{ $brand->creator->nombre ?? $brand->creator->email ?? 'Sin asignar' }}</p>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <p class="font-semibold text-slate-700">{{ $brand->display_registration_number }}</p>
                                    <p class="text-sm text-slate-500">{{ $brand->display_country }}</p>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    @if ($classes->isNotEmpty())
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($classes as $classNumber)
                                                <span class="px-2 py-1 text-xs bg-slate-100 rounded-xl text-slate-600">Clase {{ $classNumber }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-sm text-slate-400">Sin clases asociadas</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700">
                                        {{ $brand->display_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-slate-600">
                                    <p>
                                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Registro</span>
                                        <br>
                                        {{ $brand->display_registration_date ?? 'Sin fecha' }}
                                    </p>
                                    <p class="mt-2">
                                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Vencimiento</span>
                                        <br>
                                        {{ $brand->display_expiration_date ?? 'Sin fecha' }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    Aún no hay solicitudes registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
                </div>
            </section>
        </div>

    @if ($brands->hasPages())
        <div class="px-4 py-4 lg:px-10">
            {{ $brands->links() }}
        </div>
    @endif
</x-app-layout>
