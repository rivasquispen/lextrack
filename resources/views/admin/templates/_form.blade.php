@php
    $template = $template ?? null;
    $categoryOptions = $categories ?? collect();
    $categoryMatrix = $categoryMatrix ?? collect();
    $selectedCategory = old('categoria_id', optional(optional($template)->category)->id);
    $selectedSubcategory = old('subcategoria_id', optional($template)->subcategoria_id);
    $formsValue = old('forms');
    if ($formsValue === null) {
        $formsValue = $template && $template->forms
            ? json_encode($template->forms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : '';
    }
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" data-template-form>
    @csrf
    @if (isset($method) && strtoupper($method) === 'PUT')
        @method('PUT')
    @endif

    <div class="grid gap-4 md:grid-cols-2" data-category-wrapper>
        <div>
            <label class="text-sm font-semibold text-slate-600">Categoría</label>
            <select name="categoria_id" class="mt-1 w-full rounded-xl border-slate-200" data-category>
                <option value="">Selecciona</option>
                @foreach ($categoryOptions as $category)
                    <option value="{{ $category->id }}" @selected($selectedCategory == $category->id)>{{ $category->nombre }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('categoria_id')" class="mt-1" />
        </div>
        <div>
            <label class="text-sm font-semibold text-slate-600">Subcategoría</label>
            <select name="subcategoria_id" class="mt-1 w-full rounded-xl border-slate-200" data-subcategory data-selected="{{ $selectedSubcategory }}" required>
                <option value="">Selecciona</option>
            </select>
            <x-input-error :messages="$errors->get('subcategoria_id')" class="mt-1" />
        </div>
    </div>

    <div>
        <label class="text-sm font-semibold text-slate-600">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', optional($template)->nombre) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
        <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
    </div>

    <div data-tabs class="mt-6">
        <div class="flex gap-2 border-b border-slate-200" role="tablist">
            <button type="button" class="px-4 py-2 text-sm font-semibold text-primary border-b-2 border-primary" data-tab-trigger="template">Template</button>
            <button type="button" class="px-4 py-2 text-sm font-semibold text-slate-500 border-b-2 border-transparent hover:text-primary" data-tab-trigger="formulario">Formulario</button>
        </div>

        <div class="pt-4" data-tab-panel="template">
            <label class="text-sm font-semibold text-slate-600">Descripción</label>
            <textarea name="descripcion" rows="8" class="js-richtext mt-1 w-full rounded-xl border-slate-200">{{ old('descripcion', optional($template)->descripcion) }}</textarea>
            <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
        </div>

        <div class="pt-4 hidden" data-tab-panel="formulario">
            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold text-slate-600">Forms (JSON)</label>
                <span class="text-xs" data-forms-status></span>
            </div>
            <textarea name="forms" rows="12" class="font-mono mt-1 w-full rounded-xl border-slate-200" data-forms-input placeholder='[{"subtitulo": "Datos", "campos": []}]'>{{ $formsValue }}</textarea>
            <p class="mt-1 text-xs text-slate-500">Proporciona la definición JSON del formulario que se usará al generar el template.</p>
            <x-input-error :messages="$errors->get('forms')" class="mt-1" />
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.templates.index') }}" class="text-sm text-slate-500 hover:text-primary">Cancelar</a>
        <button class="btn-lex btn-lex-primary">
            {{ $submitLabel ?? 'Guardar' }}
        </button>
    </div>
</form>

@push('scripts')
    @vite('resources/js/editor.js')
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const matrix = @json($categoryMatrix);
            const form = document.querySelector('[data-template-form]');
            if (!form) {
                return;
            }

            const categorySelect = form.querySelector('[data-category]');
            const subcategorySelect = form.querySelector('[data-subcategory]');

            const renderOptions = (categoryId, selectedValue = null) => {
                subcategorySelect.innerHTML = '<option value="">Selecciona</option>';
                const category = matrix.find((item) => item.id == categoryId);
                if (!category) {
                    return;
                }

                category.subs.forEach((sub) => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.nombre;
                    option.selected = selectedValue == sub.id;
                    subcategorySelect.appendChild(option);
                });
            };

            categorySelect.addEventListener('change', (event) => {
                renderOptions(event.target.value, null);
            });

            const initialCategory = categorySelect.value;
            const initialSubcategory = subcategorySelect.dataset.selected;
            if (initialCategory) {
                renderOptions(initialCategory, initialSubcategory);
            }

            const formsInput = form.querySelector('[data-forms-input]');
            const formsStatus = form.querySelector('[data-forms-status]');
            const updateFormsStatus = () => {
                const value = formsInput.value.trim();
                if (!value.length) {
                    formsInput.classList.remove('border-red-400', 'ring-1', 'ring-red-200', 'border-emerald-300');
                    if (formsStatus) {
                        formsStatus.textContent = 'Opcional';
                        formsStatus.className = 'text-xs text-slate-400';
                    }
                    return;
                }

                try {
                    JSON.parse(value);
                    formsInput.classList.remove('border-red-400', 'ring-1', 'ring-red-200');
                    formsInput.classList.add('border-emerald-300');
                    if (formsStatus) {
                        formsStatus.textContent = 'JSON válido';
                        formsStatus.className = 'text-xs text-emerald-600';
                    }
                } catch (error) {
                    formsInput.classList.remove('border-emerald-300');
                    formsInput.classList.add('border-red-400', 'ring-1', 'ring-red-200');
                    if (formsStatus) {
                        formsStatus.textContent = 'JSON inválido';
                        formsStatus.className = 'text-xs text-red-600';
                    }
                }
            };

            formsInput?.addEventListener('input', updateFormsStatus);
            if (formsInput) {
                updateFormsStatus();
            }

            const tabsRoot = form.querySelector('[data-tabs]');
            if (tabsRoot) {
                const tabButtons = tabsRoot.querySelectorAll('[data-tab-trigger]');
                const tabPanels = tabsRoot.querySelectorAll('[data-tab-panel]');
                const activateTab = (target) => {
                    tabButtons.forEach((button) => {
                        const isActive = button.dataset.tabTrigger === target;
                        button.classList.toggle('text-primary', isActive);
                        button.classList.toggle('text-slate-500', !isActive);
                        button.classList.toggle('border-primary', isActive);
                        button.classList.toggle('border-transparent', !isActive);
                    });

                    tabPanels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.tabPanel !== target);
                    });
                };

                tabButtons.forEach((button) => {
                    button.addEventListener('click', () => activateTab(button.dataset.tabTrigger));
                });

                const defaultTab = tabButtons.length ? tabButtons[0].dataset.tabTrigger : null;
                if (defaultTab) {
                    activateTab(defaultTab);
                }
            }
        });
    </script>
@endpush
