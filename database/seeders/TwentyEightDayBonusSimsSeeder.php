<?php

namespace Database\Seeders;

use App\Models\ActivationBonusSettings;
use App\Models\Report;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SimHistory;
use App\Models\SmeData;
use App\Models\User;
use App\Support\SimStatus;
use Illuminate\Database\Seeder;

class TwentyEightDayBonusSimsSeeder extends Seeder
{
    /**
     * Seeds realistic demo SIMs that have reached the 28-day re-subscription/top-up
     * milestone with previous grant history (e.g. 1/12, 2/12, 3/12) and active
     * pending triggers waiting for admin approval on the SIM Data Top-Up desk.
     */
    public function run(): void
    {
        // 1. Ensure bonus plans and settings are enabled for MTN, AIRTEL, and GLO
        $mtnPlan = SmeData::where('network', 'MTN')->where('status', 'enabled')->first();
        if (!$mtnPlan) {
            $mtnPlan = SmeData::create([
                'data_id' => 'MTN-SME-1GB-DEMO',
                'provider' => 'kvdata',
                'network' => 'MTN',
                'plan_type' => 'SME',
                'personal_price' => 0,
                'agent_price' => 0,
                'partner_price' => 0,
                'business_price' => 0,
                'vendor_amount' => 250,
                'size' => '1GB',
                'validity' => '30 days',
                'status' => 'enabled',
            ]);
        }
        ActivationBonusSettings::forProvider('mtn')->update([
            'is_active' => true,
            'sme_data_id' => $mtnPlan->id,
        ]);

        $airtelPlan = SmeData::where('network', 'AIRTEL')->where('status', 'enabled')->first();
        if (!$airtelPlan) {
            $airtelPlan = SmeData::create([
                'data_id' => 'AIRTEL-SME-2GB-DEMO',
                'provider' => 'kvdata',
                'network' => 'AIRTEL',
                'plan_type' => 'GIFTING',
                'personal_price' => 0,
                'agent_price' => 0,
                'partner_price' => 0,
                'business_price' => 0,
                'vendor_amount' => 450,
                'size' => '2GB',
                'validity' => '30 days',
                'status' => 'enabled',
            ]);
        }
        ActivationBonusSettings::forProvider('airtel')->update([
            'is_active' => true,
            'sme_data_id' => $airtelPlan->id,
        ]);

        $gloPlan = SmeData::where('network', 'GLO')->where('status', 'enabled')->first();
        if (!$gloPlan) {
            $gloPlan = SmeData::create([
                'data_id' => 'GLO-SME-5GB-DEMO',
                'provider' => 'kvdata',
                'network' => 'GLO',
                'plan_type' => 'SME',
                'personal_price' => 0,
                'agent_price' => 0,
                'partner_price' => 0,
                'business_price' => 0,
                'vendor_amount' => 800,
                'size' => '5GB',
                'validity' => '30 days',
                'status' => 'enabled',
            ]);
        }
        ActivationBonusSettings::forProvider('glo')->update([
            'is_active' => true,
            'sme_data_id' => $gloPlan->id,
        ]);

        // 2. Identify demo users to assign SIMs to
        $partner = User::where('role', 'partner')->first() ?? User::first();
        $agent = User::where('role', 'agent')->first() ?? $partner;
        $business = User::where('role', 'business')->first() ?? $partner;
        $personal = User::where('role', 'personal')->first() ?? $partner;

        $demoSims = [
            // SIM 1: MTN - Cycle 2 Due (1/12 previously granted, 29 days elapsed)
            [
                'number' => '08031112801',
                'provider' => 'mtn',
                'category' => 'POS SIM',
                'user' => $partner,
                'days_ago' => 29,
                'completed_cycles' => 1,
                'pending_cycle' => 2,
            ],
            // SIM 2: AIRTEL - Cycle 3 Due (2/12 previously granted, 29 days elapsed since last top-up)
            [
                'number' => '08022222802',
                'provider' => 'airtel',
                'category' => 'POS SIM',
                'user' => $agent,
                'days_ago' => 57,
                'completed_cycles' => 2,
                'pending_cycle' => 3,
            ],
            // SIM 3: GLO - Cycle 2 Due (1/12 previously granted, 30 days elapsed)
            [
                'number' => '08053332803',
                'provider' => 'glo',
                'category' => 'ROUTER SIM',
                'user' => $business,
                'days_ago' => 30,
                'completed_cycles' => 1,
                'pending_cycle' => 2,
            ],
            // SIM 4: MTN - Cycle 4 Due (3/12 previously granted, 28 days elapsed)
            [
                'number' => '08034442804',
                'provider' => 'mtn',
                'category' => 'POS SIM',
                'user' => $business,
                'days_ago' => 85,
                'completed_cycles' => 3,
                'pending_cycle' => 4,
            ],
            // SIM 5: AIRTEL - Cycle 2 Due (1/12 previously granted, exactly 28 days elapsed)
            [
                'number' => '08025552805',
                'provider' => 'airtel',
                'category' => 'CAMERA SIM',
                'user' => $personal,
                'days_ago' => 28,
                'completed_cycles' => 1,
                'pending_cycle' => 2,
            ],
            // SIM 6: MTN - Active, 14 days elapsed (Not yet due, showing 1/12 in inventory)
            [
                'number' => '08036662806',
                'provider' => 'mtn',
                'category' => 'POS SIM',
                'user' => $personal,
                'days_ago' => 14,
                'completed_cycles' => 1,
                'pending_cycle' => null, // Not due
            ],
        ];

        foreach ($demoSims as $data) {
            $activatedAt = now()->subDays($data['days_ago']);

            $sim = Sim::updateOrCreate(
                ['number' => $data['number']],
                [
                    'provider' => $data['provider'],
                    'category' => $data['category'],
                    'status' => SimStatus::ACTIVATED,
                    'user_id' => $data['user']->id,
                    'partner_id' => $data['user']->role === 'partner' ? $data['user']->id : null,
                    'activated_at' => $activatedAt,
                    'created_at' => $activatedAt,
                    'updated_at' => now(),
                ]
            );

            // Ensure history record exists
            SimHistory::firstOrCreate(
                [
                    'sim_id' => $sim->id,
                    'to_status' => SimStatus::ACTIVATED,
                ],
                [
                    'from_status' => SimStatus::UNASSIGNED,
                    'actor_id' => $data['user']->id,
                    'note' => 'Initial activation for demo.',
                    'created_at' => $activatedAt,
                    'updated_at' => $activatedAt,
                ]
            );

            // Clean up any old grant records for this SIM
            SimActivationBonusGrant::where('sim_id', $sim->id)->delete();

            // Seed completed grant cycles
            for ($i = 0; $i < $data['completed_cycles']; $i++) {
                // E.g. cycle 1 was granted at activation, cycle 2 28 days later, etc.
                $grantDaysAgo = $data['days_ago'] - ($i * 28);
                $grantDate = now()->subDays(max(0, $grantDaysAgo));

                $report = Report::create([
                    'user_id' => $data['user']->id,
                    'phone_number' => $sim->number,
                    'network' => strtoupper($data['provider']),
                    'ref' => 'BONUS-DEMO-' . $sim->id . '-' . $i . '-' . uniqid(),
                    'amount' => 0.00,
                    'status' => 'completed',
                    'type' => 'activation_bonus_data',
                    'description' => "Activation bonus (" . ($i + 1) . "/12): 1GB SME Data for {$sim->number} (" . strtoupper($data['provider']) . ")",
                    'old_balance' => 0.00,
                    'new_balance' => 0.00,
                    'created_at' => $grantDate,
                    'updated_at' => $grantDate,
                ]);

                SimActivationBonusGrant::create([
                    'sim_id' => $sim->id,
                    'period_index' => $i,
                    'period_start' => $grantDate,
                    'period_end' => $grantDate->copy()->addDays(28),
                    'triggered_at' => $grantDate,
                    'granted_at' => $grantDate,
                    'resolved_at' => $grantDate,
                    'resolution_type' => 'vendor_topup',
                    'report_id' => $report->id,
                    'status' => SimActivationBonusGrant::STATUS_COMPLETED,
                    'created_at' => $grantDate,
                    'updated_at' => $grantDate,
                ]);
            }

            // Seed pending trigger if due
            if ($data['pending_cycle'] !== null) {
                $pendingIndex = $data['pending_cycle'] - 1;
                $lastTopUp = $activatedAt->copy()->addDays(($data['completed_cycles'] - 1) * 28);

                SimActivationBonusGrant::create([
                    'sim_id' => $sim->id,
                    'period_index' => $pendingIndex,
                    'period_start' => $lastTopUp,
                    'period_end' => $lastTopUp->copy()->addDays(28),
                    'triggered_at' => now()->subHours(rand(1, 12)),
                    'granted_at' => null,
                    'resolved_at' => null,
                    'resolution_type' => null,
                    'status' => SimActivationBonusGrant::STATUS_PENDING,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
