@php
    $admin = auth()->user();
    $adminSections = collect([
        [
            'title' => 'Resumen',
            'items' => [
                [
                    'label' => 'Panel principal',
                    'route' => route('admin.dashboard'),
                    'active' => request()->routeIs('admin.dashboard'),
                    'can' => 'view-admin-dashboard',
                ],
            ],
        ],
        [
            'title' => 'Administración',
            'items' => [
                [
                    'label' => 'Categorías',
                    'route' => route('admin.categories.index'),
                    'active' => request()->routeIs('admin.categories.*'),
                    'can' => 'manage-categories',
                ],
                [
                    'label' => 'Templates',
                    'route' => route('admin.templates.index'),
                    'active' => request()->routeIs('admin.templates.*'),
                    'can' => 'manage-categories',
                ],
                [
                    'label' => 'Países',
                    'route' => route('admin.countries.index'),
                    'active' => request()->routeIs('admin.countries.*'),
                    'can' => 'manage-users',
                ],
                [
                    'label' => 'Empresas',
                    'route' => route('admin.companies.index'),
                    'active' => request()->routeIs('admin.companies.*'),
                    'can' => 'manage-users',
                ],
                [
                    'label' => 'Departamentos',
                    'route' => route('admin.departments.index'),
                    'active' => request()->routeIs('admin.departments.*'),
                    'can' => 'manage-users',
                ],
            ],
        ],
        [
            'title' => 'Marcas',
            'items' => [
                [
                    'label' => 'Tipos',
                    'route' => route('admin.brand-types.index'),
                    'active' => request()->routeIs('admin.brand-types.*'),
                    'can' => 'manage-users',
                ],
                [
                    'label' => 'Países',
                    'route' => route('admin.brand-countries.index'),
                    'active' => request()->routeIs('admin.brand-countries.*'),
                    'can' => 'manage-users',
                ],
                [
                    'label' => 'Clases',
                    'route' => route('admin.brand-classes.index'),
                    'active' => request()->routeIs('admin.brand-classes.*'),
                    'can' => 'manage-users',
                ],
                [
                    'label' => 'Estados',
                    'route' => route('admin.brand-statuses.index'),
                    'active' => request()->routeIs('admin.brand-statuses.*'),
                    'can' => 'manage-users',
                ],
            ],
        ],
        [
            'title' => 'Configuración',
            'requires_role' => 'admin',
            'items' => [
                [
                    'label' => 'Usuarios',
                    'route' => route('admin.users.index'),
                    'active' => request()->routeIs('admin.users.*'),
                    'can' => 'manage-users',
                ],
                [
                    'label' => 'Roles',
                    'route' => route('admin.roles.index'),
                    'active' => request()->routeIs('admin.roles.*'),
                    'can' => 'manage-users',
                ],
                [
                    'label' => 'Settings',
                    'route' => null,
                    'active' => request()->routeIs('admin.settings.*'),
                    'can' => 'view-admin-dashboard',
                ],
            ],
        ],
    ])->map(function ($section) use ($admin) {
        if (! empty($section['requires_role']) && (! $admin || ! $admin->hasRole($section['requires_role']))) {
            return null;
        }

        $items = collect($section['items'] ?? [])->filter(function ($item) use ($admin) {
            return empty($item['can']) || ($admin && $admin->can($item['can']));
        })->values()->all();

        if (empty($items)) {
            return null;
        }

        return array_merge($section, ['items' => $items]);
    })->filter()->values()->all();
@endphp

<div class="px-4 py-8 lg:px-10">
    <div class="flex flex-col gap-6 lg:flex-row">
        <aside class="lg:w-72 shrink-0">
            <div class="bg-white rounded-2xl shadow-panel p-5">
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Backend</p>
                <p class="text-xl font-semibold text-primary-dark mt-2">Administración</p>
                <nav class="mt-6 space-y-6 text-sm">
                    @foreach ($adminSections as $section)
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.3em] text-slate-400">{{ $section['title'] }}</p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($section['items'] as $item)
                                    @php
                                        $isActive = $item['active'] ?? false;
                                        $baseClasses = 'flex items-center gap-2 px-4 py-2 rounded-xl font-medium transition';
                                        $activeClasses = $isActive
                                            ? 'bg-primary text-white shadow-sm'
                                            : 'text-slate-500 hover:bg-primary/5 hover:text-primary';
                                        $classes = $baseClasses . ' ' . $activeClasses;
                                    @endphp
                                    @if (!empty($item['route']))
                                        <li>
                                            <a href="{{ $item['route'] }}" class="{{ $classes }}">
                                                <span>{{ $item['label'] }}</span>
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <span class="{{ $classes }} cursor-not-allowed opacity-60">
                                                {{ $item['label'] }}
                                            </span>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </div>
        </aside>

        <section class="flex-1 space-y-6">
            @isset($header)
                <header class="bg-white rounded-2xl shadow-panel px-6 py-5">
                    {{ $header }}
                </header>
            @endisset

            <div class="space-y-6">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
