<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Heal any SME data provider API keys or secret keys that were encrypted with
     * a mismatched/outdated APP_KEY or contain invalid MAC payloads.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sme_data_provider_settings')) {
            return;
        }

        $rows = DB::table('sme_data_provider_settings')->get();

        foreach ($rows as $row) {
            $updates = [];

            // 1. Inspect and heal api_key
            if (!empty($row->api_key)) {
                $isDecryptionValid = false;
                try {
                    Crypt::decryptString($row->api_key);
                    $isDecryptionValid = true;
                } catch (\Throwable $e) {
                    $isDecryptionValid = false;
                }

                if (!$isDecryptionValid) {
                    Log::warning("SmeDataProviderSettings: Healing invalid api_key for provider '{$row->provider}'.");
                    $envKey = config("services.{$row->provider}.api_key");
                    if (filled($envKey)) {
                        $updates['api_key'] = Crypt::encryptString($envKey);
                    } else {
                        $updates['api_key'] = null;
                    }
                }
            } else {
                // If empty in DB, attempt to pre-fill from .env config if available
                $envKey = config("services.{$row->provider}.api_key");
                if (filled($envKey)) {
                    $updates['api_key'] = Crypt::encryptString($envKey);
                }
            }

            // 2. Inspect and heal secret_key
            if (!empty($row->secret_key)) {
                $isSecretValid = false;
                try {
                    Crypt::decryptString($row->secret_key);
                    $isSecretValid = true;
                } catch (\Throwable $e) {
                    $isSecretValid = false;
                }

                if (!$isSecretValid) {
                    Log::warning("SmeDataProviderSettings: Healing invalid secret_key for provider '{$row->provider}'.");
                    $envSecret = config("services.{$row->provider}.secret_key");
                    if (filled($envSecret)) {
                        $updates['secret_key'] = Crypt::encryptString($envSecret);
                    } else {
                        $updates['secret_key'] = null;
                    }
                }
            }

            if (!empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('sme_data_provider_settings')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Healing migration is non-destructive and doesn't require reversal
    }
};
