<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adjusts active SIMs and SIM subscriptions that were initialized or renewed
     * with the 365-day lifetime to the updated 360-day lifecycle (deducting 5 days).
     */
    public function up(): void
    {
        // 1. Adjust existing active SIM records where subscription_expires_at is set
        DB::table('sims')
            ->whereNotNull('subscription_expires_at')
            ->orderBy('id')
            ->chunk(200, function ($sims) {
                foreach ($sims as $sim) {
                    $newExpiresAt = \Carbon\Carbon::parse($sim->subscription_expires_at)->subDays(5);
                    DB::table('sims')
                        ->where('id', $sim->id)
                        ->update(['subscription_expires_at' => $newExpiresAt]);
                }
            });

        // 2. Adjust existing sim_subscriptions records
        DB::table('sim_subscriptions')
            ->whereNotNull('expires_at')
            ->orderBy('id')
            ->chunk(200, function ($subs) {
                foreach ($subs as $sub) {
                    $newExpiresAt = \Carbon\Carbon::parse($sub->expires_at)->subDays(5);
                    DB::table('sim_subscriptions')
                        ->where('id', $sub->id)
                        ->update(['expires_at' => $newExpiresAt]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sims')
            ->whereNotNull('subscription_expires_at')
            ->orderBy('id')
            ->chunk(200, function ($sims) {
                foreach ($sims as $sim) {
                    $newExpiresAt = \Carbon\Carbon::parse($sim->subscription_expires_at)->addDays(5);
                    DB::table('sims')
                        ->where('id', $sim->id)
                        ->update(['subscription_expires_at' => $newExpiresAt]);
                }
            });

        DB::table('sim_subscriptions')
            ->whereNotNull('expires_at')
            ->orderBy('id')
            ->chunk(200, function ($subs) {
                foreach ($subs as $sub) {
                    $newExpiresAt = \Carbon\Carbon::parse($sub->expires_at)->addDays(5);
                    DB::table('sim_subscriptions')
                        ->where('id', $sub->id)
                        ->update(['expires_at' => $newExpiresAt]);
                }
            });
    }
};
