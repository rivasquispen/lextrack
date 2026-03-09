<x-app-layout>
    @php
        $statusColors = [
            'creado' => 'bg-slate-100 text-slate-600',
            'asignado' => 'bg-blue-100 text-blue-700',
            'en_aprobacion' => 'bg-amber-100 text-amber-700',
            'aprobado' => 'bg-emerald-100 text-emerald-700',
            'firmado' => 'bg-teal-100 text-teal-700',
            'observado' => 'bg-red-100 text-red-700',
        ];

        $roleLabels = [
            'creador' => 'Creador',
            'abogado' => 'Responsable legal',
            'aprobador' => 'Aprobador',
            'firmante' => 'Firmante',
            'asesor' => 'Asesor',
            'observador' => 'Supervisión legal',
        ];

        $roleColors = [
            'creador' => 'bg-primary/10 text-primary',
            'abogado' => 'bg-blue-100 text-blue-700',
            'aprobador' => 'bg-amber-100 text-amber-700',
            'firmante' => 'bg-emerald-100 text-emerald-700',
            'asesor' => 'bg-purple-100 text-purple-700',
            'observador' => 'bg-slate-100 text-slate-600',
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">CONTRATOS</p>
                <h1 class="text-2xl font-semibold text-primary-dark">Gestión de Contratos</h1>
            </div>
            <div x-data="{ open:false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="btn-lex btn-lex-primary text-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 4v12m-6-6h12" />
                    </svg>
                    Nuevo contrato
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                    </svg>
                </button>
                <div x-cloak x-show="open" x-transition class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-panel border border-slate-100 overflow-hidden">
                    <a href="{{ route('contracts.create-zero') }}" class="block px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-semibold text-primary-dark">Cargar documento externo</p>
                        <p class="text-xs text-slate-500">Inicia el proceso desde un borrador o archivo enviado.</p>
                    </a>
                    <a href="{{ route('contracts.create-template') }}" class="block px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-semibold text-primary-dark">Utilizar plantilla institucional</p>
                        <p class="text-xs text-slate-500">Genera contratos estandarizados con modelos aprobados.</p>
                    </a>
                </div>
            </div>
        </div>
    </x-slot>


    <div class="px-4 py-8 lg:px-10 space-y-6">
        <!--
        <section class="bg-white rounded-2xl px-6 py-4 flex flex-wrap items-center gap-4 shadow-panel">
            <div>
                <h1 class="text-2xl font-semibold text-primary-dark">Panel principal</h1>
                <p class="text-sm text-slate-500">Administra contratos según tu rol</p>
            </div>
            <div class="flex items-center gap-3 ms-auto w-full md:w-auto">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full md:w-64">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <input type="text" placeholder="Buscar contratos..." class="bg-transparent border-0 focus:ring-0 text-sm placeholder:text-slate-400 flex-1" data-contract-search>
                </div>
                <a href="{{ route('flows.create') }}" class="btn-lex btn-lex-primary text-sm">
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 4v12m-6-6h12" />
                    </svg>
                    Nuevo flujo
                </a>
            </div>
        </section>
        -->

        <section class="grid gap-6 lg:grid-cols-12">
            <aside class="lg:col-span-3 bg-primary text-white rounded-2xl p-6 space-y-6 shadow-panel">
                <div>
                    <h2 class="text-2xl font-semibold">Filtro rápido</h2>
                </div>
                <div class="space-y-5 text-sm">
                    <div>
                        <p class="text-white/70 text-xs uppercase tracking-[0.3em] mb-2">por estado</p>
                        <div class="space-y-2">
                            @foreach ($statusLabels as $value => $label)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-white/40 bg-transparent text-accent focus:ring-accent" value="{{ $value }}" data-status-filter checked>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-white/70 text-xs uppercase tracking-[0.3em] mb-2">categorías</p>
                        <div class="space-y-2 pr-1">
                            @forelse ($categories as $category)
                                <label class="flex items-center justify-between text-white/90 gap-3">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" class="rounded border-white/40 bg-transparent text-accent focus:ring-accent" value="{{ $category->id }}" data-category-filter checked>
                                        <span>{{ $category->nombre }}</span>
                                    </span>
                                    <span class="text-xs bg-white/20 rounded-full px-2 py-0.5">{{ $categoryCounts[$category->id] ?? 0 }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-white/70">No hay categorías configuradas.</p>
                            @endforelse

                            @if (($hasUncategorized ?? false) && ($contractRecords->isNotEmpty()))
                                <label class="flex items-center justify-between text-white/90 gap-3">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" class="rounded border-white/40 bg-transparent text-accent focus:ring-accent" value="__none__" data-category-filter checked>
                                        <span>Sin categoría</span>
                                    </span>
                                    <span class="text-xs bg-white/20 rounded-full px-2 py-0.5">{{ $categoryCounts['__none__'] ?? 0 }}</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </aside>

            <div class="lg:col-span-9 space-y-4">
                <div class="bg-white rounded-2xl p-4 shadow-panel">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <!--<p class="text-xs uppercase tracking-[0.3em] text-slate-400">Roles</p>-->
                        <div class="flex flex-wrap gap-2" data-role-filter-group>
                            @foreach ($roleFilters as $filter => $label)
                                <button type="button" class="px-3 py-1.5 rounded-full border text-sm transition @if ($loop->first) bg-primary text-white border-primary @else text-slate-600 border-slate-200 bg-white hover:text-primary hover:border-primary @endif" data-role-filter="{{ $filter }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full md:w-64">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <input type="text" placeholder="Buscar contratos..." class="bg-transparent border-0 focus:ring-0 text-sm placeholder:text-slate-400 flex-1" data-contract-search>
                </div>

                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-panel">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Contrato</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                                    <!--<th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Asesor</th>-->
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Última actualización</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Tus roles</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100" data-contract-body>
                                @forelse ($contractRecords as $record)
                                    @php
                                        $contract = $record['contract'];
                                        $roles = $record['roles'];
                                    @endphp
                                    <tr data-contract-row data-status="{{ $contract->estado }}" data-category="{{ $contract->categoria_id ?? '__none__' }}" data-roles="{{ implode(',', $roles) }}" data-contract-link="{{ route('contracts.show', $contract) }}">
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-slate-800">
                                                <a href="{{ route('contracts.show', $contract) }}" class="hover:text-primary">{{ $contract->codigo }}</a>
                                            </div>
                                            <div class="text-sm text-slate-600">{{ $contract->titulo }}</div>
                                            <div class="text-xs text-slate-400 mt-1">{{ $contract->category->nombre ?? 'Sin categoría' }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$contract->estado] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $contract->status_label }}
                                            </span>
                                        </td>
                                        <!--
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $contract->advisor->nombre ?? $contract->advisor->email ?? 'Por asignar' }}
                                        </td>-->
                                        <td class="px-6 py-4 text-sm text-slate-500">
                                            {{ $contract->updated_at?->diffForHumans() ?? 'Sin registro' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($roles as $role)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $roleColors[$role] ?? 'bg-slate-100 text-slate-600' }}">
                                                        {{ $roleLabels[$role] ?? ucfirst($role) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">
                                            No tienes contratos vinculados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex flex-col gap-3 md:flex-row md:items-center md:justify-between text-sm text-slate-600 hidden" data-pagination-container>
                        <div data-pagination-summary></div>
                        <div class="flex items-center flex-wrap gap-2" data-pagination-buttons></div>
                    </div>
                    <div class="hidden px-6 py-6 text-center text-sm text-slate-500" data-empty-state>
                        No hay contratos que coincidan con los filtros seleccionados.
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = Array.from(document.querySelectorAll('[data-contract-row]'));
            const searchInput = document.querySelector('[data-contract-search]');
            const roleButtons = Array.from(document.querySelectorAll('[data-role-filter]'));
            const statusCheckboxes = Array.from(document.querySelectorAll('[data-status-filter]'));
            const categoryCheckboxes = Array.from(document.querySelectorAll('[data-category-filter]'));
            const emptyState = document.querySelector('[data-empty-state]');
            const paginationContainer = document.querySelector('[data-pagination-container]');
            const paginationSummary = document.querySelector('[data-pagination-summary]');
            const paginationButtons = document.querySelector('[data-pagination-buttons]');
            const perPage = 10;

            if (!rows.length) {
                return;
            }

            let activeRole = 'all';
            let searchQuery = '';
            let currentPage = 1;
            const selectedStatuses = new Set(statusCheckboxes.map((checkbox) => checkbox.value));
            const selectedCategories = new Set(categoryCheckboxes.map((checkbox) => checkbox.value));
            const hasStatusFilters = statusCheckboxes.length > 0;
            const hasCategoryFilters = categoryCheckboxes.length > 0;

            rows.forEach((row) => {
                row.addEventListener('click', (event) => {
                    if (event.target.closest('a') || event.target.closest('button') || event.target.closest('[data-row-action]')) {
                        return;
                    }
                    const link = row.dataset.contractLink;
                    if (link) {
                        window.location.href = link;
                    }
                });
            });

            const updateRoleButtons = () => {
                roleButtons.forEach((button) => {
                    const isActive = button.dataset.roleFilter === activeRole;
                    button.classList.toggle('bg-primary', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('border-primary', isActive);
                    button.classList.toggle('text-slate-600', !isActive);
                    button.classList.toggle('border-slate-200', !isActive);
                    button.classList.toggle('bg-white', !isActive);
                });
            };

            const renderPagination = (totalItems, totalPages) => {
                if (!paginationContainer || !paginationButtons || !paginationSummary) {
                    return;
                }

                if (!totalItems || totalItems <= perPage) {
                    paginationContainer.classList.add('hidden');
                    paginationButtons.innerHTML = '';
                    paginationSummary.textContent = '';
                    return;
                }

                paginationContainer.classList.remove('hidden');

                const start = (currentPage - 1) * perPage + 1;
                const end = Math.min(totalItems, currentPage * perPage);
                paginationSummary.textContent = `Mostrando ${start}-${end} de ${totalItems} contratos`;

                paginationButtons.innerHTML = '';

                const createButton = (label, targetPage, disabled) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = label;
                    button.disabled = disabled;
                    button.className = `px-3 py-1 rounded-lg border text-sm transition ${
                        disabled
                            ? 'text-slate-400 border-slate-200 cursor-not-allowed'
                            : 'text-primary border-primary/30 hover:border-primary hover:text-primary-dark'
                    }`;
                    button.addEventListener('click', () => {
                        if (disabled || targetPage === currentPage) {
                            return;
                        }
                        currentPage = targetPage;
                        applyFilters();
                    });
                    return button;
                };

                paginationButtons.appendChild(createButton('Anterior', currentPage - 1, currentPage === 1));

                const pageIndicator = document.createElement('span');
                pageIndicator.className = 'text-slate-500 text-sm';
                pageIndicator.textContent = `Página ${currentPage} de ${totalPages}`;
                paginationButtons.appendChild(pageIndicator);

                paginationButtons.appendChild(createButton('Siguiente', currentPage + 1, currentPage === totalPages));
            };

            const applyFilters = () => {
                const matchingRows = [];

                rows.forEach((row) => {
                    const rowStatus = row.dataset.status;
                    const rowCategory = row.dataset.category ?? '';
                    const rowRoles = row.dataset.roles ? row.dataset.roles.split(',') : [];
                    const rowText = row.dataset.search ?? row.textContent.toLowerCase();

                    if (!row.dataset.search) {
                        row.dataset.search = rowText;
                    }

                    const matchesRole = activeRole === 'all' || rowRoles.includes(activeRole);
                    const matchesStatus = hasStatusFilters
                        ? (selectedStatuses.size === 0 ? false : selectedStatuses.has(rowStatus))
                        : true;
                    const matchesCategory = hasCategoryFilters
                        ? (selectedCategories.size === 0 ? false : selectedCategories.has(rowCategory))
                        : true;
                    const matchesSearch = !searchQuery || row.dataset.search.includes(searchQuery);

                    if (matchesRole && matchesStatus && matchesCategory && matchesSearch) {
                        matchingRows.push(row);
                    } else {
                        row.classList.add('hidden');
                    }
                });

                const totalItems = matchingRows.length;
                const totalPages = Math.max(1, Math.ceil(totalItems / perPage));

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                const startIndex = (currentPage - 1) * perPage;
                const endIndex = startIndex + perPage;

                matchingRows.forEach((row, index) => {
                    const shouldDisplay = index >= startIndex && index < endIndex;
                    row.classList.toggle('hidden', !shouldDisplay);
                });

                if (emptyState) {
                    emptyState.classList.toggle('hidden', totalItems !== 0);
                }

                renderPagination(totalItems, totalPages);
            };

            statusCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        selectedStatuses.add(checkbox.value);
                    } else {
                        selectedStatuses.delete(checkbox.value);
                    }
                    currentPage = 1;
                    applyFilters();
                });
            });

            categoryCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        selectedCategories.add(checkbox.value);
                    } else {
                        selectedCategories.delete(checkbox.value);
                    }
                    currentPage = 1;
                    applyFilters();
                });
            });

            roleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    activeRole = button.dataset.roleFilter;
                    updateRoleButtons();
                    currentPage = 1;
                    applyFilters();
                });
            });

            searchInput?.addEventListener('input', (event) => {
                searchQuery = event.target.value.toLowerCase().trim();
                currentPage = 1;
                applyFilters();
            });

            updateRoleButtons();
            applyFilters();
        });
    </script>
</x-app-layout>
