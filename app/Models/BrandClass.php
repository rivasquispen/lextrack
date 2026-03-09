<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandClass extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'description'];

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_class_relations');
    }
}

