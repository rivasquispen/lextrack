<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Approval;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractSigner;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'cargo',
        'empresa_id',
        'department_id',
        'active',
        'last_login_at',
        'token',
        'refresh_token',
        'token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'empresa_id' => 'integer',
            'department_id' => 'integer',
            'token_expires_at' => 'datetime',
        ];
    }

    public function createdContracts()
    {
        return $this->hasMany(Contract::class, 'creado_por');
    }

    public function lawyerContracts()
    {
        return $this->hasMany(Contract::class, 'abogado_id');
    }

    public function assignedApprovals()
    {
        return $this->hasMany(Approval::class, 'user_id');
    }

    public function signerAssignments()
    {
        return $this->hasMany(ContractSigner::class, 'user_id');
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_user')
            ->withPivot('is_primary')
            ->withTimestamps()
            ->orderByDesc('country_user.is_primary');
    }

    public function getCountryAttribute()
    {
        $countries = $this->relationLoaded('countries')
            ? $this->getRelation('countries')
            : $this->countries()->get();

        if ($countries->isEmpty()) {
            return null;
        }

        return $countries->first(function ($country) {
            return (bool) ($country->pivot->is_primary ?? false);
        })
            ?? $countries->first();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'empresa_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
