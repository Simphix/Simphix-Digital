<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Normalizes and validates Nigerian mobile numbers to a single canonical
 * local format (0XXXXXXXXXX) for storage, and converts to the
 * country-code-prefixed format Termii's SMS API requires ("234XXXXXXXXXX",
 * no leading "+"). Centralizing this here means every phone-capturing form
 * validates the same shape, and the Termii vendor boundary is the only
 * place the international-format transform happens.
 */
class PhoneNumber
{
    private const LOCAL_PATTERN = '/^0[789]\d{9}$/';

    /**
     * Reduces arbitrary user input to canonical local format (0XXXXXXXXXX),
     * or null if it can't be made to fit a valid Nigerian mobile shape.
     * Never throws — callers must handle null explicitly.
     */
    public static function sanitizeLocal(string $raw): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', $raw);

        if (str_starts_with($digits, '+234')) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '234')) {
            $digits = '0' . substr($digits, 3);
        } else {
            $digits = ltrim($digits, '+');
        }

        return preg_match(self::LOCAL_PATTERN, $digits) === 1 ? $digits : null;
    }

    /**
     * Converts to the format Termii's /api/sms/send expects: country code
     * 234 + the 10 digits after the leading 0, no "+". Returns null if the
     * input isn't a valid Nigerian number.
     */
    public static function toTermiiFormat(string $raw): ?string
    {
        $local = self::sanitizeLocal($raw);

        return $local === null ? null : '234' . substr($local, 1);
    }

    public static function isValid(string $raw): bool
    {
        return self::sanitizeLocal($raw) !== null;
    }

    /**
     * Closure-based validation rule for use directly inside a
     * $request->validate([...]) rules array.
     */
    public static function validationRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (!self::isValid((string) $value)) {
                $fail('The :attribute must be a valid Nigerian phone number.');
            }
        };
    }

    /**
     * Normalizes $request->input($field) in place (via $request->merge())
     * before validation runs, so unique/format checks see the canonical
     * form. No-ops (leaves input untouched) if the field is absent or
     * unparseable — the subsequent validationRule() check is what produces
     * the user-facing rejection, not a silent mutation here.
     */
    public static function normalizeRequestInput(Request $request, string $field = 'phone'): void
    {
        if (!$request->filled($field)) {
            return;
        }

        $normalized = self::sanitizeLocal((string) $request->input($field));

        if ($normalized !== null) {
            $request->merge([$field => $normalized]);
        }
    }
}
