<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimActivationBonusGrant extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_RESOLVED_SILENTLY = 'resolved_silently';

    protected $fillable = [
        'sim_id',
        'period_index',
        'period_start',
        'period_end',
        'triggered_at',
        'granted_at',
        'resolved_at',
        'resolution_type',
        'report_id',
        'status',
        'admin_id',
        'admin_notes',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'triggered_at' => 'datetime',
        'granted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function sim(): BelongsTo
    {
        return $this->belongsTo(Sim::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Human-readable 1-based cycle string out of 12 total months (e.g. "1/12", "2/12").
     */
    public function cycleLabel(): string
    {
        $cycle = min(12, max(1, $this->period_index + 1));

        return "{$cycle}/12";
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isResolvedSilently(): bool
    {
        return $this->status === self::STATUS_RESOLVED_SILENTLY;
    }
}
