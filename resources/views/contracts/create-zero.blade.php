<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Contratos</p>
                <h1 class="text-2xl font-semibold text-primary-dark">Cargar documento externo</h1>
                <p class="text-sm text-slate-500">Inicia un flujo utilizando un borrador enviado por la contraparte.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-primary font-semibold">← Volver al listado</a>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10 space-y-6">
        <form action="{{ route('contracts.store-zero') }}" method="POST" enctype="multipart/form-data" class="space-y-8" data-zero-form>
            @csrf

            <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <h2 class="text-xl font-semibold text-primary-dark">Clasificación del contrato</h2>
                    <p class="text-sm text-slate-500">Selecciona la categoría y los metadatos antes de adjuntar el documento.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <!--<label for="categoria_id" class="text-sm font-semibold text-slate-600">Categoría</label>-->
                        <select name="categoria_id" id="categoria_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('categoria_id') == $category->id)>{{ $category->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('categoria_id')" class="mt-1" />
                    </div>
                    <div>
                        <!--<label for="subcategoria_id" class="text-sm font-semibold text-slate-600">Subcategoría</label>-->
                        <select name="subcategoria_id" id="subcategoria_id" class="mt-1 w-full rounded-xl border-slate-200" data-selected="{{ old('subcategoria_id') }}" required>
                            <option value="">Selecciona una subcategoría</option>
                        </select>
                        <x-input-error :messages="$errors->get('subcategoria_id')" class="mt-1" />
                    </div>
                </div>
                    <div>
                        <!--<label for="titulo" class="text-sm font-semibold text-slate-600">Nombre del contrato</label>-->
                        <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Nombre del Contrato: Ejemplo: Proveedor – Fecha" required>
                        <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                    </div>
                <div class="hidden">
                    <label for="abogado_id" class="text-sm font-semibold text-slate-600">Abogado responsable</label>
                    <select name="abogado_id" id="abogado_id" class="mt-1 w-full rounded-xl border-slate-200">
                        <option value="">Selecciona un abogado</option>
                        @foreach ($lawyers as $lawyer)
                            <option value="{{ $lawyer->id }}" @selected(old('abogado_id') == $lawyer->id)>{{ $lawyer->nombre ?? $lawyer->email }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Podrás definir responsabilidades más adelante.</p>
                    <x-input-error :messages="$errors->get('abogado_id')" class="mt-1" />
                </div>
            </section>

            <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                <div>
                    <h2 class="text-xl font-semibold text-primary-dark">Documento externo</h2>
                    <p class="text-sm text-slate-500">El archivo Word será registrado como la primera versión del contrato.</p>
                </div>
                <div data-dropzone class="space-y-2">
                    <input type="file" id="external_document" name="external_document" accept=".doc,.docx" class="sr-only" required>
                    <label for="external_document" data-dropzone-label class="block rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 hover:border-primary/40 hover:bg-primary/5 transition cursor-pointer p-6 text-center">
                        <p class="font-semibold text-slate-700">Microsoft Word (.doc o .docx)<br>Arrastra tu archivo o haz clic para seleccionarlo</p>
                        <p class="text-xs text-slate-500 mt-1" data-dropzone-helper>Solo .doc / .docx · Máx. 15 MB</p>
                        <p class="text-xs text-primary font-semibold mt-2 hidden" data-dropzone-filename></p>
                    </label>
                    <x-input-error :messages="$errors->get('external_document')" class="mt-2" />
                </div>

                <div class="space-y-4">
                    
                </div>

                @foreach ($defaultForm as $block)
                    <div class="space-y-3">
                        <h3 class="text-lg font-semibold text-primary-dark">{{ $block['subtitulo'] }}</h3>
                        <div class="grid gap-4 md:grid-cols-3">
                            @foreach ($block['campos'] as $field)
                                <div>
                                    <label class="text-sm font-semibold text-slate-600">{{ $field['label'] }}</label>
                                    <input type="{{ $field['input'] }}" name="form[{{ $field['name'] }}]" value="{{ old('form.'.$field['name']) }}" class="mt-1 w-full rounded-xl border-slate-200" @if($field['required']) required @endif>
                                    <x-input-error :messages="$errors->get('form.'.$field['name'])" class="mt-1" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @unless ($loop->last)
                        <!--<hr class="border-slate-100">-->
                    @endunless
                @endforeach
            </section>

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
                        <x-input-error :messages="$errors->get('attachments.*.name')" class="mt-2" />
                        <x-input-error :messages="$errors->get('attachments.*.file')" class="mt-1" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl text-sm text-slate-500 hover:text-primary">Cancelar</a>
                <button type="submit" class="ms-4 btn-lex btn-lex-primary" data-submit-button>Guardar contrato</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categoryMatrix = @json($categoryMatrix);
            const categoriaSelect = document.getElementById('categoria_id');
            const subcategoriaSelect = document.getElementById('subcategoria_id');
            const attachmentList = document.querySelector('[data-attachment-list]');
            const attachmentAddButton = document.querySelector('[data-attachment-add]');
            const dropInput = document.getElementById('external_document');
            const dropLabel = document.querySelector('[data-dropzone-label]');
            const dropFilename = document.querySelector('[data-dropzone-filename]');

            const populateSubcategories = (categoryId, hydrateFromOld = false) => {
                if (! subcategoriaSelect) {
                    return;
                }
                subcategoriaSelect.innerHTML = '<option value="">Selecciona una subcategoría</option>';
                const category = categoryMatrix.find((item) => String(item.id) === String(categoryId));
                if (! category) {
                    return;
                }
                const storedSub = hydrateFromOld ? (subcategoriaSelect.dataset.selected || '') : '';
                category.subs.forEach((sub) => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.nombre;
                    subcategoriaSelect.appendChild(option);
                });
                if (storedSub) {
                    subcategoriaSelect.value = storedSub;
                    subcategoriaSelect.dataset.selected = '';
                }
            };

            categoriaSelect?.addEventListener('change', (event) => {
                if (subcategoriaSelect) {
                    subcategoriaSelect.dataset.selected = '';
                }
                populateSubcategories(event.target.value);
            });

            let attachmentCounter = 0;
            const addAttachmentRow = (data = {}, focus = false) => {
                if (! attachmentList) {
                    return;
                }

                const currentIndex = attachmentCounter++;
                const wrapper = document.createElement('div');
                wrapper.className = 'border-t-2 border-dashed border-slate-200 p-4 space-y-3';
                wrapper.dataset.attachmentRow = currentIndex;

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between gap-2';
                const title = document.createElement('span');
                title.className = 'text-sm font-semibold text-primary-dark';
                let nameInput;
                const updateTitle = () => {
                    const labelValue = nameInput?.value?.trim();
                    title.textContent = labelValue ? `Adjunto: ${labelValue}` : `Adjunto #${currentIndex + 1}`;
                };

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'text-xs font-semibold text-red-500 hover:text-red-600';
                removeButton.textContent = 'Quitar';
                removeButton.addEventListener('click', () => wrapper.remove());

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
                fileInputEl.accept = '.pdf,.jpg,.jpeg,.png,.tiff';
                fileInputEl.className = 'mt-2 w-full border-slate-200';
                fileField.appendChild(fileInputEl);

                const grid = document.createElement('div');
                grid.className = 'grid gap-4 md:grid-cols-2';
                grid.appendChild(nameField);
                grid.appendChild(fileField);
                wrapper.appendChild(grid);

                attachmentList.appendChild(wrapper);
                updateTitle();

                if (focus) {
                    nameInput.focus();
                }
            };

            attachmentAddButton?.addEventListener('click', () => addAttachmentRow({}, true));

            const updateDropzone = (file) => {
                if (! dropLabel || ! dropFilename) {
                    return;
                }
                if (file) {
                    dropFilename.textContent = file.name;
                    dropFilename.classList.remove('hidden');
                    dropLabel.classList.add('border-primary');
                } else {
                    dropFilename.textContent = '';
                    dropFilename.classList.add('hidden');
                    dropLabel.classList.remove('border-primary');
                }
            };

            dropInput?.addEventListener('change', (event) => updateDropzone(event.target.files?.[0]));

            dropLabel?.addEventListener('dragover', (event) => {
                event.preventDefault();
                dropLabel.classList.add('border-primary');
            });

            dropLabel?.addEventListener('dragleave', () => dropLabel.classList.remove('border-primary'));

            dropLabel?.addEventListener('drop', (event) => {
                event.preventDefault();
                const file = event.dataTransfer?.files?.[0];
                if (! file) {
                    return;
                }
                if (! file.name.toLowerCase().endsWith('.doc') && ! file.name.toLowerCase().endsWith('.docx')) {
                    alert('Solo se permiten archivos Word (.doc o .docx).');
                    dropLabel.classList.remove('border-primary');
                    return;
                }
                if (dropInput) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    dropInput.files = dataTransfer.files;
                    updateDropzone(file);
                }
                dropLabel.classList.remove('border-primary');
            });

            if (categoriaSelect) {
                populateSubcategories(categoriaSelect.value, true);
            }

            const zeroForm = document.querySelector('[data-zero-form]');
            const submitButton = zeroForm?.querySelector('[data-submit-button]');

            zeroForm?.addEventListener('submit', () => {
                if (! submitButton) {
                    return;
                }

                submitButton.disabled = true;
                submitButton.classList.add('opacity-70');
                submitButton.innerHTML = '<svg class="me-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>Guardando contrato...';
            });

            // No adjuntos iniciales para mantener el bloque limpio
        });
    </script>
</x-app-layout>
