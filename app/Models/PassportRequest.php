<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PassportRequest extends Model
{
    use HasFactory;

    public const TYPE_NEW     = 'new';
    public const TYPE_RENEWAL = 'renewal';

    public const TYPES = [
        self::TYPE_NEW     => 'Fresh / New Passport',
        self::TYPE_RENEWAL => 'Passport Renewal / Reissue',
    ];

    public const STATUS_PENDING             = 'pending';
    public const STATUS_UNDER_REVIEW        = 'under_review';
    public const STATUS_CONTACTED           = 'contacted';
    public const STATUS_CAPTURING_SCHEDULED = 'capturing_scheduled';
    public const STATUS_PROCESSING          = 'processing';
    public const STATUS_READY_FOR_PICKUP    = 'ready_for_pickup';
    public const STATUS_COMPLETED           = 'completed';
    public const STATUS_REJECTED            = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING             => 'Submitted / Pending Triage',
        self::STATUS_UNDER_REVIEW        => 'Under Review',
        self::STATUS_CONTACTED           => 'Applicant Contacted',
        self::STATUS_CAPTURING_SCHEDULED => 'Biometrics Capture Scheduled',
        self::STATUS_PROCESSING          => 'Processing with NIS',
        self::STATUS_READY_FOR_PICKUP    => 'Ready for Collection',
        self::STATUS_COMPLETED           => 'Completed / Delivered',
        self::STATUS_REJECTED            => 'Rejected / Cancelled',
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'application_type',
        'previous_passport_number',
        'nin',
        'surname',
        'first_name',
        'middle_name',
        'date_of_birth',
        'state_of_origin',
        'lga',
        'hometown',
        'place_of_birth',
        'phone',
        'mobile_number',
        'email',
        'residential_address',
        'address',
        'gender',
        'sex',
        'occupation',
        'next_of_kin_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'next_of_kin_address',
        'passport_photo_path',
        'passport_photo',
        'state_origin_cert_path',
        'state_of_origin_cert',
        'birth_cert_path',
        'birth_certificate',
        'nin_doc_path',
        'nin_document',
        'signature_path',
        'signature',
        'previous_passport_path',
        'previous_passport',
        'status',
        'capturing_date',
        'capturing_center',
        'admin_notes',
        'customer_notes',
        'contacted_at',
        'reviewed_at',
        'resolved_at',
    ];

    protected $casts = [
        'date_of_birth'   => 'date',
        'capturing_date'  => 'datetime',
        'contacted_at'    => 'datetime',
        'reviewed_at'     => 'datetime',
        'resolved_at'     => 'datetime',
    ];

    public function setMobileNumberAttribute($value): void
    {
        $this->attributes['phone'] = $value;
    }

    public function getMobileNumberAttribute(): ?string
    {
        return $this->attributes['phone'] ?? null;
    }

    public function setAddressAttribute($value): void
    {
        $this->attributes['residential_address'] = $value;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['residential_address'] ?? null;
    }

    public function setSexAttribute($value): void
    {
        $this->attributes['gender'] = $value;
    }

    public function getSexAttribute(): ?string
    {
        return $this->attributes['gender'] ?? null;
    }

    public function setPassportPhotoAttribute($value): void
    {
        $this->attributes['passport_photo_path'] = $value;
    }

    public function getPassportPhotoAttribute(): ?string
    {
        return $this->attributes['passport_photo_path'] ?? null;
    }

    public function setStateOfOriginCertAttribute($value): void
    {
        $this->attributes['state_origin_cert_path'] = $value;
    }

    public function getStateOfOriginCertAttribute(): ?string
    {
        return $this->attributes['state_origin_cert_path'] ?? null;
    }

    public function setBirthCertificateAttribute($value): void
    {
        $this->attributes['birth_cert_path'] = $value;
    }

    public function getBirthCertificateAttribute(): ?string
    {
        return $this->attributes['birth_cert_path'] ?? null;
    }

    public function setNinDocumentAttribute($value): void
    {
        $this->attributes['nin_doc_path'] = $value;
    }

    public function getNinDocumentAttribute(): ?string
    {
        return $this->attributes['nin_doc_path'] ?? null;
    }

    public function setSignatureAttribute($value): void
    {
        $this->attributes['signature_path'] = $value;
    }

    public function getSignatureAttribute(): ?string
    {
        return $this->attributes['signature_path'] ?? null;
    }

    public function setPreviousPassportAttribute($value): void
    {
        $this->attributes['previous_passport_path'] = $value;
    }

    public function getPreviousPassportAttribute(): ?string
    {
        return $this->attributes['previous_passport_path'] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate unique tracking code e.g. PPT-202611-X4K9L
     */
    public static function generateReference(): string
    {
        do {
            $ref = 'PPT-' . strtoupper(now()->format('Ym')) . '-' . strtoupper(Str::random(5));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->surname} {$this->first_name} {$this->middle_name}");
    }

    public function getApplicationTypeLabelAttribute(): string
    {
        return self::TYPES[$this->application_type] ?? ucfirst($this->application_type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING                          => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STATUS_UNDER_REVIEW, self::STATUS_CONTACTED => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STATUS_CAPTURING_SCHEDULED              => 'bg-purple-100 text-purple-800 border-purple-200',
            self::STATUS_PROCESSING                       => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::STATUS_READY_FOR_PICKUP                 => 'bg-teal-100 text-teal-800 border-teal-200',
            self::STATUS_COMPLETED                        => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_REJECTED                         => 'bg-rose-100 text-rose-800 border-rose-200',
            default                                       => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}
