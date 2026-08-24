<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminNotification extends Model
{
    public const TARGET_GENERAL = 'general';
    public const TARGET_ROLES = 'roles';

    /**
     * Kept in sync with the role list used across admin forms — see
     * App\Http\Controllers\Admin\ManageController's validation rules.
     */
    public const ROLES = [
        'personal', 'agent', 'partner', 'business', 'staff', 'checker',
        'super_admin', 'regional_manager', 'coordinator',
    ];

    protected $fillable = [
        'title',
        'body',
        'url',
        'icon',
        'target_type',
        'target_roles',
        'status',
        'scheduled_at',
        'sent_at',
        'created_by',
        'total_recipients',
        'total_delivered',
        'total_failed',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AdminNotificationDelivery::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')->where('scheduled_at', '<=', now());
    }
}
