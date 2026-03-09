<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'sort_order',
        'is_default',
        'description',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public static function options(): array
    {
        return static::ordered()->pluck('name', 'slug')->toArray();
    }
}

