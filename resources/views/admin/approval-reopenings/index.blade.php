<x-app-layout>
    <x-slot name="header">
        <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Administración · Solo admin</p>
        <h1 class="text-2xl font-semibold text-primary-dark">Correcciones de aprobaciones</h1>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Corrección excepcional, previa coordinación con un abogado. Solo permite devolver una aprobación individual
            a pendiente mientras el contrato continúa en aprobación. No elimina documentos, no crea versiones
            ni retrocede a una versión anterior. Los contratos ya aprobados o con evidencia de firma quedan excluidos.
        </div>
        <form action="{{ route('admin.approval-reopenings.index') }}" method="GET" class="flex flex-wrap gap-3">
            <label class="flex-1 min-w-0">
                <span class="text-sm font-semibold">Buscar por ID, código o título del contrato</span>
                <input name="q" value="{{ $search }}" maxlength="120" class="mt-1 w-full rounded-xl border-slate-200">
            </label>
            <button class="btn-lex btn-lex-primary self-end" type="submit">Buscar</button>
        </form>

        @forelse ($contracts as $contract)
            <section class="rounded-2xl bg-white p-6 shadow-panel space-y-5">
                <div>
                    <h2 class="text-lg font-semibold">{{ $contract->codigo }} · {{ $contract->titulo }}</h2>
                    <p class="text-sm text-slate-500">ID {{ $contract->id }} · Versión {{ $contract->latestVersion->numero_version }} · {{ $contract->status_label }}</p>
                    <a href="{{ route('contracts.show', $contract) }}" class="text-sm text-primary underline">Ver contrato</a>
                </div>
                @foreach ($contract->latestVersion->approvals->where('estado', 'aprobado')->filter(fn ($approval) => $approval->aprobado_at) as $approval)
                    @php($restore = (string) old('selected_approval') === (string) $approval->id)
                    <details class="rounded-xl border border-slate-200 p-4">
                        <summary class="cursor-pointer font-semibold">
                            {{ $approval->user?->nombre ?? $approval->user?->email ?? 'Usuario no disponible' }}
                            <span class="text-sm font-normal">· Aprobó el {{ $approval->aprobado_at->format('d/m/Y H:i') }}</span>
                        </summary>
                        <form method="POST" action="{{ route('admin.approval-reopenings.store', [$contract, $approval]) }}" class="mt-4 space-y-4"
                              onsubmit="return confirm('¿Reabrir únicamente esta aprobación? El documento y la versión no cambiarán. La acción quedará auditada.');">
                            @csrf
                            <input type="hidden" name="selected_approval" value="{{ $approval->id }}">
                            <input type="hidden" name="version_id" value="{{ $contract->latestVersion->id }}">
                            <input type="hidden" name="approval_date" value="{{ $approval->aprobado_at->toISOString() }}">
                            <label class="block text-sm font-semibold">Abogado con quien se coordinó
                                <select name="lawyer_id" required class="mt-1 w-full rounded-xl border-slate-200">
                                    <option value="">Seleccionar abogado</option>
                                    @foreach ($lawyers as $lawyer)
                                        <option value="{{ $lawyer->id }}" @selected($restore && old('lawyer_id') == $lawyer->id)>{{ $lawyer->nombre ?? $lawyer->email }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm font-semibold">Motivo de la corrección
                                <textarea name="reason" required minlength="10" maxlength="2000" rows="3" class="mt-1 w-full rounded-xl border-slate-200">{{ $restore ? old('reason') : '' }}</textarea>
                            </label>
                            <label class="block text-sm font-semibold">Referencia de coordinación (correo, ticket u observación)
                                <textarea name="coordination_reference" required minlength="5" maxlength="1000" rows="2" class="mt-1 w-full rounded-xl border-slate-200">{{ $restore ? old('coordination_reference') : '' }}</textarea>
                            </label>
                            <label class="flex items-start gap-2 text-sm">
                                <input name="confirmation" value="1" type="checkbox" required class="mt-1 rounded border-slate-300">
                                Confirmo que coordiné esta corrección con el abogado y que el aprobador deberá revisar nuevamente la misma versión.
                            </label>
                            <button type="submit" class="btn-lex btn-lex-primary">Reabrir aprobación individual</button>
                        </form>
                    </details>
                @endforeach
            </section>
        @empty
            <div class="rounded-2xl bg-white p-6 text-slate-600">No hay contratos elegibles para esta corrección. Solo se muestran contratos aún en aprobación, con decisiones aprobadas y pendientes en la versión vigente, sin evidencia de firma.</div>
        @endforelse
        {{ $contracts->links() }}

        <section class="rounded-2xl bg-white p-6 shadow-panel space-y-4">
            <h2 class="text-lg font-semibold">Bitácora de reaperturas (solo lectura)</h2>
            <p class="text-sm text-slate-500">Últimas correcciones de todos los contratos, incluso si posteriormente completaron su aprobación.</p>
            @forelse ($reopenings as $event)
                <article class="border-t border-slate-100 pt-4 text-sm space-y-1">
                    <p class="font-semibold">{{ $event->snapshot['contract_code'] }} · ID {{ $event->contract_id }} · Versión {{ $event->version_number }}</p>
                    <p>Aprobador: {{ $event->snapshot['approver'] }} · Aprobación original: {{ $event->previous_approved_at->format('d/m/Y H:i') }}</p>
                    <p>Reabierto por {{ $event->snapshot['admin'] }} el {{ $event->created_at->format('d/m/Y H:i') }} · Coordinado con {{ $event->snapshot['lawyer'] }}</p>
                    <p class="whitespace-pre-wrap">Motivo: {{ $event->reason }}</p>
                    <p class="whitespace-pre-wrap">Coordinación: {{ $event->coordination_reference }}</p>
                </article>
            @empty
                <p class="text-sm text-slate-500">No se han registrado reaperturas.</p>
            @endforelse
            {{ $reopenings->links() }}
        </section>
    </div>
</x-app-layout>
