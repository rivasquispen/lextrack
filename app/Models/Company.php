<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'razon_social',
        'ruc',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'company_id');
    }
}
