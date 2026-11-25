<?php

namespace App\Models;

use Carbon\CarbonInterface;
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
        'is_unlocked',
        'position',
        'unlocked_at',
        'enabled_seconds',
    ];

    protected $casts = [
        'aprobado_at' => 'datetime',
        'assigned_at' => 'datetime',
        'is_unlocked' => 'boolean',
        'position' => 'integer',
        'unlocked_at' => 'datetime',
        'enabled_seconds' => 'integer',
    ];

    protected $attributes = ['is_unlocked' => false, 'enabled_seconds' => 0];

    protected static function booted(): void
    {
        static::creating(function (Approval $approval) {
            if ($approval->is_unlocked && ! $approval->unlocked_at && $approval->estado !== 'aprobado' && ! $approval->aprobado_at) {
                $approval->unlocked_at = now();
            }
            if (! $approval->position) {
                $approval->position = (int) static::where('version_id', $approval->version_id)->max('position') + 1;
            }
        });
    }

    public function totalEnabledSeconds(?CarbonInterface $until = null): int
    {
        $seconds = max(0, (int) $this->enabled_seconds);
        if (! $this->is_unlocked || ! $this->unlocked_at) {
            return $seconds;
        }
        $until = $until?->copy() ?? now();
        if ($this->aprobado_at && $this->aprobado_at->lessThan($until)) {
            $until = $this->aprobado_at->copy();
        }

        return $seconds + max(0, (int) floor($this->unlocked_at->diffInSeconds($until, false)));
    }

    // Caller saves this change inside its existing transaction and approval lock.
    public function pauseEnabledClock(?CarbonInterface $until = null): void
    {
        $this->enabled_seconds = $this->totalEnabledSeconds($until);
        $this->unlocked_at = null;
    }

    public static function formatEnabledSeconds(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds === 0) {
            return '0 minutos';
        }
        if ($seconds < 60) {
            return 'menos de un minuto';
        }
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $parts = [];
        foreach ([[$days, 'día', 'días'], [$hours, 'hora', 'horas'], [$minutes, 'minuto', 'minutos']] as [$value, $singular, $plural]) {
            if ($value > 0) {
                $parts[] = $value.' '.($value === 1 ? $singular : $plural);
            }
        }

        return implode(' ', array_slice($parts, 0, 2));
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
