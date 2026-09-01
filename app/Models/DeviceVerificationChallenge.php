<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Holds the mid-login state between a password check succeeding and a new
 * device being OTP-confirmed. Deliberately a DB-backed row referenced by an
 * opaque token in the URL, not session-stashed state — the user is fully
 * logged out (Auth::logout() + session()->invalidate()) the moment a device
 * is found untrusted, which would wipe anything kept in the session itself.
 */
class DeviceVerificationChallenge extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'channel',
        'otp_hash',
        'otp_expires_at',
        'attempts',
        'resend_count',
        'ip_address',
        'user_agent',
        'remember',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'remember' => 'boolean',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    private const CHALLENGE_MINUTES = 15;
    private const OTP_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;
    private const MAX_RESENDS = 5;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('verified_at')->where('expires_at', '>', now());
    }

    public static function open(User $user, Request $request, bool $remember): self
    {
        return static::create([
            'token' => Str::random(64),
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'remember' => $remember,
            'expires_at' => now()->addMinutes(self::CHALLENGE_MINUTES),
        ]);
    }

    public function generateOtp(string $channel): string
    {
        $code = (string) random_int(100000, 999999);

        $this->update([
            'channel' => $channel,
            'otp_hash' => hash('sha256', $code),
            'otp_expires_at' => now()->addMinutes(self::OTP_MINUTES),
            'attempts' => 0,
        ]);

        return $code;
    }

    public function verify(string $code): bool
    {
        if (
            !empty($this->otp_hash)
            && $this->otp_expires_at
            && $this->otp_expires_at->isFuture()
            && hash_equals($this->otp_hash, hash('sha256', $code))
        ) {
            $this->update([
                'otp_hash' => null,
                'otp_expires_at' => null,
                'verified_at' => now(),
            ]);

            return true;
        }

        $this->increment('attempts');

        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $this->update(['expires_at' => now()]);
        }

        return false;
    }

    public function isLocked(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function canResend(): bool
    {
        return $this->resend_count < self::MAX_RESENDS;
    }

    public function registerResend(): void
    {
        $this->increment('resend_count');
    }
}
