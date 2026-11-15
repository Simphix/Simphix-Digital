<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $services = [
            [
                'name'        => 'simcard',
                'description' => 'SIM Services, requests, inventory and activations',
                'aliases'     => ['simcard', 'sim', 'sims', 'sim_services', 'sim services', 'sim_plan', 'sim plan'],
            ],
            [
                'name'        => 'verification',
                'description' => 'BVN, NIN and identity verification services',
                'aliases'     => ['verification', 'identity_verification', 'identity verification'],
            ],
            [
                'name'        => 'airtime',
                'description' => 'Airtime top-up services',
                'aliases'     => ['airtime', 'vtu'],
            ],
            [
                'name'        => 'smedata',
                'description' => 'SME data subscription services',
                'aliases'     => ['smedata', 'sme_data', 'sme data', 'data'],
            ],
            [
                'name'        => 'cac',
                'description' => 'CAC business registration services',
                'aliases'     => ['cac', 'cac_registration', 'cac registration'],
            ],
            [
                'name'        => 'travel',
                'description' => 'Travel & Tours Packages (Umrah, Hajj & International Travels)',
                'aliases'     => ['travel', 'travel_and_tours', 'travel & tours', 'travel packages'],
            ],
            [
                'name'        => 'moniepoint_pos',
                'description' => 'Moniepoint POS Hardware & Terminal Requests',
                'aliases'     => ['moniepoint_pos', 'moniepoint pos', 'pos', 'pos_request', 'pos request'],
            ],
            [
                'name'        => 'loans',
                'description' => 'Business & Working Capital Loans',
                'aliases'     => ['loans', 'loan', 'business_loans', 'business loans'],
            ],
        ];

        foreach ($services as $svc) {
            $aliases = array_map('strtolower', $svc['aliases']);
            $existing = DB::table('services')
                ->whereRaw('LOWER(name) IN (' . implode(',', array_fill(0, count($aliases), '?')) . ')', $aliases)
                ->first();

            if (! $existing) {
                DB::table('services')->insert([
                    'name' => $svc['name'],
                    'description' => $svc['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Service controls may have acquired fields and pricing after deployment.
    }
};
