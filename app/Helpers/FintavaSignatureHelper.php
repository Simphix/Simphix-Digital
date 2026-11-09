<?php

namespace App\Helpers;

class FintavaSignatureHelper
{
    /**
     * Verify a Fintava webhook's `x-fintava-signature` header: HMAC-SHA512
     * over the RAW request body (not the parsed/re-encoded JSON — Fintava's
     * docs are explicit that re-serializing the payload before hashing
     * produces a different signature than the one they computed), keyed
     * with the webhook secret, compared in constant time.
     *
     * @param  string  $rawBody  $request->getContent() — must be the exact
     *                           bytes Fintava sent, not $request->all().
     */
    public static function verify(string $rawBody, ?string $signatureHeader, ?string $secret): bool
    {
        if (empty($signatureHeader) || empty($secret)) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
