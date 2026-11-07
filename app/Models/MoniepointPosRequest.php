<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MoniepointPosRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING      = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_CONTACTED    = 'contacted';
    public const STATUS_DISPATCHED   = 'dispatched';
    public const STATUS_DELIVERED    = 'delivered';
    public const STATUS_DECLINED     = 'declined';

    public const STATUSES = [
        self::STATUS_PENDING      => 'Pending Review',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_CONTACTED    => 'Customer Will Be Contacted',
        self::STATUS_DISPATCHED   => 'Dispatched / In Transit',
        self::STATUS_DELIVERED    => 'Delivered & Activated',
        self::STATUS_DECLINED     => 'Declined',
    ];

    public const POS_TYPES = [
        'smart_pos'       => 'Smart POS (Android)',
        'traditional_pos' => 'Traditional POS',
        'mini_pos'        => 'Mini POS / MPOS',
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'name',
        'phone',
        'email',
        'passport_photo',
        'bvn',
        'nin',
        'shop_address',
        'state',
        'lga',
        'home_address',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_address',
        'referral_name',
        'referral_phone',
        'pos_type',
        'business_name',
        'delivery_address',
        'quantity',
        'notes',
        'status',
        'admin_notes',
        'customer_notes',
        'contacted_at',
        'resolved_at',
    ];

    public function getPassportPhotoUrlAttribute(): ?string
    {
        if (! $this->passport_photo) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->passport_photo);
    }

    protected $casts = [
        'quantity'     => 'integer',
        'contacted_at' => 'datetime',
        'resolved_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'MPOS-' . strtoupper(now()->format('Ym')) . '-' . strtoupper(Str::random(5));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getPosTypeLabelAttribute(): string
    {
        return self::POS_TYPES[$this->pos_type] ?? ucfirst(str_replace('_', ' ', $this->pos_type));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING      => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STATUS_UNDER_REVIEW => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STATUS_CONTACTED    => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::STATUS_DISPATCHED   => 'bg-purple-100 text-purple-800 border-purple-200',
            self::STATUS_DELIVERED    => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_DECLINED     => 'bg-rose-100 text-rose-800 border-rose-200',
            default                   => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}
