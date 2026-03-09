<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Marcas</p>
                <h1 class="text-2xl font-semibold text-primary-dark">Crear nueva registro</h1>
            </div>
            <a href="{{ route('brands.index') }}" class="text-sm text-primary font-semibold">← Volver al listado</a>
        </div>
    </x-slot>

    <div class="px-4 py-8 lg:px-10">
        <div class="max-w-5xl mx-auto space-y-6">
            @if ($errors->any())
                <div class="bg-rose-50 text-rose-700 border border-rose-200 rounded-2xl px-4 py-3 space-y-1">
                    <p class="font-semibold">No se pudo registrar la solicitud:</p>
                    <ul class="text-sm list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-panel p-8 space-y-6">
                <div>
                    <p class="text-sm text-slate-500">Completa la información principal de la solicitud. El estado se establecerá automáticamente en "En trámite" y podrás actualizarlo luego.</p>
                </div>

                <form action="{{ route('brands.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Nombre de la marca</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Ej: Medifarma" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Titular</label>
                            <input type="text" name="holder" value="{{ old('holder') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Empresa titular" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">País de registro</label>
                            <select name="brand_country_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                                <option value="">Selecciona una opción</option>
                                @foreach ($brandCountries as $country)
                                    <option value="{{ $country->id }}" @selected(old('brand_country_id') == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Tipo de marca</label>
                            <select name="brand_type_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                                <option value="">Selecciona una opción</option>
                                @foreach ($brandTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('brand_type_id') == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">N.º de certificado</label>
                            <input type="text" name="certificate_number" value="{{ old('certificate_number') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Ej: 123456">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Imagen (opcional)</label>
                            <input type="file" name="image" accept="image/*" class="mt-1 w-full rounded-xl border-slate-200">
                            <p class="text-xs text-slate-500 mt-1">Formatos: JPG, PNG hasta 2MB.</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Fecha de registro</label>
                            <input type="date" name="registration_date" value="{{ old('registration_date') }}" class="mt-1 w-full rounded-xl border-slate-200">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Inicio de trámite</label>
                            <input type="date" name="process_start_date" value="{{ old('process_start_date') }}" class="mt-1 w-full rounded-xl border-slate-200">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Inicio de uso</label>
                            <input type="date" name="usage_start_date" value="{{ old('usage_start_date') }}" class="mt-1 w-full rounded-xl border-slate-200">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Vencimiento</label>
                            <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" class="mt-1 w-full rounded-xl border-slate-200">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-600">Clases de Niza</label>
                        <p class="text-xs text-slate-500">Selecciona todas las clases asociadas a esta solicitud.</p>
                        <div class="grid gap-3 md:grid-cols-3">
                            @foreach ($brandClasses as $class)
                                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 hover:border-primary/50">
                                    <input type="checkbox" name="brand_class_ids[]" value="{{ $class->id }}" class="mt-1" @checked(collect(old('brand_class_ids', []))->contains($class->id))>
                                    <div>
                                        <p class="font-semibold text-slate-700">Clase {{ $class->number }}</p>
                                        <p class="text-xs text-slate-500">{{ $class->description }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('brands.index') }}" class="text-sm text-slate-500 hover:text-primary">Cancelar</a>
                        <button type="submit" class="btn-lex btn-lex-primary">Registrar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
