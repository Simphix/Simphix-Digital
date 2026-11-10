<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove legacy (Fadeelposdatasub) from provider settings
        DB::table('sme_data_provider_settings')->where('provider', 'legacy')->delete();

        // 2. Set all existing providers to inactive
        DB::table('sme_data_provider_settings')->update(['is_active' => false]);

        // 3. Create or activate KVDATAAPI as the default active provider
        DB::table('sme_data_provider_settings')->updateOrInsert(
            ['provider' => 'kvdata'],
            [
                'base_url'   => config('services.kvdata.base_url', 'https://kvdataapi.net/api/data/'),
                'api_key'    => config('services.kvdata.api_key'),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 4. Update existing legacy plans to kvdata if no collision, or replicate active plans under kvdata
        $legacyPlans = DB::table('sme_data')->where('provider', 'legacy')->get();
        foreach ($legacyPlans as $plan) {
            $existingKvdataPlan = DB::table('sme_data')
                ->where('provider', 'kvdata')
                ->where('data_id', $plan->data_id)
                ->first();

            if (!$existingKvdataPlan) {
                DB::table('sme_data')->where('id', $plan->id)->update(['provider' => 'kvdata']);
            } else {
                DB::table('sme_data')->where('id', $plan->id)->delete();
            }
        }

        // If kvdata has no plans, duplicate existing plans from other providers so plans are immediately available
        $kvdataCount = DB::table('sme_data')->where('provider', 'kvdata')->count();
        if ($kvdataCount === 0) {
            $otherPlans = DB::table('sme_data')->where('provider', 'gogoz')->get();
            foreach ($otherPlans as $plan) {
                $targetDataId = str_replace(['_gogoz', '_legacy'], '', $plan->data_id);
                $exists = DB::table('sme_data')->where('provider', 'kvdata')->where('data_id', $targetDataId)->exists();
                if (!$exists) {
                    DB::table('sme_data')->insert([
                        'provider'       => 'kvdata',
                        'data_id'        => $targetDataId,
                        'network'        => $plan->network,
                        'plan_type'      => $plan->plan_type,
                        'personal_price' => $plan->personal_price,
                        'agent_price'    => $plan->agent_price,
                        'partner_price'  => $plan->partner_price,
                        'business_price' => $plan->business_price,
                        'vendor_amount'  => $plan->vendor_amount ?? 0,
                        'size'           => $plan->size,
                        'validity'       => $plan->validity,
                        'status'         => $plan->status,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }
        }

        // 5. Update default value of provider column on sme_data table
        if (Schema::hasColumn('sme_data', 'provider')) {
            Schema::table('sme_data', function (Blueprint $table) {
                $table->string('provider')->default('kvdata')->change();
            });
        }
    }

    public function down(): void
    {
        DB::table('sme_data_provider_settings')->where('provider', 'kvdata')->delete();
        DB::table('sme_data')->where('provider', 'kvdata')->delete();

        // Reactivate gogoz as fallback
        DB::table('sme_data_provider_settings')->where('provider', 'gogoz')->update(['is_active' => true]);
    }
};
