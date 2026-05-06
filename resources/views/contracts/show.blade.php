<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Contrato</p>
                <h1 class="text-2xl font-semibold text-primary-dark">{{ $contract->codigo }} – {{ $contract->titulo }}</h1>
                <p class="text-sm text-slate-500">Estado actual: <span class="font-semibold text-primary">{{ $contract->status_label }}</span></p>
            </div>
            <div class="ms-auto flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="btn-lex btn-lex-secondary text-sm">Volver al panel</a>
            </div>
        </div>
    </x-slot>
    @php
        $formatFieldValue = function ($value) {
            if (is_bool($value)) {
                return $value ? 'Sí' : 'No';
            }
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                try {
                    return \Illuminate\Support\Carbon::parse($value)
                        ->locale('es')
                        ->translatedFormat('d \d\e F \d\e Y');
                } catch (\Throwable $exception) {
                    // ignore parse errors
                }
            }

            return is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };

        $isVersionApproved = $latestVersion && $latestVersion->estado === 'aprobado';
        $isContractApproved = in_array($contract->estado, [
            \App\Models\Contract::STATUS_APPROVED,
            \App\Models\Contract::STATUS_SIGNED,
        ], true);
        $showFinalDocumentSection = $isVersionApproved || $isContractApproved;
    @endphp

    <div class="px-4 py-8 lg:px-10 space-y-6">
        @if (($versions ?? collect())->count() > 0)
            <div class="bg-white rounded-2xl shadow-panel p-4 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 me-2">Versiones:</span>
                @foreach ($versions as $versionMeta)
                    @php
                        $isActive = (int) $versionMeta->id === (int) ($selectedVersionId ?? 0);
                        $latestId = $versions->first()->id ?? null;
                        $versionUrl = ($versionMeta->id === $latestId)
                            ? route('contracts.show', $contract)
                            : route('contracts.versions.show', [$contract, $versionMeta->id]);
                    @endphp
                    <a href="{{ $versionUrl }}"
                       class="px-3 py-1 rounded-full text-xs font-semibold {{ $isActive ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-primary/10 hover:text-primary-dark' }}">
                        V{{ $versionMeta->numero_version }}
                    </a>
                @endforeach
            </div>
        @endif

        @if (! ($isViewingLatestVersion ?? true))
            <div class="bg-amber-50 border border-amber-100 text-amber-900 text-sm rounded-2xl p-4">
                Estás revisando una versión anterior del contrato. Algunas acciones están deshabilitadas.
                <a href="{{ route('contracts.show', $contract) }}" class="underline font-semibold ms-2">Ver versión más reciente</a>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4 lg:col-span-2">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Información general</p>
                    <h2 class="text-xl font-semibold text-primary-dark">{{ $contract->titulo }}</h2>
                </div>
                <dl class="grid gap-4 md:grid-cols-2 text-sm text-slate-600">
                    <div>
                        <dt class="font-semibold text-slate-500">Código del contrato</dt>
                        <dd class="mt-1 text-slate-900">{{ $contract->codigo }}</dd>
                    </div>
                @if ($latestVersion)

                        <div>
                            <dt class="font-semibold text-slate-500">Número de versión</dt>
                            <dd class="mt-1 text-slate-900">{{ $latestVersion->numero_version }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Estado</dt>
                            <dd class="mt-1 text-slate-900 capitalize">{{ str_replace('_', ' ', $latestVersion->estado ?? '—') }}</dd>
                        </div>
                        <!--
                        <div>
                            <dt class="font-semibold text-slate-500">Elaborado por</dt>
                            <dd class="mt-1 text-slate-900">{{ $latestVersion->creator->nombre ?? '—' }}</dd>
                        </div>
                        -->
                @else
                    <p class="text-sm text-slate-500">Aún no hay versiones registradas.</p>
                @endif
                    <div>
                        <dt class="font-semibold text-slate-500">Categoría</dt>
                        <dd class="mt-1 text-slate-900">{{ $contract->category->nombre ?? 'Sin categoría' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Subcategoría</dt>
                        <dd class="mt-1 text-slate-900">{{ $contract->subcategory->nombre ?? 'Sin subcategoría' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Solicitante</dt>
                        <dd class="mt-1 text-slate-900">{{ $contract->creator->nombre ?? $contract->creator->email ?? '—' }}</dd>
                    </div>
    <!--
                    <div>
                        <dt class="font-semibold text-slate-500">Responsable legal</dt>
                        <dd class="mt-1 text-slate-900">{{ $contract->lawyer->nombre ?? 'Por asignar' }}</dd>
                    </div>
    -->
                    <div>
                        <dt class="font-semibold text-slate-500">Asesor asignado</dt>
                        <dd class="mt-1 text-slate-900">{{ $contract->advisor->nombre ?? $contract->advisor->email ?? 'No asignado' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Creado el</dt>
                        <dd class="mt-1 text-slate-900">{{ optional($contract->created_at)->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Última actualización</dt>
                        <dd class="mt-1 text-slate-900">{{ optional($contract->updated_at)->diffForHumans() ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($canAssignAdvisor ?? false)
                    <div class="mt-6 border-t border-slate-100 pt-4">
                        <form action="{{ route('contracts.advisor.update', $contract) }}" method="POST" class="grid gap-3 md:grid-cols-3 md:items-end">
                            @csrf
                            @method('PATCH')
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold text-slate-600">Seleccionar asesor</label>
                                <select name="asesor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                    <option value="">Sin asignar</option>
                                    @foreach ($advisors as $advisor)
                                        <option value="{{ $advisor->id }}" @selected($contract->asesor_id == $advisor->id)>
                                            {{ $advisor->nombre ?? $advisor->email }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('asesor_id')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex md:justify-end">
                                <button type="submit" class="btn-lex btn-lex-primary text-sm" data-loading-button>
                                    <span data-loading-text class="hidden">Guardando...</span>
                                    <span data-default-text>Guardar asesor</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>

            <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">

                @if ($latestVersion)
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Formulario</p>
                        <h2 class="text-xl font-semibold text-primary-dark">Datos capturados</h2>
                    </div>
                    @if ($latestVersion && ! empty($latestVersion->form_payload))
                        <dl class="grid gap-3 sm:grid-cols-2 text-sm text-slate-700">
                            @foreach ($latestVersion->form_payload as $key => $value)
                                <div class="flex flex-col gap-1">
                                    <dt class="text-xs font-semibold uppercase- tracking-[0.2em]- text-slate-400">{{ ucwords(str_replace(['_', '-'], ' ', $key)) }}</dt>
                                    <dd class="text-sm text-slate-900">{{ $formatFieldValue($value) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-slate-500">No se registraron datos dinámicos para esta versión.</p>
                    @endif

@if (! empty($attachments))
      <div class="pt-4 border-t border-slate-100 space-y-3">
          <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-primary-dark">Adjuntos</h3>

              @if ($canUploadDocument)
                  <button type="button"
                          class="text-xs font-semibold text-primary hover:text-primary-dark"
                          data-open-attachments>
                      Subir más adjuntos
                  </button>
              @endif
          </div>

          <ul class="space-y-2 text-sm text-primary">
              @foreach ($attachments as $attachment)
                  <li class="flex items-center justify-between gap-2 border-t-2 border-dashed border-slate-100 pt-2">
                      <div>
  @php
      $downloadUrl = route('contracts.attachments.download', [
          $contract,
          'path' => $attachment['path'] ?? null,
      ]);
  @endphp

  @if (! empty($attachment['path']))
      <a href="{{ $downloadUrl }}"
         class="font-semibold hover:text-primary-dark">
          {{ $attachment['name'] ?? 'Adjunto' }}
      </a>
  @else
      <span class="text-slate-600">{{ $attachment['name'] ?? 'Adjunto' }}</span>
  @endif
                      </div>

                      @if ($canUploadDocument && ! empty($attachment['path']))
                          <form method="POST"
                                action="{{ route('contracts.attachments.destroy', $contract) }}"
                                onsubmit="return confirm('¿Eliminar este adjunto?');">
                              @csrf
                              @method('DELETE')
                              <input type="hidden" name="attachment_path" value="{{ $attachment['path'] }}">
                              <button type="submit" class="text-xs text-rose-600 hover:text-rose-700">
                                  Quitar
                              </button>
                          </form>
                      @endif
                  </li>
              @endforeach
          </ul>
      </div>
  @else
      @if ($canUploadDocument)
          <div class="pt-4 border-t border-slate-100">
              <button type="button"
                      class="text-xs font-semibold text-primary hover:text-primary-dark"
                      data-open-attachments>
                  Subir adjuntos
              </button>
          </div>
      @endif
  @endif

 @if ($canUploadDocument)
 @push('modals')
  <div class="fixed inset-0 z-50 hidden" data-attachments-modal>
      <div class="absolute inset-0 bg-slate-900/60" data-attachments-close></div>
      <div class="relative z-10 mx-auto mt-10 w-full max-w-2xl px-4">
          <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
              <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                  <div>
                      <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Adjuntos</p>
                      <h3 class="text-lg font-semibold text-primary-dark">Agregar archivos</h3>
                  </div>
                  <button type="button" class="text-slate-400 hover:text-slate-600" data-attachments-close>
                      <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <path fill-rule="evenodd" d="M10 8.586 4.293 2.879A1 1 0 1 0 2.879 4.293L8.586 10l-5.707 5.707a1 1 0 1 0 1.414 1.414L10 11.414l5.707
  5.707a1 1 0 0 0 1.414-1.414L11.414 10l5.707-5.707a1 1 0 0 0-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                      </svg>
                  </button>
              </div>

              <form method="POST"
                    action="{{ route('contracts.attachments.store', $contract) }}"
                    enctype="multipart/form-data"
                    class="px-6 py-6 space-y-4"
                    data-attachments-form>
                  @csrf
                  <div class="mb-2">
                      <button type="button"
                              class="text-sm font-semibold text-primary hover:text-primary-dark"
                              data-attachments-add>
                          + Añadir archivo
                      </button>
                  </div>
                  <div class="space-y-4" data-attachments-list></div>

                  <div class="flex items-center justify-end">
                      <div class="flex items-center gap-2">
                          <button type="button"
                                  class="px-4 py-2 text-sm text-slate-500 hover:text-primary"
                                  data-attachments-close>
                              Cancelar
                          </button>
                          <button type="submit" class="btn-lex btn-lex-primary text-sm">
                              Guardar adjuntos
                          </button>
                      </div>
                  </div>
              </form>
          </div>
      </div>
  </div>
  @endpush
  @endif


                @endif
  <datalist id="document_suggestions">
      @foreach(__('custom.requirements.items') as $text)
          <option value="{{ $text }}">
      @endforeach
  </datalist>
            </section>
        </div>

        @if ($latestVersion)
            @php
                $documentLinkable = $canDownloadDocument && $latestDocumentHistory;
                $revisionCount = ($documentHistories ?? collect())->count();
            @endphp
            <div class="grid gap-6 lg:grid-cols-3">
                <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Documento</p>
                            @if (! $showFinalDocumentSection)
                                <h2 class="text-xl font-semibold text-primary-dark">Revisión actual {{ $revisionCount }}</h2>
                                <p class="text-sm text-slate-500">Descarga el archivo, edítalo en Word con control de cambios y vuelve a subirlo.</p>
                            @else
                                <h2 class="text-xl font-semibold text-primary-dark">Documento final aprobado</h2>
                                <p class="text-sm text-slate-500">Descarga el Word definitivo o registra el PDF firmado.</p>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-4 text-sm text-slate-600">
                        @if (! $showFinalDocumentSection)
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 flex items-center gap-4">
                                @if ($documentLinkable)
                                    <a href="{{ route('contracts.documents.download', [$contract, $latestDocumentHistory]) }}" target="_blank" rel="noopener" class="flex items-center gap-4 flex-1 min-w-0 group">
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('assets/images/icon-word.png') }}" alt="Word" class="w-14 h-14 object-contain" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 group-hover:text-primary-dark">{{ $contract->titulo }}</p>
                                            <p class="text-xs text-slate-500">{{ $contract->codigo }} · Versión {{ $latestVersion->numero_version }} · Revisión: {{ $revisionCount }}</p>
                                            <p class="text-xs text-slate-500">Última actualización {{ optional($latestVersion->updated_at)->format('d/m/Y H:i') ?? 'sin registro' }}</p>
                                        </div>
                                    </a>
                                @else
                                    <div class="flex items-center gap-4 flex-1 min-w-0">
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('assets/images/icon-word.png') }}" alt="Word" class="w-14 h-14 object-contain opacity-80" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-slate-800">{{ $contract->titulo }}</p>
                                            <p class="text-xs text-slate-500">{{ $contract->codigo }} · Versión {{ $latestVersion->numero_version }} · Revisión: {{ $revisionCount }}</p>
                                            <p class="text-xs text-slate-500">Última actualización {{ optional($latestVersion->updated_at)->format('d/m/Y H:i') ?? 'sin registro' }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @if ($canUploadDocument)
                                <form method="POST" action="{{ route('contracts.versions.store', $contract) }}" enctype="multipart/form-data" class="space-y-4" data-version-form>
                                    @csrf
                                    <div>
                                        <label class="text-sm font-semibold text-slate-700">Subir nueva revisión (.docx)</label>
                                        <input type="file" name="document" accept=".doc,.docx" required class="mt-2 w-full text-sm" />
                                        @error('document')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn-lex btn-lex-primary text-sm" data-version-submit>
                                        Guardar nueva revisión
                                    </button>
                                </form>
                            @else
                                <p class="text-sm text-slate-500">Solo quienes participan en el contrato pueden subir una nueva revisión.</p>
                            @endif
                        @else
                            <!--
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 flex items-center gap-4">
                                @if ($hasFinalDocument && $canDownloadDocument)
                                    <a href="{{ route('contracts.documents.final', $contract) }}" target="_blank" rel="noopener" class="flex items-center gap-4 flex-1 min-w-0 group">
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('assets/images/icon-word.png') }}" alt="Word" class="w-14 h-14 object-contain" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 group-hover:text-primary-dark">{{ $contract->titulo }}</p>
                                            <p class="text-xs text-slate-500">{{ $contract->codigo }} · Versión {{ $latestVersion->numero_version ?? '—' }}</p>
                                            <p class="text-xs text-slate-500">Archivo final listo para firma</p>
                                        </div>
                                    </a>
                                @else
                                    <div class="flex items-center gap-4 flex-1 min-w-0">
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('assets/images/icon-word.png') }}" alt="Word" class="w-14 h-14 object-contain opacity-80" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-slate-800">{{ $contract->titulo }}</p>
                                            <p class="text-xs text-slate-500">El Word final aún no está disponible.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            -->

                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 flex items-center gap-4">
                                @if ($hasSignedDocument && $canDownloadDocument)
                                    <a href="{{ route('contracts.documents.signed', $contract) }}" target="_blank" rel="noopener" class="flex items-center gap-4 flex-1 min-w-0 group">
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('assets/images/icon-pdf.png') }}" alt="PDF" class="w-14 h-14 object-contain" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 group-hover:text-primary-dark">{{ $contract->titulo }}</p>
                                            <p class="text-xs text-slate-500">{{ $contract->codigo }} · PDF firmado disponible</p>
                                        </div>
                                    </a>
                                @else
                                    <a href="{{ route('contracts.documents.approved', $contract) }}" target="_blank" rel="noopener" class="flex items-center gap-4 flex-1 min-w-0">
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('assets/images/icon-word.png') }}" alt="PDF" class="w-14 h-14 object-contain opacity-70" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 group-hover:text-primary-dark">{{ $contract->titulo }}</p>
                                            <p class="text-xs text-slate-500">{{ $contract->codigo }} · Versión {{ $latestVersion->numero_version }}</p>
                                        </div>
                                    </a>
                                @endif
                            </div>

                            @if ($canUploadSignedDocument)
                                <div class="py-3">
                                    <hr>
                                </div>
                                <form method="POST" action="{{ route('contracts.documents.signed.store', $contract) }}" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="text-sm font-semibold text-slate-700">Subir contrato firmado (.pdf)</label>
                                        <input type="file" name="signed_document" accept=".pdf" required class="mt-2 w-full rounded-xl border-slate-200 text-sm" />
                                        @error('signed_document')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="submit" class="btn-lex btn-lex-primary text-sm flex-1">Guardar</button>
                                    </div>
                                </form>

                                @if ($canObserveContract ?? false)
                                    <form method="POST" action="{{ route('contracts.observe', $contract) }}" class="mt-3" onsubmit="return confirm('Esto creará una nueva versión observada, ¿continuar?');">
                                        @csrf
                                        <button type="submit" class="btn-lex btn-lex-secondary text-sm w-full">Observar</button>
                                    </form>
                                @endif
                            @endif
                        @endif
                    </div>
                </section>

                <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Historial</p>
                        <h2 class="text-xl font-semibold text-primary-dark">Revisiones recientes</h2>
                    </div>
                    @php
                        $totalRevisions = ($documentHistories ?? collect())->count();
                    @endphp
                    @if ($totalRevisions > 0)
                        <ul class="divide-y divide-slate-100 text-sm max-h-96 overflow-y-auto">
                            @foreach ($documentHistories as $history)
                                @php
                                    $revisionNumber = $totalRevisions - $loop->index;
                                    $isVendorReady = (bool) $history->is_ready_for_vendor_review;
                                    $vendorReadyClasses = $isVendorReady
                                        ? 'text-emerald-600'
                                        : 'text-slate-400';
                                @endphp
                                <li class="py-3 flex flex-wrap items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-800">Revisión {{ $revisionNumber }}</p>
                                        @if (auth()->user()?->hasRole('abogado'))
                                            <form method="POST" action="{{ route('contracts.documents.vendor-review-ready', [$contract, $history]) }}" class="mt-1">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex items-center gap-2 text-xs {{ $vendorReadyClasses }} hover:text-emerald-700 transition-colors">
                                                    <span aria-hidden="true">{{ $isVendorReady ? '●' : '○' }}</span>
                                                    <span>Conforme para el envío al proveedor</span>
                                                </button>
                                            </form>
                                        @else
                                            <p class="mt-1 inline-flex items-center gap-2 text-xs {{ $vendorReadyClasses }}">
                                                <span aria-hidden="true">{{ $isVendorReady ? '●' : '○' }}</span>
                                                <span>Conforme para el envío al proveedor</span>
                                            </p>
                                        @endif
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ optional($history->created_at)->format('d/m/Y H:i') ?? 'sin fecha' }} ·
                                            {{ $history->uploader->nombre ?? $history->uploader->email ?? 'Usuario' }}
                                        </p>
                                    </div>
                                    @if ($canDownloadDocument)
                                        <a href="{{ route('contracts.documents.download', [$contract, $history]) }}" class="text-xs font-semibold text-primary hover:text-primary-dark">Descargar</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-slate-500">Aún no hay revisiones almacenadas para este documento.</p>
                    @endif
                </section>

        <section class="bg-white rounded-2xl shadow-panel space-y-4">
            <div class="pt-4 px-4">
                <!--<p class="text-xs uppercase tracking-[0.3em] text-slate-400">Notas</p>-->
                <h2 class="text-xl font-semibold text-primary-dark">COMENTARIOS</h2>
            </div>

            <div style="background-position:top center;background-image:url({{ asset('assets/images/bg.jpg') }})" class="p-4 min-h-[220px] max-h-[350px] overflow-y-auto bg-light border-t-2 border-b-2" data-comments-container>
                @php $comments = $contract->comments()->with('user')->latest()->take(40)->get()->reverse(); @endphp
                @forelse ($comments as $comment)
                    @php $isOwner = auth()->id() == $comment->user_id; @endphp
                    <div class="rounded-2xl border px-4 my-2 py-3 {{ $isOwner ? 'bg-emerald-100 border-emerald-100 ml-10' : 'bg-slate-50 border-slate-100 mr-10' }}">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold {{ $isOwner ? 'text-emerald-800' : 'text-slate-800' }}">{{ $comment->user->nombre ?? $comment->user->email }}</p>
                            <span class="text-xs text-slate-400">{{ optional($comment->created_at)->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-slate-600 mt-2">{{ $comment->mensaje }}</p>
                    </div>
                @empty
                    <div class="flex justify-center p-4">
                        <p class="text-xs uppercase tracking-wider font-semibold bg-slate-100 text-slate-400 border-2 border-dashed border-slate-200 px-4 py-2 rounded-lg">
                            Sin comentarios registrados
                        </p>
                    </div>
                @endforelse
            </div>

            <form action="{{ route('contracts.comments.store', $contract) }}" method="POST" class="flex items-center gap-1 pb-4 px-4">
                @csrf
                <input type="hidden" name="contract_version_id" value="{{ $latestVersion->id ?? '' }}">
                <div class="flex-1">
                    <textarea name="mensaje" rows="2" class="w-full rounded-xl border-slate-200 focus:border-primary focus:ring-primary/40 text-sm" placeholder="Escribe un comentario..." required></textarea>
                </div>
                <button type="submit" class="rounded-full bg-primary text-white p-3" data-loading-button>
                    <span data-loading-text class="hidden">...</span>
                    <svg data-default-text class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-4-4l4 4-4 4" />
                    </svg>
                </button>
            </form>
        </section>
            </div>
        @endif


    <div class="grid grid-cols-12 gap-6">
        @if ($userCanConfigure)
        <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4 col-span-12 lg:col-span-4">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Aprobadores</p>
                <h2 class="text-xl font-semibold text-primary-dark">Asignar Aprobadores</h2>
            </div>
            
                <form action="{{ route('contracts.approvals.update', $contract) }}" method="POST" class="space-y-4" data-approver-form>
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-600" for="approver-select">Seleccionar aprobadores</label>
                        <input type="text" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="Buscar por nombre o departamento" data-filter-input="approver-select">
                        <select id="approver-select" name="approvers[]" multiple size="6" class="mt-2 w-full rounded-xl border-slate-200 focus:border-primary focus:ring-primary/40" {{ $approverOptions->isEmpty() ? 'disabled' : '' }}>
                            @forelse ($approverOptions as $approver)
                                @php
                                    $label = ($approver->nombre ?? $approver->email).' — '.($approver->department->nombre ?? 'Sin departamento');
                                @endphp
                                <option value="{{ $approver->id }}" data-search="{{ strtolower($label) }}">
                                    {{ $label }}
                                </option>
                            @empty
                                <option value="" disabled>No hay aprobadores disponibles para agregar</option>
                            @endforelse
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Solo se muestran los aprobadores que aún no forman parte del flujo.</p>
                    </div>

                    @foreach ($contract->signers as $signer)
                        <input type="hidden" name="signers[]" value="{{ $signer->user_id }}">
                    @endforeach

                    <div class="flex justify-between">
                        <p class="text-xs text-slate-500 mt-2">Usa CTRL/CMD para seleccionar varios y agregar nuevos aprobadores.</p>
                        <button type="submit" class="btn-lex btn-lex-primary text-sm" data-loading-button>
                            <span data-loading-text class="hidden">Guardando...</span>
                            <span data-default-text>Guardar</span>
                        </button>
                    </div>
                </form>
            
        </section>
        @endif

        <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4 col-span-12 {{ $userCanConfigure ? 'lg:col-span-8' : 'lg:col-span-12' }}">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Aprobadores</p>
                <h2 class="text-xl font-semibold text-primary-dark">Flujo de aprobación</h2>
            </div>

            @php
                $currentUser = auth()->user();
            @endphp
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($latestApprovals as $approval)
                    @php
                        $assignmentDate = $approval->assigned_at ?? $approval->created_at;
                        $canApproveContract = $currentUser && ((int) $currentUser->id) === (int) $approval->user_id && $approval->estado !== 'aprobado';
                        $canRemoveApprover = $currentUser?->hasRole('abogado') && $approval->estado !== 'aprobado';
                    @endphp
                    <div class="rounded-2xl border border-slate-100- p-4 h-full relative">
                        @if ($canRemoveApprover)
                            <form method="POST" action="{{ route('contracts.approvals.destroy', [$contract, $approval]) }}" class="absolute top-2 right-2" style="right:8px; top: 8px">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-red-600" title="Eliminar aprobador" onclick="return confirm('¿Eliminar este aprobador del flujo?');">
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.5 3a1 1 0 00-.894.553L7.118 5H4a1 1 0 100 2h.167l.637 9.163A2 2 0 006.8 18h6.4a2 2 0 001.996-1.837L15.833 7H16a1 1 0 100-2h-3.118l-.488-1.447A1 1 0 0011.5 3h-3zm-1.4 5.2a1 1 0 112 0v6.6a1 1 0 11-2 0V8.2zm3.8 0a1 1 0 112 0v6.6a1 1 0 11-2 0V8.2z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>
                        @endif
                        <p class="text-sm font-semibold text-slate-800">{{ $approval->user->nombre ?? $approval->user->email }}</p>
                        <p class="text-xs text-slate-500 mt-1">Estado: <span class="font-semibold text-primary">{{ ucfirst($approval->estado) }}</span></p>
                        @if ($approval->aprobado_at)
                            <p class="text-xs text-emerald-600 mt-1">Aprobado {{ $approval->aprobado_at->diffForHumans() }}</p>
                        @else
                            <p class="text-xs text-slate-400 mt-1">Pendiente de revisión</p>
                        @endif
                        @if ($assignmentDate)
                            <p class="text-xs text-slate-400 mt-1">Asignado el {{ $assignmentDate->format('d/m/Y H:i') }}</p>
                        @endif
                        @if (! $approval->aprobado_at && $assignmentDate)
                            <p class="text-xs text-amber-600 mt-1">Pendiente durante {{ $assignmentDate->diffForHumans(now(), true) }}</p>
                        @endif

                        @if ($canApproveContract)
                            <form action="{{ route('contracts.approvals.approve', [$contract, $approval]) }}" method="POST" class="mt-4">
                                @csrf
                                <button type="submit" class="btn-lex btn-lex-primary text-xs w-full justify-center" data-loading-button>
                                    <span data-loading-text class="hidden">Aprobando...</span>
                                    <span data-default-text>Aprobar</span>
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay aprobadores asignados para la versión actual.</p>
                @endforelse
            </div>
        </section>
    </div>

    <!--
        <section class="bg-white rounded-2xl shadow-panel p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Firmantes</p>
                    <h2 class="text-xl font-semibold text-primary-dark">Usuarios que deben firmar</h2>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($contract->signers as $signer)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ $signer->user->nombre ?? $signer->user->email }}</p>
                        <p class="text-xs text-slate-500 mt-1">Estado: {{ ucfirst($signer->estado) }}</p>
                        @if ($signer->firmado_at)
                            <p class="text-xs text-emerald-600 mt-1">Firmado {{ $signer->firmado_at->diffForHumans() }}</p>
                        @endif
                        @if ($signer->observaciones)
                            <p class="text-xs text-slate-500 mt-1">{{ $signer->observaciones }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aún no se han designado firmantes.</p>
                @endforelse
            </div>
        </section>

    -->

    </div>
</x-app-layout>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const versionForm = document.querySelector('[data-version-form]');
            const versionSubmit = document.querySelector('[data-version-submit]');

            versionForm?.addEventListener('submit', () => {
                if (! versionSubmit) {
                    return;
                }

                versionSubmit.disabled = true;
                versionSubmit.classList.add('opacity-70');
                versionSubmit.innerHTML = '<svg class="me-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>Guardando revisión...';
            });
        const filterInput = document.querySelector('[data-filter-input="approver-select"]');
        const approverSelect = document.getElementById('approver-select');

        if (filterInput && approverSelect) {
            filterInput.addEventListener('input', () => {
                const term = filterInput.value.trim().toLowerCase();
                Array.from(approverSelect.options).forEach((option) => {
                    const haystack = option.dataset.search || option.textContent.toLowerCase();
                    option.hidden = term && !haystack.includes(term);
                });
            });
        }

        document.querySelectorAll('form button[data-loading-button]').forEach((button) => {
            const form = button.closest('form');
            if (!form) {
                return;
            }

            form.addEventListener('submit', () => {
                button.disabled = true;
                const defaultNode = button.querySelector('[data-default-text]');
                const loadingNode = button.querySelector('[data-loading-text]');
                if (defaultNode && loadingNode) {
                    defaultNode.classList.add('hidden');
                    loadingNode.classList.remove('hidden');
                }
            });
        });

        const commentsContainer = document.querySelector('[data-comments-container]');
        if (commentsContainer) {
            commentsContainer.scrollTop = commentsContainer.scrollHeight;
        }
    });

//Modal + adjuntos
  const attachmentsModal = document.querySelector('[data-attachments-modal]');
  const openAttachments = document.querySelectorAll('[data-open-attachments]');
  const closeAttachments = document.querySelectorAll('[data-attachments-close]');
  const attachmentsList = attachmentsModal?.querySelector('[data-attachments-list]');
  const addAttachmentButton = attachmentsModal?.querySelector('[data-attachments-add]');
  let attachmentIndex = 0;

  const toggleAttachmentsModal = (show = false) => {
      if (!attachmentsModal) return;
      attachmentsModal.classList.toggle('hidden', !show);

      if (show && attachmentsList && attachmentsList.childElementCount === 0) {
          addAttachmentRow();
      }
  };

  const addAttachmentRow = () => {
      if (!attachmentsList) return;

      const currentIndex = attachmentIndex++;
      const wrapper = document.createElement('div');
      wrapper.className = 'border-t-2 border-dashed border-slate-200 py-4 space-y-3';
      wrapper.innerHTML = `
          <div class="flex items-center justify-between gap-2">
              <input type="text"
                     name="attachments[${currentIndex}][name]"
                     class="mt-1 w-full rounded-xl border-slate-200"
                     placeholder="Descripción del archivo"
                     required
                     list="document_suggestions">
              <input type="file"
                     name="attachments[${currentIndex}][file]"
                     class="mt-1 w-full border-slate-200"
                     accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.tiff"
                     required>
              <button type="button" class="text-xs text-rose-600 hover:text-rose-700" data-attachment-remove>
                  Quitar
              </button>
          </div>
      `;

      wrapper.querySelector('[data-attachment-remove]').addEventListener('click', () => {
          wrapper.remove();
          if (attachmentsList.childElementCount === 0) {
             addAttachmentRow();
          }
      });

      attachmentsList.appendChild(wrapper);
  };

  openAttachments.forEach((button) => button.addEventListener('click', () => toggleAttachmentsModal(true)));
  closeAttachments.forEach((button) => button.addEventListener('click', () => toggleAttachmentsModal(false)));
  addAttachmentButton?.addEventListener('click', () => addAttachmentRow());


</script>
