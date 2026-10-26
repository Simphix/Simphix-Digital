<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SmeDataProviderSetting extends Model
{
    public const PROVIDERS = ['gogoz', 'smeplug', 'amsub', 'legacy'];

    protected $fillable = [
        'provider',
        'base_url',
        'api_key',
        'secret_key',
        'auth_base_url',
        'debit_account',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted',
        'secret_key' => 'encrypted',
    ];

    /**
     * One row per vendor, created on first access. 'gogoz' is the primary
     * #1 vendor and starts active by default.
     */
    public static function forProvider(string $provider): self
    {
        return static::firstOrCreate(
            ['provider' => $provider],
            match ($provider) {
                'gogoz' => [
                    'is_active' => true,
                    'base_url' => config('services.gogoz.base_url', 'https://www.gongozconcept.com/api/data/'),
                    'api_key' => config('services.gogoz.api_key'),
                ],
                'legacy' => [
                    'is_active' => false,
                    'base_url' => config('services.smedata.base_url'),
                    'api_key' => config('services.smedata.api_key'),
                ],
                'amsub' => [
                    'is_active' => false,
                    'base_url' => config('services.amsub.base_url'),
                    'api_key' => config('services.amsub.api_key'),
                ],
                default => [
                    'is_active' => false,
                    'base_url' => config('services.smeplug.base_url'),
                    'api_key' => config('services.smeplug.api_key'),
                ],
            }
        );
    }

    /**
     * All vendor rows, keyed by provider, creating any missing ones so the
     * admin UI always has a full set of rows to configure.
     */
    public static function allProviders(): Collection
    {
        foreach (self::PROVIDERS as $provider) {
            self::forProvider($provider);
        }

        return static::whereIn('provider', self::PROVIDERS)->get()->keyBy('provider');
    }

    public static function activeProvider(): string
    {
        return static::where('is_active', true)->value('provider') ?? 'gogoz';
    }

    /**
     * Atomically flips the active flag so exactly one row is ever active.
     */
    public static function activate(string $provider): void
    {
        DB::transaction(function () use ($provider) {
            static::query()->update(['is_active' => false]);
            self::forProvider($provider)->update(['is_active' => true]);
        });
    }
}
