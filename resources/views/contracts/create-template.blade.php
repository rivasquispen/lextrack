<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Contratos</p>
                <h1 class="text-2xl font-semibold text-primary-dark">Utilizar plantilla institucional</h1>
                <p class="text-sm text-slate-500">Genera contratos estandarizados con modelos aprobados.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-primary font-semibold">← Volver al listado</a>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10 space-y-6">

        <form action="{{ route('flows.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8" data-contract-form>
        @csrf

        <div class="bg-white rounded-2xl p-6 shadow-panel space-y-4">
            <div>
                <h2 class="text-xl font-semibold text-primary-dark">Clasificación del contrato</h2>
                <p class="text-sm text-slate-500">Determina la categoría y subcategoría antes de seleccionar una template.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <!--<label class="text-sm font-semibold text-slate-600">Categoría</label>-->
                    <select name="categoria_id" id="categoria_id" required class="mt-1 w-full rounded-xl border-slate-200">
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('categoria_id') == $category->id)>{{ $category->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('categoria_id')" class="mt-1" />
                </div>
                <div>
                    <!--<label class="text-sm font-semibold text-slate-600">Subcategoría</label>-->
                    <select name="subcategoria_id" id="subcategoria_id" required class="mt-1 w-full rounded-xl border-slate-200" data-selected="{{ old('subcategoria_id') }}">
                        <option value="">Selecciona una subcategoría</option>
                    </select>
                    <x-input-error :messages="$errors->get('subcategoria_id')" class="mt-1" />
                </div>
            </div>
                <div>
                    <!--<label class="text-sm font-semibold text-slate-600">Nombre del Contrato</label>-->
                    <input type="text" name="titulo" value="{{ old('titulo') }}" placeholder="Nombre del Contrato: Ejemplo: Proveedor – Fecha" required class="mt-1 w-full rounded-xl border-slate-200">
                    <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                </div>
                <div class="hidden">
                    <label class="text-sm font-semibold text-slate-600">Abogado responsable</label>
                    <select name="abogado_id" class="mt-1 w-full rounded-xl border-slate-200">
                        <option value="">Selecciona un abogado</option>
                        @forelse ($lawyers as $lawyer)
                            <option value="{{ $lawyer->id }}" @selected(old('abogado_id') == $lawyer->id)>
                                {{ $lawyer->nombre ?? $lawyer->email }}
                            </option>
                        @empty
                            <option value="" disabled>No hay abogados registrados</option>
                        @endforelse
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Si aún no hay responsables legales, podrás asignarlo más adelante.</p>
                    <x-input-error :messages="$errors->get('abogado_id')" class="mt-1" />
                </div>


        </div>

        <div class="bg-white rounded-2xl p-6 shadow-panel space-y-4">
                        <div class="flex flex-wrap items-center gap-3 justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-primary-dark">Template</h2>
                                <p class="text-sm text-slate-500">Selecciona un template para ver su estructura y completar sus variables.</p>
                            </div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400" data-template-selected-label>Ningún template seleccionado</span>
                        </div>
                        <div>
                            <span class="mx-1 text-xs text-slate-400" data-template-helper>Selecciona subcategoría para ver templates.</span>
                            <select name="template_id" id="template_id" class="mt-1 w-full rounded-xl border-slate-200 disabled:bg-slate-50 disabled:cursor-not-allowed" data-selected="{{ old('template_id') }}" disabled required>
                                <option value="">Selecciona un template</option>
                            </select>
                            
                            <x-input-error :messages="$errors->get('template_id')" class="mt-1" />
                        </div>

                <div class="flex flex-col gap-6 lg:flex-row" data-template-panels>
                    <div class="order-1 lg:order-2 lg:w-1/3 lg:min-w-[250px] space-y-4" data-template-form-wrapper>
                        <div class="flex justify-end w-full"> 
                            <button type="button" class="text-xs font-semibold text-primary hover:text-primary-dark underline" data-request-info>
                                Solicitar información a la contraparte
                            </button>
                        </div>
                        
                        <div data-template-form class="space-y-4 text-sm text-slate-700">
                            <p class="text-sm text-slate-500">Selecciona un template para completar sus campos dinámicos.</p>
                        </div>
                        <input type="hidden" name="template_payload" value="{{ old('template_payload') }}" data-template-payload>
                    </div>
                    <div class="order-2 lg:order-1 lg:w-2/3 space-y-4" data-template-workspace>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 overflow-auto" data-template-preview>
                            <p class="text-sm text-slate-500">Selecciona un template para mostrar la previsualización.</p>
                        </div>
                    </div>
                </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-panel space-y-4">
            <div>

<h2 class="text-xl font-semibold text-primary-dark">
    {{ __('custom.requirements.title') }}
</h2>

<p class="text-sm text-slate-500 mb-2">
    {{ __('custom.requirements.description') }}
</p>

<ul class="list-disc list-inside text-sm text-slate-500">
    @foreach(__('custom.requirements.items') as $key => $text)
        <li>{{ $text }}</li>
    @endforeach
</ul>

<datalist id="document_suggestions">
    @foreach(__('custom.requirements.items') as $text)
        <option value="{{ $text }}">
    @endforeach
</datalist>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-2">
                        <button type="button" class="text-sm font-semibold text-primary hover:text-primary-light" data-attachment-add>+ Agregar adjunto</button>
                        <span class="text-xs text-slate-400">Se aceptan PDF, JPG, PNG, JPEG, TIFF (máx. 10 MB).</span>
                    </div>
                    <div class="space-y-3" data-attachment-list></div>
                    <x-input-error :messages="collect($errors->get('attachments.*.name'))->flatten()->all()" class="mt-2" />
                    <x-input-error :messages="collect($errors->get('attachments.*.file'))->flatten()->all()" class="mt-1" />
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl text-sm text-slate-500 hover:text-primary">Cancelar</a>
            <button type="submit" class="ms-4 btn-lex btn-lex-primary" data-submit-button>
                Crear contrato
            </button>
        </div>
        </form>

    </div>

    <div class="fixed inset-0 z-40 hidden" data-request-modal>
        <div class="absolute inset-0 bg-slate-900/60" data-request-close></div>
        <div class="relative z-10 mx-auto mt-10 w-full max-w-3xl px-4" role="dialog" aria-modal="true">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Solicitud a contraparte</p>
                        <h3 class="text-lg font-semibold text-primary-dark">Solicitar información</h3>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" data-request-close>
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 8.586L4.293 2.879A1 1 0 102.879 4.293L8.586 10l-5.707 5.707a1 1 0 101.414 1.414L10 11.414l5.707 5.707a1 1 0 001.414-1.414L11.414 10l5.707-5.707a1 1 0 00-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-6 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Asunto</label>
                        <input type="text" class="mt-1 w-full rounded-xl border-slate-200" data-request-subject>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-600">Mensaje para la contraparte</label>
                        <textarea rows="10" class="mt-1 w-full rounded-2xl border-slate-200" data-request-body></textarea>
                        <p class="mt-1 text-xs text-slate-500">Este texto incluye todos los campos del formulario para que la contraparte complete la información.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 bg-slate-50 border-t border-slate-100">
                    <div class="text-xs text-slate-500">Puedes copiar el mensaje o abrir tu cliente de correo con un clic.</div>
                    <div class="flex items-center gap-3">
                        <button type="button" class="text-sm font-semibold text-slate-600 hover:text-primary" data-request-copy>Copiar al portapapeles</button>
                        <button type="button" class="btn-lex btn-lex-primary hidde" data-request-send>Redactar correo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categories = @json($categoryMatrix);
            const templateGroups = @json($templatesBySubcategory ?? []);
            const templateLookup = @json($templatesLookup ?? []);

            const categoriaSelect = document.getElementById('categoria_id');
            const subcategoriaSelect = document.getElementById('subcategoria_id');
            const templateSelect = document.getElementById('template_id');
            const templateHelper = document.querySelector('[data-template-helper]');
            const templatePreview = document.querySelector('[data-template-preview]');
            const templateFormContainer = document.querySelector('[data-template-form]');
            const templateLabel = document.querySelector('[data-template-selected-label]');
            const payloadInput = document.querySelector('[data-template-payload]');
            const formColumn = document.querySelector('[data-template-form-wrapper]');
            const attachmentList = document.querySelector('[data-attachment-list]');
            const attachmentAddButton = document.querySelector('[data-attachment-add]');
            const oldAttachments = @json(old('attachments', []));
            const contractTitleInput = document.querySelector('input[name="titulo"]');
            const requestInfoButton = document.querySelector('[data-request-info]');
            const requestModal = document.querySelector('[data-request-modal]');
            const requestEmailInput = requestModal?.querySelector('[data-request-email]');
            const requestSubjectInput = requestModal?.querySelector('[data-request-subject]');
            const requestBodyTextarea = requestModal?.querySelector('[data-request-body]');
            const requestCopyButton = requestModal?.querySelector('[data-request-copy]');
            const requestSendButton = requestModal?.querySelector('[data-request-send]');
            const requestCloseButtons = requestModal ? requestModal.querySelectorAll('[data-request-close]') : [];

            const safeParse = (value) => {
                if (!value) {
                    return {};
                }
                try {
                    const parsed = JSON.parse(value);
                    return typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
                } catch (error) {
                    return {};
                }
            };

            const escapeRegExp = (string = '') => String(string).replace(/[\\^$.*+?()[\]{}|]/g, '\\$&');

            const isIsoDateString = (value) => typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value);

            const formatLongDate = (value) => {
                if (!isIsoDateString(value)) {
                    return value;
                }
                const parsed = new Date(value + 'T00:00:00');
                if (Number.isNaN(parsed.getTime())) {
                    return value;
                }
                return new Intl.DateTimeFormat('es-ES', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                }).format(parsed);
            };

            const formatTemplateValue = (value) => {
                if (value === null || value === undefined) {
                    return '';
                }
                if (typeof value === 'boolean') {
                    return value ? 'Sí' : 'No';
                }
                if (isIsoDateString(value)) {
                    return formatLongDate(value);
                }
                return String(value);
            };

            const normalizeInputValue = (value) => {
                if (value === null || value === undefined) {
                    return '';
                }
                return value;
            };

            const gatherTemplateFields = (template) => {
                if (!template) {
                    return [];
                }
                const sections = Array.isArray(template.forms) ? template.forms : [];
                const collected = [];
                sections.forEach((section) => {
                    if (!Array.isArray(section.campos)) {
                        return;
                    }
                    section.campos.forEach((field) => {
                        if (!field || !field.name) {
                            return;
                        }
                        collected.push({
                            name: field.name,
                            label: field.label || field.name,
                        });
                    });
                });
                return collected;
            };

            const buildRequestBody = () => {
                const template = currentTemplateId ? templateLookup[currentTemplateId] : null;
                const contractReference = contractTitleInput?.value?.trim() || currentTemplateName || template?.nombre || 'nuevo contrato';
                const header = `Estimado equipo,\n\nPor favor completar la siguiente información para continuar con el contrato ${contractReference}:\n\n`;

                if (!currentTemplateFields.length) {
                    return `${header}—\n\nMuchas gracias,\nEquipo Lextrack`;
                }

                const lines = currentTemplateFields.map((field) => {
                    const rawValue = formValues[field.name];
                    const hasValue = rawValue !== undefined && rawValue !== null && String(rawValue).trim() !== '';
                    const formatted = hasValue ? formatTemplateValue(rawValue) : '__________';
                    return `${field.label}: ${formatted}`;
                });

                return `${header}${lines.join('\n')}\n\nMuchas gracias,\nEquipo Lextrack`;
            };

            const refreshRequestBody = () => {
                if (!requestModal || requestModal.classList.contains('hidden')) {
                    return;
                }
                if (requestBodyTextarea) {
                    requestBodyTextarea.value = buildRequestBody();
                }
            };

            const populateRequestModal = () => {
                if (!requestModal) {
                    return;
                }
                const template = currentTemplateId ? templateLookup[currentTemplateId] : null;
                currentTemplateName = template?.nombre || currentTemplateName;
                const contractReference = contractTitleInput?.value?.trim() || currentTemplateName || 'nuevo contrato';
                if (requestSubjectInput) {
                    requestSubjectInput.value = `Lextrack: contrato ${contractReference}`;
                }
                if (requestBodyTextarea) {
                    requestBodyTextarea.value = buildRequestBody();
                    requestBodyTextarea.focus();
                    requestBodyTextarea.setSelectionRange(0, 0);
                }
            };

            const openRequestModal = () => {
                if (!requestModal) {
                    return;
                }
                requestModal.classList.remove('hidden');
                document.documentElement.classList.add('overflow-hidden');
            };

            const closeRequestModal = () => {
                if (!requestModal) {
                    return;
                }
                requestModal.classList.add('hidden');
                document.documentElement.classList.remove('overflow-hidden');
            };

            const applyValues = (content, values) => {
                if (!content) {
                    return '';
                }

                let compiled = content;
                Object.entries(values || {}).forEach(([key, value]) => {
                    if (!key) {
                        return;
                    }
                    const pattern = new RegExp('\\{\\{\\s*' + escapeRegExp(key) + '\\s*\\}\\}', 'g');
                    compiled = compiled.replace(pattern, formatTemplateValue(value));
                });

                return compiled;
            };

            const initialPayload = safeParse(payloadInput ? payloadInput.value : '');
            let initialPayloadApplied = false;
            let currentTemplateId = null;
            let currentTemplateFields = [];
            let currentTemplateName = '';
            let formValues = {};
            let attachmentIndex = 0;

            const updateTemplateHelper = (message, tone = 'muted') => {
                if (!templateHelper) {
                    return;
                }
                templateHelper.textContent = message;
                templateHelper.classList.remove('text-slate-400', 'text-slate-500', 'text-red-500');
                const toneClass = tone === 'info' ? 'text-slate-500' : tone === 'danger' ? 'text-red-500' : 'text-slate-400';
                templateHelper.classList.add(toneClass);
            };

            const syncPayload = () => {
                if (!payloadInput) {
                    return;
                }
                const hasValues = Object.keys(formValues).length > 0;
                payloadInput.value = hasValues ? JSON.stringify(formValues) : '';
            };

            const syncPreviewHeight = () => {
                if (!formColumn || !templatePreview) {
                    return;
                }
                const formHeight = formColumn.offsetHeight;

                if (!formHeight) {
                    templatePreview.style.removeProperty('height');
                    templatePreview.style.removeProperty('min-height');
                    return;
                }

                templatePreview.style.height = formHeight + 'px';
                templatePreview.style.minHeight = '0px';
            };

            if (typeof ResizeObserver !== 'undefined' && formColumn) {
                const resizeObserver = new ResizeObserver(() => syncPreviewHeight());
                resizeObserver.observe(formColumn);
            }

            window.addEventListener('resize', syncPreviewHeight);

            const renderPreview = (template) => {
                if (!templatePreview) {
                    return;
                }

                if (!template) {
                    templatePreview.innerHTML = '<p class="text-sm text-slate-500">Selecciona un template para mostrar la previsualización.</p>';
                    syncPreviewHeight();
                    return;
                }

                const baseContent = template.descripcion || '';
                if (!baseContent.trim()) {
                    templatePreview.innerHTML = '<p class="text-sm text-slate-500">El template seleccionado aún no tiene contenido.</p>';
                    syncPreviewHeight();
                    return;
                }

                templatePreview.innerHTML = applyValues(baseContent, formValues);
                syncPreviewHeight();
            };

            const createField = (field = {}) => {
                const wrapper = document.createElement('div');
                const fieldName = field.name || '';
                const fieldId = fieldName ? `template-field-${fieldName}` : `template-field-${Math.random().toString(36).slice(2)}`;
                const label = document.createElement('label');
                label.className = 'text-sm font-semibold text-slate-600 flex flex-wrap items-center justify-between gap-2';
                label.textContent = field.label || fieldName || 'Campo';
                if (fieldName) {
                    label.setAttribute('for', fieldId);
                }
                if (field.required) {
                    const badge = document.createElement('span');
                    badge.className = 'text-xs text-red-500';
                    badge.textContent = 'Requerido';
                    label.appendChild(badge);
                }
                wrapper.appendChild(label);

                const type = (field.input || 'text').toLowerCase();
                const currentValue = fieldName && formValues[fieldName] !== undefined
                    ? formValues[fieldName]
                    : field.default ?? '';

                let control;
                if (type === 'textarea') {
                    control = document.createElement('textarea');
                    control.rows = field.rows || 4;
                    control.value = normalizeInputValue(currentValue);
                } else if (type === 'select') {
                    control = document.createElement('select');
                    const placeholderOption = document.createElement('option');
                    placeholderOption.value = '';
                    placeholderOption.textContent = 'Selecciona una opción';
                    control.appendChild(placeholderOption);
                    (field.options || []).forEach((option) => {
                        const opt = document.createElement('option');
                        opt.value = option.value ?? option.label ?? '';
                        opt.textContent = option.label ?? option.value ?? 'Opción';
                        if (String(opt.value) === String(currentValue)) {
                            opt.selected = true;
                        }
                        control.appendChild(opt);
                    });
                } else if (type === 'checkbox') {
                    control = document.createElement('input');
                    control.type = 'checkbox';
                    control.checked = Boolean(currentValue);
                    control.className = 'rounded border-slate-300 text-primary focus:ring-primary';
                } else {
                    control = document.createElement('input');
                    control.type = ['number', 'date', 'email'].includes(type) ? type : 'text';
                    control.value = normalizeInputValue(currentValue);
                }

                if (type !== 'checkbox') {
                    control.classList.add('mt-1', 'w-full', 'rounded-xl', 'border-slate-200');
                }

                if (fieldName) {
                    control.dataset.fieldName = fieldName;
                    control.id = fieldId;
                }

                if (field.placeholder && type !== 'checkbox') {
                    control.placeholder = field.placeholder;
                }
                control.required = Boolean(field.required);

                const handleInput = (event) => {
                    if (!fieldName) {
                        return;
                    }
                    if (event.target.type === 'checkbox') {
                        formValues[fieldName] = event.target.checked;
                    } else {
                        formValues[fieldName] = event.target.value;
                    }
                    renderPreview(templateLookup[currentTemplateId]);
                    syncPayload();
                    refreshRequestBody();
                };

                const eventType = type === 'select' || type === 'checkbox' ? 'change' : 'input';
                control.addEventListener(eventType, handleInput);
                if (eventType !== 'input') {
                    control.addEventListener('input', handleInput);
                }

                if (type === 'checkbox') {
                    const checkboxWrapper = document.createElement('div');
                    checkboxWrapper.className = 'flex items-center gap-2 mt-1';
                    checkboxWrapper.appendChild(control);
                    const checkboxLabel = document.createElement('span');
                    checkboxLabel.className = 'text-sm text-slate-600';
                    checkboxLabel.textContent = field.placeholder || 'Selecciona si aplica';
                    checkboxWrapper.appendChild(checkboxLabel);
                    wrapper.appendChild(checkboxWrapper);
                } else {
                    wrapper.appendChild(control);
                }

                if (field.help) {
                    const help = document.createElement('p');
                    help.className = 'mt-1 text-xs text-slate-500';
                    help.textContent = field.help;
                    wrapper.appendChild(help);
                }

                return wrapper;
            };

            const renderForm = (template) => {
                if (!templateFormContainer) {
                    return;
                }

                templateFormContainer.innerHTML = '';

                if (!template) {
                    templateFormContainer.innerHTML = '<p class="text-sm text-slate-500">Selecciona un template para completar sus campos dinámicos.</p>';
                    syncPreviewHeight();
                    return;
                }

                const sections = Array.isArray(template.forms) ? template.forms : [];
                if (!sections.length) {
                    templateFormContainer.innerHTML = '<p class="text-sm text-slate-500">Este template no tiene formulario configurado.</p>';
                    syncPreviewHeight();
                    return;
                }

                sections.forEach((section, index) => {
                    const block = document.createElement('div');
                    block.className = 'pb-3';

                    const title = document.createElement('p');
                    title.className = 'text-sm font-semibold text-primary-dark mb-3';
                    title.textContent = section.subtitulo || `Sección ${index + 1}`;
                    block.appendChild(title);

                    const fieldsWrapper = document.createElement('div');
                    fieldsWrapper.className = 'grid gap-4';

                    if (Array.isArray(section.campos) && section.campos.length) {
                        section.campos.forEach((field) => {
                            fieldsWrapper.appendChild(createField(field));
                        });
                    } else {
                        const empty = document.createElement('p');
                        empty.className = 'text-sm text-slate-500';
                        empty.textContent = 'No hay campos definidos para esta sección.';
                        fieldsWrapper.appendChild(empty);
                    }

                    block.appendChild(fieldsWrapper);
                    templateFormContainer.appendChild(block);
                });

                syncPreviewHeight();
            };

            const addAttachmentRow = (data = {}) => {
                if (!attachmentList) {
                    return;
                }

                const currentIndex = attachmentIndex++;
                const wrapper = document.createElement('div');
                wrapper.className = 'border-t-2 border-dashed border-slate-200 p-4 space-y-3';
                wrapper.dataset.attachment = currentIndex;

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between gap-2';
                const title = document.createElement('span');
                title.className = 'text-sm font-semibold text-primary-dark';
                let nameInput;
                const updateTitle = () => {
                    const labelValue = nameInput?.value?.trim();
                    title.textContent = labelValue
                        ? `Adjunto: ${labelValue}`
                        : `Adjunto #${currentIndex + 1}`;
                };

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'text-xs font-semibold text-red-500 hover:text-red-600';
                removeButton.textContent = 'Quitar';
                removeButton.addEventListener('click', () => {
                    wrapper.remove();
                });

                header.appendChild(title);
                header.appendChild(removeButton);
                wrapper.appendChild(header);

                const nameField = document.createElement('div');
                nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.name = `attachments[${currentIndex}][name]`;
                nameInput.placeholder = 'Nombre del adjunto';
                nameInput.className = 'mt-1 w-full rounded-xl border-slate-200';
                nameInput.value = data.name || '';
                nameInput.required = true;
                nameInput.setAttribute('list', 'document_suggestions');
                nameInput.setAttribute('autocomplete', 'off');
                nameInput.addEventListener('input', updateTitle);
                nameField.appendChild(nameInput);

                const fileField = document.createElement('div');
                const fileInputEl = document.createElement('input');
                fileInputEl.type = 'file';
                fileInputEl.name = `attachments[${currentIndex}][file]`;
                fileInputEl.accept = '.pdf,jpg,.jpeg,.png,.tiff';
                fileInputEl.className = 'mt-2 w-full border-slate-200';
                fileField.appendChild(fileInputEl);

                const grid = document.createElement('div');
                grid.className = 'grid gap-4 md:grid-cols-2';
                grid.appendChild(nameField);
                grid.appendChild(fileField);

                wrapper.appendChild(grid);
                attachmentList.appendChild(wrapper);
                updateTitle();
            };

            const setTemplate = (templateId, { hydrateFromPayload = false } = {}) => {
                if (!templateSelect) {
                    return;
                }

                if (!templateId || !templateLookup[templateId]) {
                    currentTemplateId = null;
                    currentTemplateFields = [];
                    currentTemplateName = '';
                    formValues = {};
                    if (templateLabel) {
                        templateLabel.textContent = 'Ningún template seleccionado';
                    }
                    renderForm(null);
                    renderPreview(null);
                    syncPayload();
                    return;
                }

                if (templateId === currentTemplateId && !hydrateFromPayload) {
                    return;
                }

                currentTemplateId = templateId;
                const template = templateLookup[templateId];
                currentTemplateFields = gatherTemplateFields(template);
                currentTemplateName = template?.nombre || '';

                if (hydrateFromPayload && !initialPayloadApplied && Object.keys(initialPayload).length) {
                    formValues = { ...initialPayload };
                    initialPayloadApplied = true;
                } else {
                    formValues = {};
                }

                if (templateLabel) {
                    templateLabel.textContent = template?.nombre || 'Template seleccionado';
                }

                renderForm(template);
                renderPreview(template);
                syncPayload();
                refreshRequestBody();
            };

            const refreshTemplateOptions = (subcategoryId, { hydrateFromOld = false } = {}) => {
                if (!templateSelect) {
                    return;
                }

                const groupKey = subcategoryId ? String(subcategoryId) : null;
                const options = groupKey && templateGroups[groupKey] ? templateGroups[groupKey] : [];
                const storedSelection = hydrateFromOld ? (templateSelect.dataset.selected || '') : '';
                let selectedApplied = false;

                templateSelect.innerHTML = '<option value="">Selecciona un template</option>';

                options.forEach((tpl) => {
                    const option = document.createElement('option');
                    option.value = tpl.id;
                    option.textContent = tpl.nombre;
                    if (storedSelection && String(storedSelection) === String(tpl.id)) {
                        option.selected = true;
                        selectedApplied = true;
                    }
                    templateSelect.appendChild(option);
                });

                templateSelect.disabled = options.length === 0;

                if (!options.length) {
                    if (!subcategoryId) {
                        updateTemplateHelper('Ver templates por Subcategoría.');
                    } else {
                        updateTemplateHelper('No hay templates disponibles.');
                    }
                    setTemplate(null);
                    return;
                }

                updateTemplateHelper('Seleccionar Template.', 'info');

                if (selectedApplied) {
                    setTemplate(templateSelect.value, { hydrateFromPayload: true });
                } else {
                    setTemplate(null);
                }

                templateSelect.dataset.selected = '';
            };

            const populateSubcategories = (categoryId, { hydrateFromOld = false } = {}) => {
                if (!subcategoriaSelect) {
                    return;
                }

                subcategoriaSelect.innerHTML = '<option value="">Selecciona una subcategoría</option>';
                const storedSubcategory = hydrateFromOld ? (subcategoriaSelect.dataset.selected || '') : '';
                let matched = false;
                const category = categories.find((cat) => String(cat.id) === String(categoryId));

                if (category) {
                    category.subs.forEach((sub) => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.nombre;
                        if (storedSubcategory && String(storedSubcategory) === String(sub.id)) {
                            option.selected = true;
                            matched = true;
                        }
                        subcategoriaSelect.appendChild(option);
                    });
                }

                if (!matched) {
                    subcategoriaSelect.value = '';
                }

                refreshTemplateOptions(subcategoriaSelect.value, { hydrateFromOld: matched && hydrateFromOld });
                subcategoriaSelect.dataset.selected = '';
            };

            categoriaSelect?.addEventListener('change', (event) => {
                if (subcategoriaSelect) {
                    subcategoriaSelect.dataset.selected = '';
                }
                if (templateSelect) {
                    templateSelect.dataset.selected = '';
                }
                populateSubcategories(event.target.value || '');
            });

            subcategoriaSelect?.addEventListener('change', (event) => {
                if (templateSelect) {
                    templateSelect.dataset.selected = '';
                }
                refreshTemplateOptions(event.target.value || '');
            });

            templateSelect?.addEventListener('change', (event) => {
                templateSelect.dataset.selected = event.target.value || '';
                setTemplate(event.target.value || '');
            });

            if (Array.isArray(oldAttachments) && oldAttachments.length) {
                oldAttachments.forEach((attachment) => addAttachmentRow(attachment));
            }

            attachmentAddButton?.addEventListener('click', () => addAttachmentRow());

            requestInfoButton?.addEventListener('click', () => {
                if (!currentTemplateId || !templateLookup[currentTemplateId]) {
                    updateTemplateHelper('Selecciona un template antes de solicitar información.', 'danger');
                    return;
                }
                populateRequestModal();
                openRequestModal();
            });

            requestCloseButtons.forEach((button) => {
                button.addEventListener('click', () => closeRequestModal());
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && requestModal && !requestModal.classList.contains('hidden')) {
                    closeRequestModal();
                }
            });

            requestCopyButton?.addEventListener('click', async () => {
                if (!requestBodyTextarea) {
                    return;
                }
                const originalText = requestCopyButton.textContent;
                const copyFallback = () => alert('No se pudo copiar automáticamente, copia el texto manualmente.');

                if (!navigator?.clipboard?.writeText) {
                    copyFallback();
                    return;
                }

                try {
                    await navigator.clipboard.writeText(requestBodyTextarea.value);
                    requestCopyButton.textContent = 'Copiado';
                    setTimeout(() => {
                        requestCopyButton.textContent = originalText || 'Copiar al portapapeles';
                    }, 2000);
                } catch (error) {
                    copyFallback();
                }
            });

            requestSendButton?.addEventListener('click', () => {
                if (!requestBodyTextarea) {
                    return;
                }
                const targetEmail = requestEmailInput?.value?.trim();
                if (!targetEmail) {
                    requestEmailInput?.focus();
                    return;
                }
                const subject = requestSubjectInput?.value?.trim() || 'Solicitud de información sobre contrato';
                const body = requestBodyTextarea.value || '';
                const mailto = `mailto:${encodeURIComponent(targetEmail)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
                window.location.href = mailto;
            });

            populateSubcategories(categoriaSelect ? categoriaSelect.value : '', { hydrateFromOld: true });
            syncPreviewHeight();

            const contractForm = document.querySelector('[data-contract-form]');
            const submitButton = document.querySelector('[data-submit-button]');

            contractForm?.addEventListener('submit', () => {
                if (!submitButton) {
                    return;
                }
                submitButton.disabled = true;
                submitButton.classList.add('opacity-70');
                submitButton.innerHTML = '<svg class="me-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>Creando contrato...';
            });
        });
    </script>
</x-app-layout>
