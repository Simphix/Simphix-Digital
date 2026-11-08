<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The email and phone number sent to Fintava when creating a customer.
 *
 * Fintava refuses a customer whose email or phone already belongs to another
 * customer ("Customer with email or Phone exists"), which real people hit
 * constantly (shared phones, a retry after a failed attempt, a number reused
 * from an old account). So Fintava is given a fresh, random, unique pair for
 * every funding account instead of the user's own. They are purely internal:
 * saved beside the account (virtual_accounts.provider_email / provider_phone)
 * so the customer can be traced later, and never shown to or emailed to the
 * user. The user's real email and phone stay on their own profile untouched.
 */
final class FintavaCustomerIdentity
{
    /** Real Nigerian mobile prefixes, so the number passes format validation. */
    private const PHONE_PREFIXES = ['701', '802', '803', '805', '806', '807', '808', '810', '811', '813', '814', '816', '902', '903', '906', '913', '915'];

    /** @return array{email: string, phone: string} */
    public static function generate(User $user): array
    {
        $domain = (string) config('services.fintava.internal_email_domain', 'simphix.ng');

        do {
            $email = 'fv' . $user->id . '.' . Str::lower(Str::random(10)) . '@' . $domain;
            $phone = '0' . self::PHONE_PREFIXES[array_rand(self::PHONE_PREFIXES)] . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (self::alreadyUsed($email, $phone));

        return ['email' => $email, 'phone' => $phone];
    }

    private static function alreadyUsed(string $email, string $phone): bool
    {
        return DB::table('virtual_accounts')
            ->where('provider_email', $email)
            ->orWhere('provider_phone', $phone)
            ->exists();
    }
}
