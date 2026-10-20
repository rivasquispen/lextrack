<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalReopening extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contract_id', 'version_id', 'approval_id', 'approver_id', 'admin_id', 'lawyer_id',
        'version_number', 'previous_status', 'previous_approved_at', 'reason',
        'coordination_reference', 'snapshot', 'created_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'previous_approved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }
}
