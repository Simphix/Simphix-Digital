<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
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
        $this->makeUser('support', 'Sule', 'Support', $password);
        $this->makeUser('super_admin', 'Ada', 'Admin', $password);

        $this->seedJurisdictions($regionalManager, $coordinator);
    }

    private function seedJurisdictions(User $regionalManager, User $coordinator): void
    {
        // 6 Geopolitical Zones with States
        $regions = [
            'South West' => ['Lagos', 'Ogun', 'Oyo', 'Osun', 'Ondo', 'Ekiti'],
            'South East' => ['Abia', 'Anambra', 'Ebonyi', 'Enugu', 'Imo'],
            'South South' => ['Akwa Ibom', 'Bayelsa', 'Cross River', 'Delta', 'Edo', 'Rivers'],
            'North Central' => ['Benue', 'Kogi', 'Kwara', 'Nasarawa', 'Niger', 'Plateau', 'FCT'],
            'North West' => ['Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Jigawa', 'Sokoto', 'Zamfara'],
            'North East' => ['Adamawa', 'Bauchi', 'Borno', 'Gombe', 'Taraba', 'Yobe'],
        ];

        foreach ($regions as $regionName => $stateNames) {
            $region = Region::firstOrCreate(
                ['name' => $regionName],
                ['regional_manager_id' => $regionName === 'South West' ? $regionalManager->id : null]
            );

            if ($regionName === 'South West' && $region->regional_manager_id !== $regionalManager->id) {
                $region->update(['regional_manager_id' => $regionalManager->id]);
            }

            foreach ($stateNames as $stateName) {
                State::firstOrCreate(
                    ['name' => $stateName],
                    ['region_id' => $region->id]
                );
            }
        }

        // Seed sample Senatorial Districts
        $lagos = State::where('name', 'Lagos')->first();
        if ($lagos) {
            SenatorialDistrict::updateOrCreate(
                ['name' => 'Lagos Central'],
                ['state_id' => $lagos->id, 'coordinator_id' => $coordinator->id]
            );
            SenatorialDistrict::firstOrCreate(
                ['name' => 'Lagos West'],
                ['state_id' => $lagos->id, 'coordinator_id' => null]
            );
            SenatorialDistrict::firstOrCreate(
                ['name' => 'Lagos East'],
                ['state_id' => $lagos->id, 'coordinator_id' => null]
            );
        }

        $ogun = State::where('name', 'Ogun')->first();
        if ($ogun) {
            SenatorialDistrict::firstOrCreate(
                ['name' => 'Ogun Central'],
                ['state_id' => $ogun->id, 'coordinator_id' => null]
            );
        }
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
                'device_registration_enabled' => false,
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
