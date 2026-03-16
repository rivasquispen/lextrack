<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'slug', 'category_id'];

    protected static function booted(): void
    {
        static::creating(function (Subcategory $subcategory): void {
            $subcategory->slug = self::buildSlug($subcategory->nombre);
        });

        static::created(function (Subcategory $subcategory): void {
            $slugWithId = self::buildSlug($subcategory->nombre, (string) $subcategory->id);

            if ($subcategory->slug !== $slugWithId) {
                $subcategory->slug = $slugWithId;
                $subcategory->saveQuietly();
            }
        });

        static::updating(function (Subcategory $subcategory): void {
            $subcategory->slug = self::buildSlug($subcategory->nombre, (string) $subcategory->id);
        });
    }

    private static function buildSlug(string $value, ?string $suffix = null): string
    {
        $base = Str::slug($value) ?: 'item';
        $suffix = $suffix ?: Str::random(8);

        $maxBaseLength = 160 - strlen($suffix) - 1;
        if ($maxBaseLength < 1) {
            $maxBaseLength = 1;
        }

        $base = Str::limit($base, $maxBaseLength, '');

        return $base.'-'.$suffix;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
