<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * Get the customer information recorded upon activation.
     */
    public function customer(): HasOne
    {
        return $this->hasOne(SimCustomer::class, 'sim_id');
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
     * Alias for history().
     */
    public function histories(): HasMany
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

    /**
     * Filters to SIMs whose cascade lineage falls within a given
     * Region/State/SenatorialDistrict. Safe on an ACTIVATED sim because
     * SimAssignmentService::activate() only ever flips status — it never
     * touches regional_manager_id/coordinator_id, so those FKs (set once
     * per tier as the sim moved down the cascade via assignDown()) persist
     * for the sim's whole lifetime. Most-specific filter wins: passing a
     * districtId ignores stateId/regionId, etc.
     *
     * Two resolution paths, since a sim's jurisdiction can be known via
     * either holder: coordinator->districtAsCoordinator (district/state/
     * region all derivable) or regionalManager->regionAsManager (region and
     * its states, for a sim whose current cascade never passed through a
     * district-holding coordinator — e.g. it's still sitting with the
     * Regional Manager, or moved straight to a state-level query).
     */
    public function scopeInJurisdiction($query, ?int $regionId, ?int $stateId, ?int $districtId)
    {
        if ($districtId) {
            return $query->whereHas('coordinator.districtAsCoordinator', fn ($q) => $q->where('id', $districtId));
        }

        if ($stateId) {
            return $query->where(function ($q) use ($stateId) {
                $q->whereHas('coordinator.districtAsCoordinator', fn ($q2) => $q2->where('state_id', $stateId))
                    ->orWhereHas('regionalManager.regionAsManager.states', fn ($q2) => $q2->where('id', $stateId));
            });
        }

        if ($regionId) {
            return $query->where(function ($q) use ($regionId) {
                $q->whereHas('regionalManager.regionAsManager', fn ($q2) => $q2->where('id', $regionId))
                    ->orWhereHas('coordinator.districtAsCoordinator.state', fn ($q2) => $q2->where('region_id', $regionId));
            });
        }

        return $query;
    }

    /**
     * Get whole rounded days since creation/holding with no floating number.
     */
    public function daysHeld(): int
    {
        return (int) round($this->created_at ? $this->created_at->diffInDays(now()) : 0);
    }
}
