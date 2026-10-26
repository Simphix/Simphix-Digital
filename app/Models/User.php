<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'first_name',
    'middle_name',
    'last_name',
    'gender',
    'profile_photo',
    'role',
    'status',
    'account_tier',
    'email',
    'phone',
    'password',
    'transaction_pin',
    'pin_set_at',
    'bvn',
    'nin',
    'date_of_birth',
    'otp_code',
    'otp_expires_at',
    'state',
    'lga',
    'address',
    'business_name',
    'business_type',
    'cac_number',
    'pending_role',
    'upgrade_status',
    'upgrade_requested_at',
    'referral_code',
    'referred_by',
    'limit',
    'last_login_at',
    'last_login_ip',
    'suspended_at',
    'suspension_reason',
    'suspended_by',
    'device_registration_enabled',
    'phone_verification_otp_hash',
    'phone_verification_otp_expires_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get or set the user's full name.
     */
    protected function name(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name])) ?: $this->email,
            set: function ($value) {
                $parts = explode(' ', trim($value), 3);
                return [
                    'first_name' => $parts[0] ?? '',
                    'middle_name' => count($parts) > 2 ? $parts[1] : '',
                    'last_name' => count($parts) > 2 ? $parts[2] : ($parts[1] ?? ''),
                ];
            }
        );
    }

    /**
     * Get the wallet associated with the user.
     */
    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Devices/browsers this user has enabled push notifications on.
     */
    public function pushSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Devices this user has OTP-verified and is remembered on — distinct
     * from pushSubscriptions() above, which is about notification delivery,
     * not login trust.
     */
    public function trustedDevices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Get the virtual accounts associated with the user.
     */
    public function virtualAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VirtualAccount::class);
    }

    /**
     * Get the user's single saved cash-out destination bank account.
     */
    public function withdrawalAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WithdrawalAccount::class);
    }

    /**
     * Get custom service prices assigned to this user.
     */
    public function servicePrices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user is an internal staff-level or above.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['super_admin', 'business_sales_manager', 'support'], true);
    }

    /**
     * Check if the user is a Business Sales Manager.
     */
    public function isBusinessSalesManager(): bool
    {
        return $this->role === 'business_sales_manager';
    }


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'phone_verified_at'   => 'datetime',
            'otp_expires_at'      => 'datetime',
            'device_registration_enabled' => 'boolean',
            'phone_verification_otp_expires_at' => 'datetime',
            'pin_set_at'          => 'datetime',
            'last_login_at'       => 'datetime',
            'suspended_at'        => 'datetime',
            'date_of_birth'       => 'date',
            'limit'               => 'decimal:2',
            'account_tier'        => 'integer',
            'password'            => 'hashed',
            'transaction_pin'     => 'hashed',
            'upgrade_requested_at'=> 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Status Helpers
    // -------------------------------------------------------------------------

    /** Is the account active and not suspended? */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Is the account suspended or banned? */
    public function isSuspended(): bool
    {
        return in_array($this->status, ['suspended', 'banned']);
    }

    /** Suspend the user with an optional reason, attributing who did it. */
    public function suspend(string $reason = '', ?int $suspendedBy = null): void
    {
        $this->forceFill([
            'status'           => 'suspended',
            'suspended_at'     => now(),
            'suspension_reason'=> $reason,
            'suspended_by'     => $suspendedBy,
        ])->save();
    }

    /** Reinstate a suspended user. */
    public function reinstate(): void
    {
        $this->forceFill([
            'status'           => 'active',
            'suspended_at'     => null,
            'suspension_reason'=> null,
            'suspended_by'     => null,
        ])->save();
    }

    // -------------------------------------------------------------------------
    // Role / Business Helpers
    // -------------------------------------------------------------------------

    /** Is this a business/agent/partner account? */
    public function isBusiness(): bool
    {
        return in_array($this->role, ['business', 'agent', 'partner']);
    }

    // -------------------------------------------------------------------------
    // Referral Relationships
    // -------------------------------------------------------------------------

    /** The user who referred this user. */
    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** Users who were referred by this user. */
    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    // -------------------------------------------------------------------------
    // Jurisdiction Relationships (independent of the referral hierarchy above)
    // -------------------------------------------------------------------------

    /** The region this user manages, if they are its Regional Manager. */
    public function regionAsManager(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Region::class, 'regional_manager_id');
    }

    /** The senatorial district this user covers, if they are its Coordinator. */
    public function districtAsCoordinator(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SenatorialDistrict::class, 'coordinator_id');
    }

    /** Does this user's role carry a geographic jurisdiction at all? */
    public function hasJurisdictionRole(): bool
    {
        return in_array($this->role, ['regional_manager', 'coordinator'], true);
    }

    /**
     * Human-readable jurisdiction label: the region name for a Regional
     * Manager, or "{district} · {state} · {region}" for a Coordinator —
     * region resolved live through district->state->region, never
     * duplicated/cached, which is what makes a state-remap cascade
     * automatically with zero writes to the district/coordinator record.
     * Null both when the role carries no jurisdiction and when it does but
     * none is assigned yet — callers needing to distinguish check
     * hasJurisdictionRole() first.
     */
    public function jurisdictionLabel(): ?string
    {
        if ($this->role === 'regional_manager') {
            return $this->regionAsManager?->name;
        }

        if ($this->role === 'coordinator') {
            $district = $this->districtAsCoordinator;
            if (!$district) {
                return null;
            }

            return implode(' · ', array_filter([$district->name, $district->state?->name, $district->state?->region?->name]));
        }

        return null;
    }

    /**
     * Generate a new 6-digit OTP code for the user.
     */
    public function generateOtp(): string
    {
        $otp = (string) random_int(100000, 999999);
        $this->forceFill([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(15),
        ])->save();
        return $otp;
    }

    /**
     * Verify the provided OTP code.
     */
    public function verifyOtp(string $code): bool
    {
        if (!empty($this->otp_code) && hash_equals($this->otp_code, $code) && $this->otp_expires_at && $this->otp_expires_at->isFuture()) {
            $this->forceFill([
                'otp_code' => null,
                'otp_expires_at' => null,
                'email_verified_at' => now(),
            ])->save();
            return true;
        }
        return false;
    }

    /**
     * Send the OTP code via email.
     */
    public function sendOtpEmail(): void
    {
        $otp = $this->otp_code ?? $this->generateOtp();
        \Illuminate\Support\Facades\Mail::to($this->email)->send(new \App\Mail\OtpVerificationMail($otp));
    }

    /**
     * Generate a 6-digit phone-ownership verification code. Deliberately
     * separate storage from otp_code/otp_expires_at (email verification)
     * and from device_verification_challenges (login) — this is its own
     * narrow purpose: proving the user actually controls the phone number
     * on file before it can be trusted as a device-OTP delivery channel.
     */
    public function generatePhoneVerificationOtp(): string
    {
        $otp = (string) random_int(100000, 999999);
        $this->forceFill([
            'phone_verification_otp_hash' => hash('sha256', $otp),
            'phone_verification_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        return $otp;
    }

    public function verifyPhoneVerificationOtp(string $code): bool
    {
        if (
            !empty($this->phone_verification_otp_hash)
            && $this->phone_verification_otp_expires_at
            && $this->phone_verification_otp_expires_at->isFuture()
            && hash_equals($this->phone_verification_otp_hash, hash('sha256', $code))
        ) {
            $this->forceFill([
                'phone_verification_otp_hash' => null,
                'phone_verification_otp_expires_at' => null,
                'phone_verified_at' => now(),
            ])->save();

            return true;
        }

        return false;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        // Disable default Laravel email verification notification
        // so that only the OTP verification code is used.
    }
}
