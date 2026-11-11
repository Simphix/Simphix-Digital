<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SafeEncrypted implements CastsAttributes
{
    /**
     * Cast the stored encrypted value to plain text.
     * Gracefully catches DecryptException (invalid MAC, changed APP_KEY, corrupted ciphertext)
     * so that the application/view does not crash with a fatal 500 error.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::warning("SafeEncrypted: Decryption failed for '{$key}' on " . class_basename($model) . " (ID: {$model->getKey()}). APP_KEY may have changed or data was encrypted with a different key.", [
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning("SafeEncrypted: Unexpected error decrypting '{$key}': " . $e->getMessage());

            return null;
        }
    }

    /**
     * Encrypt the plain text value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString($value);
    }
}
