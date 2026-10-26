<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove 9PSB from provider settings and data plans
        DB::table('sme_data_provider_settings')->where('provider', '9psb')->delete();
        DB::table('sme_data')->where('provider', '9psb')->delete();

        // 2. Set all existing providers to inactive
        DB::table('sme_data_provider_settings')->update(['is_active' => false]);

        // 3. Create or activate Gogoz Concept as the #1 provider
        DB::table('sme_data_provider_settings')->updateOrInsert(
            ['provider' => 'gogoz'],
            [
                'base_url'   => config('services.gogoz.base_url', 'https://www.gongozconcept.com/api/data/'),
                'api_key'    => config('services.gogoz.api_key'),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 4. If there are legacy plans, replicate or ensure they exist under gogoz
        $legacyPlans = DB::table('sme_data')->where('provider', 'legacy')->get();
        foreach ($legacyPlans as $plan) {
            $existingGogozPlan = DB::table('sme_data')
                ->where('provider', 'gogoz')
                ->where('data_id', $plan->data_id)
                ->first();

            if (!$existingGogozPlan) {
                DB::table('sme_data')->insert([
                    'provider'       => 'gogoz',
                    'data_id'        => $plan->data_id . '_gogoz',
                    'network'        => $plan->network,
                    'plan_type'      => $plan->plan_type,
                    'personal_price' => $plan->personal_price,
                    'agent_price'    => $plan->agent_price,
                    'partner_price'  => $plan->partner_price,
                    'business_price' => $plan->business_price,
                    'size'           => $plan->size,
                    'validity'       => $plan->validity,
                    'status'         => $plan->status,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('sme_data_provider_settings')->where('provider', 'gogoz')->delete();
        DB::table('sme_data')->where('provider', 'gogoz')->delete();

        // Reactivate legacy as fallback
        DB::table('sme_data_provider_settings')->where('provider', 'legacy')->update(['is_active' => true]);
    }
};
