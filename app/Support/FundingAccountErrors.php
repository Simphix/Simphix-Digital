<?php

namespace App\Support;

/**
 * Turns the payment provider's rejection of a funding account into a message
 * a user can act on, without ever passing the provider's own text through.
 *
 * Only problems with the user's own details are translated (name that does
 * not match the BVN/NIN record, wrong date of birth, an ID that cannot be
 * verified, an ID already linked to another account, a bad address). The
 * wording is ours, so nothing about the vendor, its API or our systems can
 * leak. Anything unrecognised returns null and the caller shows a generic
 * message instead.
 */
final class FundingAccountErrors
{
    /** @param iterable<mixed> $messages the provider's message(s); a string or a list of strings */
    public static function forUser(iterable $messages): ?string
    {
        $found = [];

        foreach ($messages as $raw) {
            $translated = is_string($raw) ? self::translate($raw) : null;

            if ($translated !== null) {
                $found[$translated] = true;
            }
        }

        // Two sentences is plenty; more would just be noise.
        return $found ? implode(' ', array_slice(array_keys($found), 0, 2)) : null;
    }

    /** The provider's `message`, which can be a string or a list, as a list of strings. */
    public static function normalize(mixed $message): array
    {
        $flat = [];

        // array_walk_recursive() only accepts arrays, so wrap a bare string.
        $items = is_array($message) ? $message : [$message];

        array_walk_recursive($items, function ($item) use (&$flat) {
            if (is_scalar($item) && trim((string) $item) !== '') {
                $flat[] = trim((string) $item);
            }
        });

        return $flat;
    }

    private static function translate(string $raw): ?string
    {
        $text = strtolower($raw);

        $bvn = (bool) preg_match('/\bbvn\b/', $text);
        $nin = (bool) preg_match('/\bnin\b/', $text);
        $id = $bvn && $nin ? 'BVN/NIN' : ($nin ? 'NIN' : ($bvn ? 'BVN' : 'ID'));
        $aboutId = $bvn || $nin || str_contains($text, 'identification');

        $mismatch = (bool) preg_match('/(not match|does not match|do not match|doesn.t match|mismatch|different from|not the same)/', $text);

        if ($aboutId && $mismatch && preg_match('/(first\s*name|last\s*name|surname|full\s*name|\bname)/', $text)) {
            return "The first and last name on your account do not match the name on your {$id} record. Update your name in Profile Settings to match it exactly, then try again.";
        }

        if ($mismatch && preg_match('/(date of birth|\bdob\b|birth)/', $text)) {
            return "The date of birth you entered does not match your {$id} record. Please check it and try again.";
        }

        if ($aboutId && preg_match('/(already|in use|been used|registered|linked|duplicate)/', $text)) {
            return "This {$id} is already linked to another account.";
        }

        if ($aboutId && preg_match('/(invalid|not found|incorrect|not valid|could not|unable|failed|verification|not exist)/', $text)) {
            return "The {$id} you entered could not be verified. Please check it and try again.";
        }

        if (preg_match('/address/', $text)) {
            return 'Please enter a valid residential address in your profile, then try again.';
        }

        return null;
    }
}
