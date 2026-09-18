<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CacRegistration extends Model
{
    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'action_required' => 'Action Required',
        'name_reservation' => 'Name Reservation',
        'publication' => 'Newspaper Publication',
        'awaiting_approval' => 'Awaiting CAC Approval',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    /** Terminal states that refund the fee. */
    public const REFUND_STATUSES = ['rejected', 'cancelled'];

    protected $fillable = [
        'reference', 'user_id', 'category', 'status', 'amount', 'transaction_ref',
        'form_data', 'submitted_at', 'completed_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CacRegistrationDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CacRegistrationEvent::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getCategoryLabelAttribute(): string
    {
        return \App\Support\CacRequirements::get($this->category)['label'] ?? $this->category;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'rejected', 'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100',
            'action_required' => 'bg-amber-50 text-amber-700 border-amber-100',
            'submitted' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-blue-50 text-blue-700 border-blue-100',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'rejected', 'cancelled'], true);
    }
}
