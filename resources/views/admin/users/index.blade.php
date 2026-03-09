<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Panel</p>
            <h1 class="text-2xl font-semibold text-primary-dark">Gestión de usuarios</h1>
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

        <div class="flex flex-wrap items-center justify-between gap-3">
            <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Buscar por nombre o correo" class="rounded-xl border-slate-200 px-3 py-2 text-sm">
                <button class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-primary">Buscar</button>
                @if (! empty($search))
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500">Limpiar</a>
                @endif
            </form>
            <div class="flex items-center gap-3">
                @if (! empty($search))
                    <p class="text-sm text-slate-500">Mostrando resultados para <span class="font-semibold">“{{ $search }}”</span></p>
                @endif
                <button type="button" class="btn-lex btn-lex-primary text-sm" data-modal-open="create-user">
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 4v12m-6-6h12" />
                    </svg>
                    Agregar usuario
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Empresa y países</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acciones</th>
                            <th class="px-4 py-3">Última sesión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($users as $user)
                            @php
                                $userCountryIds = $user->countries->pluck('id')->map(fn ($id) => (string) $id)->toArray();
                                $primaryCountryId = optional($user->country)->id;
                                $userRoleNames = $user->roles->pluck('name');
                            @endphp
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-800">{{ $user->nombre ?? $user->name ?? 'Sin nombre' }}</p>
                                    <p class="text-slate-500 text-sm">{{ $user->email }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ $user->cargo ?? 'Sin cargo' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-700">{{ $user->company->nombre ?? 'Sin empresa asignada' }}</p>
                                    <p class="text-xs text-slate-500">Departamento: {{ $user->department->nombre ?? 'No definido' }}</p>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @forelse ($user->countries as $country)
                                            <span class="px-2 py-0.5 text-xs rounded-full border {{ $primaryCountryId === $country->id ? 'border-primary text-primary bg-primary/5' : 'border-slate-200 text-slate-500' }}">
                                                {{ $country->nombre }}
                                                @if ($primaryCountryId === $country->id)
                                                    <span class="ml-1 text-[10px] uppercase tracking-[0.2em]">Principal</span>
                                                @endif
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400">Sin países asignados</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($userRoleNames as $roleName)
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-primary/10 text-primary capitalize">{{ $roleName }}</span>
                                        @empty
                                            <span class="text-xs text-slate-400">Sin roles</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <form action="{{ route('admin.users.update-status', $user) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="active" value="{{ $user->active ? 0 : 1 }}">
                                        <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl {{ $user->active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                            {{ $user->active ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-4 space-y-2">
                                    <button type="button"
                                        class="w-full px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 text-white"
                                        data-organization-open
                                        data-action="{{ route('admin.users.update-organization', $user) }}"
                                        data-user="{{ $user->nombre ?? $user->email }}"
                                        data-company="{{ $user->empresa_id ?? '' }}"
                                        data-countries='@json($userCountryIds)'
                                        data-primary-country="{{ $primaryCountryId ?? '' }}"
                                        data-department="{{ $user->department_id ?? '' }}">
                                        Editar organización
                                    </button>
                                    <button type="button"
                                        class="w-full px-3 py-1.5 text-xs font-semibold rounded-xl border border-primary text-primary"
                                        data-roles-open
                                        data-action="{{ route('admin.users.update-role', $user) }}"
                                        data-user="{{ $user->nombre ?? $user->email }}"
                                        data-roles='@json($userRoleNames)'>
                                        Editar roles
                                    </button>
                                </td>
                                <td class="px-4 py-4 text-slate-500">
                                    {{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">No se encontraron usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        </div>
    </div>
    <div class="fixed inset-0 z-40 hidden" data-modal="create-user">
        <div class="absolute inset-0 bg-slate-900/50" data-modal-close></div>
        <div class="relative z-10 mx-auto mt-10 w-full max-w-3xl px-4" role="dialog" aria-modal="true">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Usuarios</p>
                        <h3 class="text-lg font-semibold text-primary-dark">Agregar usuario manualmente</h3>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close>
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 8.586L4.293 2.879A1 1 0 102.879 4.293L8.586 10l-5.707 5.707a1 1 0 101.414 1.414L10 11.414l5.707 5.707a1 1 0 001.414-1.414L11.414 10l5.707-5.707a1 1 0 00-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <form action="{{ route('admin.users.store') }}" method="POST" class="px-6 py-6 space-y-5">
                    @csrf
                    <input type="hidden" name="form_context" value="create_user">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Nombre completo</label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}" class="mt-1 w-full rounded-xl border-slate-200" required data-initial-focus>
                            @error('nombre')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Correo corporativo</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                            @error('email')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Cargo</label>
                            <input type="text" name="cargo" value="{{ old('cargo') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Opcional">
                            @error('cargo')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Estado de acceso</label>
                            <div class="flex items-center gap-3 mt-2">
                                <input type="hidden" name="active" value="0">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" name="active" value="1" class="rounded border-slate-300 text-primary focus:ring-primary" @checked(old('active', '1') === '1')>
                                    Activar inmediatamente
                                </label>
                            </div>
                            @error('active')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Empresa</label>
                            <select name="empresa_id" class="mt-1 w-full rounded-xl border-slate-200">
                                <option value="">Sin asignar</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected(old('empresa_id') == $company->id)>{{ $company->nombre }}</option>
                                @endforeach
                            </select>
                            @error('empresa_id')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Departamento</label>
                            <select name="department_id" class="mt-1 w-full rounded-xl border-slate-200">
                                <option value="">Sin asignar</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                        {{ $department->nombre }} — {{ $department->company->nombre ?? 'Sin empresa' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Países</label>
                            <select name="country_ids[]" multiple class="mt-1 w-full rounded-xl border-slate-200" size="3">
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected(collect(old('country_ids', []))->contains($country->id))>{{ $country->nombre }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Selecciona uno o varios países.</p>
                            @error('country_ids')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                            @error('country_ids.*')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">País principal</label>
                            <select name="primary_country_id" class="mt-1 w-full rounded-xl border-slate-200">
                                <option value="">Sin definir</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected(old('primary_country_id') == $country->id)>{{ $country->nombre }}</option>
                                @endforeach
                            </select>
                            @error('primary_country_id')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-600 mb-2">Roles asignados</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($roles as $roleName)
                                <label class="inline-flex items-center gap-2 text-xs font-medium {{ collect(old('roles', []))->contains($roleName) ? 'text-primary' : 'text-slate-500' }}">
                                    <input type="checkbox" name="roles[]" value="{{ $roleName }}" class="rounded border-slate-300" @checked(collect(old('roles', []))->contains($roleName))>
                                    {{ ucfirst($roleName) }}
                                </label>
                            @endforeach
                        </div>
                        @error('roles')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                        @error('roles.*')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" class="text-sm text-slate-500 hover:text-primary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn-lex btn-lex-primary">Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-40 hidden" data-modal="organization">
        <div class="absolute inset-0 bg-slate-900/50" data-modal-close></div>
        <div class="relative z-10 mx-auto mt-10 w-full max-w-2xl px-4" role="dialog" aria-modal="true">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Usuarios</p>
                        <h3 class="text-lg font-semibold text-primary-dark">Editar organización</h3>
                        <p class="text-sm text-slate-500" data-organization-user>—</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close>
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 8.586L4.293 2.879A1 1 0 102.879 4.293L8.586 10l-5.707 5.707a1 1 0 101.414 1.414L10 11.414l5.707 5.707a1 1 0 001.414-1.414L11.414 10l5.707-5.707a1 1 0 00-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <form method="POST" class="px-6 py-6 space-y-4" data-organization-form>
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Empresa</label>
                        <select name="empresa_id" class="mt-1 w-full rounded-xl border-slate-200">
                            <option value="">Sin asignar</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Departamento</label>
                        <select name="department_id" class="mt-1 w-full rounded-xl border-slate-200">
                            <option value="">Sin asignar</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">
                                    {{ $department->nombre }} — {{ $department->company->nombre ?? 'Sin empresa' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Países asignados</label>
                        <select name="country_ids[]" multiple class="mt-1 w-full rounded-xl border-slate-200" size="4">
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->nombre }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Usa CTRL/CMD para elegir varios países.</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">País principal</label>
                        <select name="primary_country_id" class="mt-1 w-full rounded-xl border-slate-200">
                            <option value="">Sin definir</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" class="text-sm text-slate-500 hover:text-primary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn-lex btn-lex-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-40 hidden" data-modal="roles">
        <div class="absolute inset-0 bg-slate-900/50" data-modal-close></div>
        <div class="relative z-10 mx-auto mt-10 w-full max-w-2xl px-4" role="dialog" aria-modal="true">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Usuarios</p>
                        <h3 class="text-lg font-semibold text-primary-dark">Editar roles</h3>
                        <p class="text-sm text-slate-500" data-roles-user>—</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close>
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 8.586L4.293 2.879A1 1 0 102.879 4.293L8.586 10l-5.707 5.707a1 1 0 101.414 1.414L10 11.414l5.707 5.707a1 1 0 001.414-1.414L11.414 10l5.707-5.707a1 1 0 00-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <form method="POST" class="px-6 py-6 space-y-4" data-roles-form>
                    @csrf
                    @method('PATCH')
                    <div class="flex flex-wrap gap-3">
                        @foreach ($roles as $roleName)
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                                <input type="checkbox" name="roles[]" value="{{ $roleName }}" class="rounded border-slate-300">
                                {{ ucfirst($roleName) }}
                            </label>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" class="text-sm text-slate-500 hover:text-primary" data-modal-close>Cancelar</button>
                        <button type="submit" class="btn-lex btn-lex-primary">Guardar roles</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const getOpenModals = () => Array.from(document.querySelectorAll('[data-modal]')).filter(modal => !modal.classList.contains('hidden'));
        const updateBodyScroll = () => {
            document.documentElement.classList.toggle('overflow-hidden', getOpenModals().length > 0);
        };

        const openModal = (modal) => {
            if (!modal) {
                return;
            }
            modal.classList.remove('hidden');
            updateBodyScroll();
            modal.querySelector('[data-initial-focus]')?.focus();
        };

        const closeModal = (modal) => {
            if (!modal) {
                return;
            }
            modal.classList.add('hidden');
            updateBodyScroll();
        };

        document.querySelectorAll('[data-modal-open]').forEach((button) => {
            const target = button.getAttribute('data-modal-open');
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const modal = document.querySelector(`[data-modal="${target}"]`);
                openModal(modal);
            });
        });

        document.querySelectorAll('[data-modal]').forEach((modal) => {
            modal.querySelectorAll('[data-modal-close]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    closeModal(modal);
                });
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                getOpenModals().forEach((modal) => closeModal(modal));
            }
        });

        if (@json(old('form_context') === 'create_user')) {
            const createModal = document.querySelector('[data-modal="create-user"]');
            openModal(createModal);
        }

        const organizationModal = document.querySelector('[data-modal="organization"]');
        const organizationForm = organizationModal?.querySelector('[data-organization-form]');
        const organizationUserLabel = organizationModal?.querySelector('[data-organization-user]');

        document.querySelectorAll('[data-organization-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                if (!organizationModal || !organizationForm) {
                    return;
                }

                organizationForm.setAttribute('action', button.dataset.action);
                if (organizationUserLabel) {
                    organizationUserLabel.textContent = button.dataset.user || 'Usuario';
                }

                const companySelect = organizationForm.querySelector('select[name="empresa_id"]');
                const departmentSelect = organizationForm.querySelector('select[name="department_id"]');
                const countriesSelect = organizationForm.querySelector('select[name="country_ids[]"]');
                const primarySelect = organizationForm.querySelector('select[name="primary_country_id"]');

                if (companySelect) {
                    companySelect.value = button.dataset.company || '';
                }

                if (departmentSelect) {
                    departmentSelect.value = button.dataset.department || '';
                }

                const selectedCountries = (() => {
                    try {
                        return JSON.parse(button.dataset.countries || '[]');
                    } catch (error) {
                        return [];
                    }
                })();

                if (countriesSelect) {
                    Array.from(countriesSelect.options).forEach((option) => {
                        option.selected = selectedCountries.includes(option.value);
                    });
                }

                if (primarySelect) {
                    primarySelect.value = button.dataset.primaryCountry || '';
                }

                openModal(organizationModal);
            });
        });

        const rolesModal = document.querySelector('[data-modal="roles"]');
        const rolesForm = rolesModal?.querySelector('[data-roles-form]');
        const rolesUserLabel = rolesModal?.querySelector('[data-roles-user]');

        document.querySelectorAll('[data-roles-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                if (!rolesModal || !rolesForm) {
                    return;
                }

                rolesForm.setAttribute('action', button.dataset.action);
                if (rolesUserLabel) {
                    rolesUserLabel.textContent = button.dataset.user || 'Usuario';
                }

                const selectedRoles = (() => {
                    try {
                        return JSON.parse(button.dataset.roles || '[]');
                    } catch (error) {
                        return [];
                    }
                })();

                rolesForm.querySelectorAll('input[name="roles[]"]').forEach((input) => {
                    input.checked = selectedRoles.includes(input.value);
                });

                openModal(rolesModal);
            });
        });
    });
</script>
