<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_CREATED = 'creado';
    public const STATUS_ASSIGNED = 'asignado';
    public const STATUS_APPROVAL = 'en_aprobacion';
    public const STATUS_APPROVED = 'aprobado';
    public const STATUS_SIGNED = 'firmado';
    public const STATUS_OBSERVED = 'observado';

    public const STATUS_LABELS = [
        self::STATUS_CREATED => 'Creado',
        self::STATUS_ASSIGNED => 'Asignado',
        self::STATUS_APPROVAL => 'En aprobación',
        self::STATUS_APPROVED => 'Aprobado',
        self::STATUS_SIGNED => 'Firmado',
        self::STATUS_OBSERVED => 'Observado',
    ];

    protected $fillable = [
        'titulo',
        'categoria_id',
        'subcategoria_id',
        'creado_por',
        'abogado_id',
        'asesor_id',
        'estado',
        'document',
        'document_signed',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'subcategoria_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abogado_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class);
    }

    public function approvals(): HasManyThrough
    {
        return $this->hasManyThrough(
            Approval::class,
            ContractVersion::class,
            'contract_id',
            'version_id'
        );
    }

    public function signers(): HasMany
    {
        return $this->hasMany(ContractSigner::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContractComment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->estado] ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
    }

    public static function statusKeys(): array
    {
        return array_keys(self::STATUS_LABELS);
    }
}
