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
        'business_sales_manager_id',
        'business_id',
        'business_agent_id',
        'status',
        'activated_at',
        'subscription_expires_at',
    ];

    protected $casts = [
        'activated_at'            => 'datetime',
        'subscription_expires_at' => 'datetime',
    ];

    /**
     * Get the Business Sales Manager who holds or dispatched this SIM.
     */
    public function businessSalesManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_sales_manager_id');
    }

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
     * Get the subscription periods for this SIM.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(SimSubscription::class, 'sim_id')->latest();
    }

    /**
     * Get the latest subscription record for this SIM.
     */
    public function latestSubscription(): HasOne
    {
        return $this->hasOne(SimSubscription::class, 'sim_id')->latestOfMany();
    }

    /**
     * Get whole rounded days since creation/holding with no floating number.
     */
    public function daysHeld(): int
    {
        return (int) round($this->created_at ? $this->created_at->diffInDays(now()) : 0);
    }

    /**
     * Get remaining calendar days until the 365-day subscription expires.
     * Calculated on a calendar-day basis in the application timezone (Africa/Lagos),
     * so that the countdown decrements by 1 day every 24 hours at 12:00 AM midnight.
     * Returns null if the SIM was never activated.
     * Returns 0 if expired.
     */
    public function daysRemaining(): ?int
    {
        if (! $this->subscription_expires_at) {
            return null;
        }

        if ($this->isSubscriptionExpired()) {
            return 0;
        }

        $tz = config('app.timezone', 'Africa/Lagos');
        $todayStart = now($tz)->startOfDay();
        $expiryStart = $this->subscription_expires_at->copy()->timezone($tz)->startOfDay();

        $diffDays = (int) $todayStart->diffInDays($expiryStart, false);

        return max(0, $diffDays);
    }

    /**
     * Determine whether the 365-day subscription has completed / expired.
     */
    public function isSubscriptionExpired(): bool
    {
        if (! $this->subscription_expires_at) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->subscription_expires_at);
    }

    /**
     * Return subscription lifecycle tier: 'unactivated', 'expired', 'expiring_soon', 'active'.
     */
    public function subscriptionStatus(): string
    {
        if (! $this->subscription_expires_at) {
            return 'unactivated';
        }

        if ($this->isSubscriptionExpired()) {
            return 'expired';
        }

        $days = $this->daysRemaining();
        if ($days !== null && $days <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Human-friendly label for subscription status.
     */
    public function subscriptionStatusLabel(): string
    {
        return match ($this->subscriptionStatus()) {
            'expired'       => 'Expired',
            'expiring_soon' => 'Expiring Soon',
            'active'        => 'Active',
            default         => 'Not Activated',
        };
    }

    /**
     * Badge classes for subscription status.
     */
    public function subscriptionBadgeClasses(): string
    {
        return match ($this->subscriptionStatus()) {
            'expired'       => 'bg-rose-50 text-rose-700 border-rose-200',
            'expiring_soon' => 'bg-amber-50 text-amber-700 border-amber-200',
            'active'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            default         => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }

    public function activationBonusGrants(): HasMany
    {
        return $this->hasMany(SimActivationBonusGrant::class, 'sim_id');
    }

    public function latestActivationBonusGrant(): HasOne
    {
        return $this->hasOne(SimActivationBonusGrant::class, 'sim_id')->latestOfMany();
    }

    public function pendingActivationBonusGrant(): HasOne
    {
        return $this->hasOne(SimActivationBonusGrant::class, 'sim_id')->where('status', SimActivationBonusGrant::STATUS_PENDING);
    }

    /**
     * How many bonus cycles this SIM has fulfilled (completed or resolved silently), out of 12.
     */
    public function activationBonusCount(): int
    {
        return $this->activationBonusGrants()
            ->whereIn('status', [SimActivationBonusGrant::STATUS_COMPLETED, SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY])
            ->count();
    }

    /**
     * Human-readable bonus cycle progress (e.g. "1/12", "2/12").
     */
    public function activationBonusProgress(): string
    {
        $count = min(12, $this->activationBonusCount());

        return "{$count}/12";
    }

    /**
     * Color for the status blip dot.
     */
    public function subscriptionBlipColor(): string
    {
        return match ($this->subscriptionStatus()) {
            'expired'       => 'rose',
            'expiring_soon' => 'amber',
            'active'        => 'emerald',
            default         => 'slate',
        };
    }
}
