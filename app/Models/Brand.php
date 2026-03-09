<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    public const STATUS_DEFAULT = 'en_tramite';

    public const FALLBACK_STATUS_OPTIONS = [
        'en_tramite' => 'En trámite',
        'en_oposicion' => 'En oposición',
        'bajo_examen_formal' => 'Bajo examen formal',
        'bajo_examen_fondo' => 'Bajo examen de fondo',
        'aceptada_registro' => 'Aceptada en registro',
        'aceptada_pago_aprobacion' => 'Aceptada en pago de aprobación',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
    ];

    protected $fillable = [
        'name',
        'nombre',
        'numero_registro',
        'certificate_number',
        'brand_country_id',
        'brand_type_id',
        'holder',
        'image_path',
        'pais_id',
        'status',
        'brand_status_id',
        'registration_date',
        'fecha_registro',
        'process_start_date',
        'usage_start_date',
        'expiration_date',
        'fecha_vencimiento',
        'created_by',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'fecha_registro' => 'date',
        'process_start_date' => 'date',
        'usage_start_date' => 'date',
        'expiration_date' => 'date',
        'fecha_vencimiento' => 'date',
        'brand_status_id' => 'integer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'pais_id');
    }

    public function brandCountry()
    {
        return $this->belongsTo(BrandCountry::class);
    }

    public function brandType()
    {
        return $this->belongsTo(BrandType::class);
    }

    public function classes()
    {
        return $this->belongsToMany(BrandClass::class, 'brand_class_relations');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusDefinition()
    {
        return $this->belongsTo(BrandStatus::class, 'brand_status_id');
    }

    public function comments()
    {
        return $this->hasMany(BrandComment::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->attributes['name']
            ?? $this->attributes['nombre']
            ?? 'Sin nombre';
    }

    public function getDisplayHolderAttribute(): string
    {
        return $this->attributes['holder']
            ?? $this->attributes['titular']
            ?? 'Sin titular';
    }

    public function getDisplayCountryAttribute(): string
    {
        return $this->brandCountry?->name
            ?? $this->country?->nombre
            ?? 'Sin país';
    }

    public function getDisplayTypeAttribute(): string
    {
        return $this->brandType?->name ?? 'Sin tipo';
    }

    public function getDisplayRegistrationNumberAttribute(): string
    {
        return $this->attributes['certificate_number']
            ?? $this->attributes['numero_registro']
            ?? 'Sin registro';
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->statusDefinition) {
            return $this->statusDefinition->name;
        }

        $status = $this->attributes['status'] ?? null;

        if ($status && isset(self::FALLBACK_STATUS_OPTIONS[$status])) {
            return self::FALLBACK_STATUS_OPTIONS[$status];
        }

        return $status
            ? Str::of($status)->lower()->replace('_', ' ')->title()
            : 'Sin estado';
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->statusDefinition) {
            return $this->statusDefinition->color ?: 'slate';
        }

        $status = Str::of($this->attributes['status'] ?? '')->lower()->value();

        return match ($status) {
            'en_tramite' => 'sky',
            'bajo_examen_formal', 'bajo_examen_fondo' => 'amber',
            'aceptada_registro', 'aceptada_pago_aprobacion', 'aprobada' => 'emerald',
            'en_oposicion' => 'orange',
            'rechazada', 'cancelado' => 'rose',
            default => 'slate',
        };
    }

    public static function statusOptions(): array
    {
        $options = BrandStatus::options();

        return empty($options) ? self::FALLBACK_STATUS_OPTIONS : $options;
    }

    public function getDisplayRegistrationDateAttribute(): ?string
    {
        return $this->formatDate($this->attributes['registration_date'] ?? $this->attributes['fecha_registro'] ?? null);
    }

    public function getDisplayExpirationDateAttribute(): ?string
    {
        return $this->formatDate($this->attributes['expiration_date'] ?? $this->attributes['fecha_vencimiento'] ?? null);
    }

    protected function formatDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return $date->format('d M Y');
    }
}
