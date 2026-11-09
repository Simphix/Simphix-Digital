<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TravelRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING   => 'Pending Triage',
        self::STATUS_CONTACTED => 'Customer Will Be Contacted',
        self::STATUS_APPROVED  => 'Approved / Processing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled / Declined',
    ];

    public const PACKAGE_TYPES = [
        'umrah'         => 'Umrah Pilgrimage',
        'hajj'          => 'Hajj Pilgrimage',
        'international' => 'International Travel & Tour',
        'custom'        => 'Custom Travel Itinerary',
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'travel_package_id',
        'name',
        'phone',
        'email',
        'package_name',
        'package_type',
        'number_of_travelers',
        'departure_date',
        'notes',
        'referral_name',
        'referral_phone',
        'status',
        'admin_notes',
        'customer_notes',
        'contacted_at',
        'resolved_at',
    ];

    protected $casts = [
        'number_of_travelers' => 'integer',
        'contacted_at'        => 'datetime',
        'resolved_at'         => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function travelPackage(): BelongsTo
    {
        return $this->belongsTo(TravelPackage::class, 'travel_package_id');
    }

    /**
     * Generate a unique readable tracking reference code.
     */
    public static function generateReference(): string
    {
        do {
            $ref = 'TRV-' . strtoupper(now()->format('Ym')) . '-' . strtoupper(Str::random(5));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ($this->status === 'reviewing' ? 'Customer Will Be Contacted' : ucfirst(str_replace('_', ' ', $this->status)));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING                          => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STATUS_CONTACTED, self::STATUS_REVIEWING => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::STATUS_APPROVED                         => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_COMPLETED                        => 'bg-teal-100 text-teal-800 border-teal-200',
            self::STATUS_CANCELLED                        => 'bg-rose-100 text-rose-800 border-rose-200',
            default                                       => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }

    public function getPackageTypeLabelAttribute(): string
    {
        return self::PACKAGE_TYPES[$this->package_type] ?? ucfirst($this->package_type);
    }
}
