<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dateTime('unlocked_at')->nullable();
            $table->unsignedBigInteger('enabled_seconds')->default(0);
        });

        // There is no historical lock ledger: start measuring current assignments now.
        DB::table('approvals')->where('is_unlocked', true)->where('estado', 'pendiente')->whereNull('aprobado_at')
            ->whereIn('version_id', function ($query) {
                $query->select('contract_versions.id')->from('contract_versions')
                    ->join('contracts', 'contracts.id', '=', 'contract_versions.contract_id')
                    ->whereNull('contract_versions.deleted_at')->whereNull('contracts.deleted_at')
                    ->where('contract_versions.estado', '!=', 'aprobado')
                    ->whereIn('contracts.estado', ['creado', 'asignado', 'en_aprobacion', 'observado'])
                    ->where(fn ($q) => $q->whereNull('contracts.document_signed')->orWhere('contracts.document_signed', ''))
                    ->whereNotExists(function ($newer) {
                        $newer->select(DB::raw(1))->from('contract_versions as newer')
                            ->whereColumn('newer.contract_id', 'contract_versions.contract_id')->whereNull('newer.deleted_at')
                            ->where(fn ($q) => $q->whereColumn('newer.numero_version', '>', 'contract_versions.numero_version')
                                ->orWhere(fn ($tie) => $tie->whereColumn('newer.numero_version', 'contract_versions.numero_version')->whereColumn('newer.id', '>', 'contract_versions.id')));
                    })
                    ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('contract_signers')
                        ->whereColumn('contract_signers.contract_id', 'contracts.id')
                        ->where(fn ($s) => $s->whereNotNull('firmado_at')->orWhere('estado', 'firmado')))
                    ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('signatures')
                        ->join('contract_versions as signed_versions', 'signed_versions.id', '=', 'signatures.version_id')
                        ->whereColumn('signed_versions.contract_id', 'contracts.id')
                        ->where(fn ($s) => $s->where('signatures.firmado', true)->orWhereNotNull('signatures.firmado_at')
                            ->orWhere(fn ($d) => $d->whereNotNull('signatures.documento_firmado')->where('signatures.documento_firmado', '!=', ''))));
            })->update(['unlocked_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('approvals', fn (Blueprint $table) => $table->dropColumn(['unlocked_at', 'enabled_seconds']));
    }
};
