<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One demo account per role, so every role-gated feature (onboarding
 * hierarchy, SIM assignment cascade, commissions, admin screens) has
 * real data to sign in and test with.
 *
 * All accounts share the password "Password123!" and use the email
 * pattern demo+<role>@smartsim.test.
 *
 * The hierarchy roles are chained together via referred_by so the
 * "My Network" downline view and the SIM assignment cascade both have
 * a real Regional Manager -> Coordinator -> Partner -> User chain to
 * onboard/assign against: demo+regional_manager -> demo+coordinator ->
 * demo+partner -> demo+personal.
 */
class RoleAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Password123!');

        // Hierarchy chain, seeded in order so each can reference the one before it.
        $regionalManager = $this->makeUser('regional_manager', 'Regina', 'Manager', $password);
        $coordinator = $this->makeUser('coordinator', 'Cody', 'Ordinator', $password, $regionalManager->id);
        $partner = $this->makeUser('partner', 'Percy', 'Partner', $password, $coordinator->id);
        $personal = $this->makeUser('personal', 'Uche', 'User', $password, $partner->id);

        // Standalone legacy / admin roles, unrelated to the hierarchy chain.
        $this->makeUser('agent', 'Ade', 'Agent', $password, $partner->id);
        $this->makeUser('business', 'Bola', 'Business', $password, $partner->id);
        $this->makeUser('business_sales_manager', 'Brian', 'SalesManager', $password);
        $this->makeUser('staff', 'Sam', 'Staff', $password);
        $this->makeUser('checker', 'Chika', 'Checker', $password);
        $this->makeUser('super_admin', 'Ada', 'Admin', $password);
    }

    private function makeUser(string $role, string $firstName, string $lastName, string $password, ?int $referredBy = null): User
    {
        $email = 'demo+' . $role . '@smartsim.test';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                // Always 11 digits (070 + 8-digit hash of the role) to match the app's phone number format.
                'phone' => '070' . str_pad((string) (crc32($role) % 100000000), 8, '0', STR_PAD_LEFT),
                'password' => $password,
                'role' => $role,
                'status' => 'active',
                'email_verified_at' => now(),
                'referral_code' => strtoupper(Str::random(8)),
                'referred_by' => $referredBy,
                'limit' => 20000.00,
            ]
        );

        Wallet::updateOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 50000.00,
                'bonus' => 0.00,
                'hold_amount' => 0.00,
                'total_credited' => 0.00,
                'total_debited' => 0.00,
                'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
                'currency' => 'NGN',
                'daily_limit' => 100000.00,
                'monthly_limit' => 1000000.00,
                'status' => 'active',
                'is_locked' => false,
                'last_activity' => now(),
            ]
        );

        return $user;
    }
}
