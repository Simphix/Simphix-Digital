<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A device the user has verified via OTP and is remembered for future
 * logins — checked via a selector/validator split cookie ("device_trust"),
 * not by re-deriving a fingerprint from the request on every login (that
 * would fight VerifySessionFingerprint's own IP-subnet tolerance and cause
 * false "new device" prompts on ordinary mobile IP drift). Trust slides
 * forward 60 days on every use (touch()) rather than being permanent, so an
 * abandoned device eventually falls out of trust on its own.
 */
class TrustedDevice extends Model
{
    protected $fillable = [
        'user_id',
        'selector',
        'hashed_validator',
        'label',
        'user_agent',
        'ip_address',
        'last_used_at',
        'last_used_ip',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    private const TRUST_DAYS = 60;
    private const COOKIE_NAME = 'device_trust';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Creates the trust record and returns the raw cookie value
     * (selector.validator) — the only moment the validator ever exists in
     * plaintext; only its hash is persisted.
     */
    public static function issueFor(User $user, Request $request): string
    {
        $selector = Str::random(26);
        $validator = Str::random(40);

        static::create([
            'user_id' => $user->id,
            'selector' => $selector,
            'hashed_validator' => hash('sha256', $validator),
            'label' => self::labelFromUserAgent($request->userAgent()),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
            'expires_at' => now()->addDays(self::TRUST_DAYS),
        ]);

        return "{$selector}.{$validator}";
    }

    /**
     * Resolves a raw cookie value to its trust record — never throws; an
     * absent/malformed/tampered/expired/revoked cookie just means "not
     * trusted," same as a device that was never seen before.
     */
    public static function resolveFromCookie(?string $raw): ?self
    {
        if (!$raw || !str_contains($raw, '.')) {
            return null;
        }

        [$selector, $validator] = explode('.', $raw, 2);

        $device = static::where('selector', $selector)->first();

        if (!$device || !hash_equals($device->hashed_validator, hash('sha256', $validator))) {
            return null;
        }

        if ($device->revoked_at !== null || ($device->expires_at && $device->expires_at->isPast())) {
            return null;
        }

        return $device;
    }

    public function recordUse(Request $request): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
            'expires_at' => now()->addDays(self::TRUST_DAYS),
        ]);
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    public static function cookieName(): string
    {
        return self::COOKIE_NAME;
    }

    public static function trustDays(): int
    {
        return self::TRUST_DAYS;
    }

    public static function labelFromUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown device';
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome/') => 'Safari',
            default => 'Browser',
        };

        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'an unknown OS',
        };

        return "{$browser} on {$os}";
    }
}
