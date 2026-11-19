<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimExportSettings extends Model
{
    protected $fillable = [
        'recipient_email',
        'is_active',
        'last_exported_at',
        'last_attempted_at',
        'last_export_status',
        'last_export_count',
        'last_export_recipient',
        'last_export_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_exported_at' => 'datetime',
        'last_attempted_at' => 'datetime',
        'last_export_count' => 'integer',
    ];

    /**
     * Resolves recipient email from database, falling back to environment variables.
     */
    public function getEffectiveRecipientEmail(): ?string
    {
        return $this->recipient_email
            ?: env('SIM_EXPORT_RECIPIENT_EMAIL')
            ?: env('NIGHTLY_SIMS_EXPORT_RECIPIENT')
            ?: env('ADMIN_NOTIFICATION_EMAIL');
    }

    /**
     * Checks if export is enabled via database or environment override.
     */
    public function isEffectivelyActive(): bool
    {
        if ($this->is_active) {
            return true;
        }

        return filter_var(env('SIM_EXPORT_ENABLED') ?? env('NIGHTLY_SIMS_EXPORT_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Records the outcome of an automated or manual export execution.
     */
    public function recordExportResult(string $status, ?int $count = null, ?string $recipient = null, ?string $message = null): void
    {
        $data = [
            'last_attempted_at' => now(),
            'last_export_status' => $status,
            'last_export_count' => $count,
            'last_export_recipient' => $recipient,
            'last_export_message' => $message,
        ];

        if ($status === 'success') {
            $data['last_exported_at'] = now();
        }

        $this->update($data);
    }

    /**
     * Single global settings row, created with safe (off, no recipient yet)
     * defaults on first access. Deliberately not `firstOrCreate(['id' => 1],
     * ...)` — see PlatformWelfareSettings::current() for why.
     */
    public static function current(): self
    {
        return static::first() ?? static::create(['recipient_email' => null, 'is_active' => false]);
    }
}
