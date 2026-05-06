<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_version_id',
        'document_path',
        'uploaded_by',
        'is_ready_for_vendor_review',
    ];

    protected $casts = [
        'is_ready_for_vendor_review' => 'boolean',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'contract_version_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
