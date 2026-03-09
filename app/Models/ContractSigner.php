<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSigner extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'estado',
        'observaciones',
        'firmado_at',
    ];

    protected $casts = [
        'firmado_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
