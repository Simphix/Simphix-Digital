<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LoanRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING      = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_CONTACTED    = 'contacted';
    public const STATUS_APPROVED     = 'approved';
    public const STATUS_DISBURSED    = 'disbursed';
    public const STATUS_DECLINED     = 'declined';

    public const STATUSES = [
        self::STATUS_PENDING      => 'Processing',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_CONTACTED    => 'Customer Will Be Contacted',
        self::STATUS_APPROVED     => 'Approved',
        self::STATUS_DISBURSED    => 'Disbursed',
        self::STATUS_DECLINED     => 'Declined',
    ];

    public const TENURES = [
        '30_days'  => '30 Days (1 Month)',
        '60_days'  => '60 Days (2 Months)',
        '90_days'  => '90 Days (3 Months)',
        '180_days' => '180 Days (6 Months)',
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'name',
        'phone',
        'email',
        'moniepoint_account_number',
        'amount_requested',
        'business_name',
        'loan_tenure',
        'purpose',
        'status',
        'admin_notes',
        'customer_notes',
        'contacted_at',
        'resolved_at',
    ];

    protected $casts = [
        'amount_requested' => 'decimal:2',
        'contacted_at'     => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'LOAN-' . strtoupper(now()->format('Ym')) . '-' . strtoupper(Str::random(5));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format((float) $this->amount_requested, 2);
    }

    public function getTenureLabelAttribute(): string
    {
        return self::TENURES[$this->loan_tenure] ?? ($this->loan_tenure ?: 'Flexible');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING      => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STATUS_UNDER_REVIEW => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STATUS_CONTACTED    => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::STATUS_APPROVED     => 'bg-teal-100 text-teal-800 border-teal-200',
            self::STATUS_DISBURSED    => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_DECLINED     => 'bg-rose-100 text-rose-800 border-rose-200',
            default                   => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}
