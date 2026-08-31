<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimActivationBonusGrant extends Model
{
    protected $fillable = [
        'sim_id',
        'period_index',
        'period_start',
        'period_end',
        'granted_at',
        'report_id',
        'status',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'granted_at' => 'datetime',
    ];

    public function sim(): BelongsTo
    {
        return $this->belongsTo(Sim::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
