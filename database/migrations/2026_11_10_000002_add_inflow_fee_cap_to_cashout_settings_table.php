<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashout_settings', function (Blueprint $table) {
            $table->decimal('inflow_fee_cap', 10, 2)->nullable()->default(300.00)->after('inflow_fee_type');
        });

        // Set default 0.3% capped at ₦300 for existing settings rows if not already custom-configured
        DB::table('cashout_settings')
            ->where(function ($query) {
                $query->whereNull('inflow_fee')
                    ->orWhere('inflow_fee', 0.00);
            })
            ->update([
                'inflow_fee' => 0.30,
                'inflow_fee_type' => 'percent',
                'inflow_fee_cap' => 300.00,
                'inflow_fee_description' => 'Wallet Funding Fee',
            ]);
    }

    public function down(): void
    {
        Schema::table('cashout_settings', function (Blueprint $table) {
            $table->dropColumn('inflow_fee_cap');
        });
    }
};
