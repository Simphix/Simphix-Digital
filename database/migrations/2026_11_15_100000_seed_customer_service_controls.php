<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $services = [
            ['name' => 'simcard', 'description' => 'SIM services, requests, inventory and activations', 'aliases' => ['simcard', 'sim', 'sims', 'sim_services', 'sim services', 'sim_plan', 'sim plan']],
            ['name' => 'verification', 'description' => 'BVN, NIN and identity verification services', 'aliases' => ['verification', 'identity_verification', 'identity verification']],
            ['name' => 'airtime', 'description' => 'Airtime top-up services', 'aliases' => ['airtime', 'vtu']],
            ['name' => 'smedata', 'description' => 'SME data subscription services', 'aliases' => ['smedata', 'sme_data', 'sme data', 'data']],
            ['name' => 'cac', 'description' => 'CAC business registration services', 'aliases' => ['cac', 'cac_registration', 'cac registration']],
            ['name' => 'travel', 'description' => 'Travel & Tours packages and requests', 'aliases' => ['travel', 'travel_and_tours', 'travel & tours', 'travel packages']],
            ['name' => 'moniepoint_pos', 'description' => 'Moniepoint POS hardware and requests', 'aliases' => ['moniepoint_pos', 'moniepoint pos', 'pos', 'pos_request', 'pos request']],
            ['name' => 'loans', 'description' => 'Business and working capital loan requests', 'aliases' => ['loans', 'loan', 'business_loans', 'business loans']],
        ];

        foreach ($services as $service) {
            $aliases = array_map('strtolower', $service['aliases']);
            $placeholders = implode(',', array_fill(0, count($aliases), '?'));
            $exists = DB::table('services')
                ->whereRaw("LOWER(name) IN ({$placeholders})", $aliases)
                ->exists();

            if (! $exists) {
                DB::table('services')->insert([
                    'name' => $service['name'],
                    'description' => $service['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep service controls because they may have fields and prices attached.
    }
};