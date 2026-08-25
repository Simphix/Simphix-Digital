<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sim extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'category',
        'provider',
        'user_id',
        'partner_id',
        'coordinator_id',
        'regional_manager_id',
        'business_id',
        'business_agent_id',
        'status',
    ];

    /**
     * Get the user that is assigned to the SIM.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the partner that owns/assigned the SIM.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    /**
     * Get the coordinator currently holding this SIM in the cascade.
     */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    /**
     * Get the regional manager currently holding this SIM in the cascade.
     */
    public function regionalManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regional_manager_id');
    }

    /**
     * Get the Enterprise Partner (business role) currently holding this SIM
     * in the cascade.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the business agent currently holding this SIM in the cascade.
     */
    public function businessAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_agent_id');
    }

    /**
     * Get the requests associated with the SIM.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(SimRequest::class, 'sim_id');
    }

    /**
     * Get the assignment/activation history log for this SIM.
     */
    public function history(): HasMany
    {
        return $this->hasMany(SimHistory::class, 'sim_id')->latest();
    }

    /**
     * Get the swap requests filed for this SIM.
     */
    public function swapRequests(): HasMany
    {
        return $this->hasMany(SimSwapRequest::class, 'sim_id');
    }
}
