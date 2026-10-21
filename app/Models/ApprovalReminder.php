<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalReminder extends Model
{
    protected $fillable = [
        'contract_id', 'version_id', 'approval_id', 'sender_id', 'recipient_id',
        'pending_days', 'status', 'failure_type', 'snapshot', 'sent_at',
    ];

    protected $casts = [
        'pending_days' => 'integer',
        'snapshot' => 'array',
        'sent_at' => 'datetime',
    ];
}
