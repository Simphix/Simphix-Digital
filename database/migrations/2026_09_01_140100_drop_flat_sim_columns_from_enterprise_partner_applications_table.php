<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The application row's own sim_category/provider/quantity/unit_price
     * columns move to the new enterprise_partner_application_items table so
     * one application can hold multiple SIM category/network line items
     * (the "cart") instead of exactly one. Any existing application with a
     * real quantity is backfilled into a single corresponding item row
     * before the columns disappear, so no in-flight order silently loses
     * its line item.
     */
    public function up(): void
    {
        $applications = DB::table('enterprise_partner_applications')->where('quantity', '>', 0)->get();

        foreach ($applications as $application) {
            DB::table('enterprise_partner_application_items')->insert([
                'application_id' => $application->id,
                'sim_category' => $application->sim_category,
                'provider' => $application->provider,
                'quantity' => $application->quantity,
                'unit_price_snapshot' => $application->unit_price_snapshot,
                'line_total_snapshot' => $application->quantity * $application->unit_price_snapshot,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('enterprise_partner_applications', function (Blueprint $table) {
            $table->dropColumn(['sim_category', 'provider', 'quantity', 'unit_price_snapshot']);
        });
    }

    public function down(): void
    {
        // Backfilled item data isn't reconstructed here — the items stay in
        // enterprise_partner_application_items (dropped by its own
        // migration's down(), not this one) untouched by this rollback.
        Schema::table('enterprise_partner_applications', function (Blueprint $table) {
            $table->string('sim_category')->nullable()->after('user_id');
            $table->string('provider')->nullable()->after('sim_category');
            $table->unsignedInteger('quantity')->nullable()->after('provider');
            $table->decimal('unit_price_snapshot', 15, 2)->nullable()->after('quantity');
        });
    }
};
