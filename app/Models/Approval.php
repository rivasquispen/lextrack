<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'user_id',
        'estado',
        'observaciones',
        'aprobado_at',
        'assigned_at',
    ];

    protected $casts = [
        'aprobado_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
