<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Marcas</p>
                <h1 class="text-2xl font-semibold text-primary-dark">{{ $brand->display_name }}</h1>
                <p class="text-sm text-slate-500">Titular: {{ $brand->display_holder }}</p>
            </div>
            <a href="{{ route('brands.index') }}" class="text-sm text-primary font-semibold">← Volver al listado</a>
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



        <div class="grid gap-6 lg:grid-cols-12">

            <div class="space-y-6 lg:col-span-8">
                <div class="bg-white rounded-2xl shadow-panel p-6 space-y-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-3">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Estado actual</p>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold bg-{{ $brand->status_color }}-50 text-{{ $brand->status_color }}-700">
                                {{ $brand->display_status }}
                            </span>
                            @if ($brand->created_by == auth()->id() && !empty($statusOptions))
                                <form action="{{ route('brands.status.update', $brand) }}" method="POST" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-sm font-semibold text-slate-600">Actualizar estado</label>
                                    <div class="flex flex-wrap gap-2">
                                        <select name="status" class="rounded-xl border-slate-200">
                                            @foreach ($statusOptions as $key => $label)
                                                <option value="{{ $key }}" @selected($brand->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn-lex btn-lex-primary">Guardar</button>
                                    </div>
                                </form>
                            @endif
                        </div>

                    </div>

                    <div class="grid gap-6 md:grid-cols-2 text-sm text-slate-600">
                        <div class="space-y-2">
                            <p><span class="font-semibold">País:</span> {{ $brand->display_country }}</p>
                            <p><span class="font-semibold">Tipo:</span> {{ $brand->display_type }}</p>
                            <p><span class="font-semibold">N.º certificado:</span> {{ $brand->display_registration_number }}</p>
                            <p><span class="font-semibold">Registro:</span> {{ $brand->display_registration_date ?? 'Sin fecha' }}</p>
                            <p><span class="font-semibold">Vencimiento:</span> {{ $brand->display_expiration_date ?? 'Sin fecha' }}</p>
                            @if ($brand->classes->isNotEmpty())
                                <p><span class="font-semibold">Clases:</span> {{ $brand->classes->pluck('number')->map(fn ($num) => 'Clase '.$num)->implode(', ') }}</p>
                            @endif
                            <div class="space-y-4"><hr></div>
                            <p><span class="font-semibold">Creado por:</span> {{ $brand->creator->nombre ?? $brand->creator->email ?? 'N/A' }}</p>
                            <p><span class="font-semibold">Creado el:</span> {{ optional($brand->created_at)->format('d/m/Y H:i') }}</p>
                            <p><span class="font-semibold">Actualizado el:</span> {{ optional($brand->updated_at)->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Imagen</p>
                            @if ($brand->image_path)
                                <img src="{{ asset('storage/'.$brand->image_path) }}" alt="{{ $brand->display_name }}" class="rounded-2xl border border-slate-100 max-h-80 w-full object-contain">
                            @else
                                <div class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-slate-500">
                                    No se ha cargado imagen para esta marca.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-6 lg:col-span-4">
                <section class="bg-white rounded-2xl shadow-panel space-y-4">
                    <div class="px-6 pt-4">
                        <h2 class="text-lg font-semibold text-primary-dark">Comentarios</h2>
                    </div>
                    <div style="background-position:top center;background-image:url({{ asset('assets/images/bg.jpg') }})" class="px-4 py-4 min-h-[220px] max-h-[450px] overflow-y-auto bg-light border-t-2 border-b-2" data-comments-container>
                        @forelse ($brand->comments as $comment)
                            @php $isOwner = auth()->id() === $comment->user_id; @endphp
                            <div class="rounded-2xl border px-4 py-3 my-2 {{ $isOwner ? 'bg-emerald-100 border-emerald-100 ml-6' : 'bg-white border-slate-100 mr-6' }}">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold {{ $isOwner ? 'text-emerald-800' : 'text-slate-800' }}">{{ $comment->user->nombre ?? $comment->user->email ?? 'Usuario' }}</p>
                                    <span class="text-xs text-slate-400">{{ optional($comment->created_at)->diffForHumans() }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ $comment->body }}</p>
                            </div>
                        @empty
                            <div class="flex justify-center p-4">
                                <p class="text-xs uppercase tracking-wider font-semibold bg-slate-100 text-slate-400 border-2 border-dashed border-slate-200 px-4 py-2 rounded-lg">
                                    Sin comentarios registrados
                                </p>
                            </div>
                        @endforelse
                    </div>
                    <form action="{{ route('brands.comments.store', $brand) }}" method="POST" class="flex items-center gap-2 pb-4 px-4">
                        @csrf
                        <textarea name="body" rows="2" class="flex-1 rounded-xl border-slate-200 focus:border-primary focus:ring-primary/40 text-sm" placeholder="Escribe un comentario..." required>{{ old('body') }}</textarea>
                        <button type="submit" class="rounded-full bg-primary text-white p-3" data-loading-button>
                            <span data-loading-text class="hidden">...</span>
                            <svg data-default-text class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-4-4l4 4-4 4" />
                            </svg>
                        </button>
                    </form>
                </section>
            </aside>
        </div>
    </div>

    <script>
        const commentsContainer = document.querySelector('[data-comments-container]');
        if (commentsContainer) {
            commentsContainer.scrollTop = commentsContainer.scrollHeight;
        }
    </script>
</x-app-layout>
