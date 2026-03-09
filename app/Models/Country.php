<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'country_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
