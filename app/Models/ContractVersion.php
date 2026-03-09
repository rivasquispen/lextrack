<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'template_id',
        'numero_version',
        'documento',
        'creado_por',
        'comentarios',
        'estado',
        'form_payload',
        'attachments',
    ];

    protected $casts = [
        'form_payload' => 'array',
        'attachments' => 'array',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'version_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ContractVersionHistory::class, 'contract_version_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContractComment::class, 'contract_version_id');
    }
}
