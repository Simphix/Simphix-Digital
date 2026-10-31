<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashout_settings', function (Blueprint $table) {
            // Cashout charges & threshold
            $table->decimal('cashout_fee', 10, 2)->default(0.00)->after('role_limits');
            $table->string('cashout_fee_type', 20)->default('fixed')->after('cashout_fee');
            $table->string('cashout_fee_description')->nullable()->default('Withdrawal Processing Fee')->after('cashout_fee_type');
            $table->decimal('cashout_threshold_amount', 12, 2)->nullable()->default(9999.99)->after('cashout_fee_description');
            $table->decimal('cashout_threshold_fee', 10, 2)->default(50.00)->after('cashout_threshold_amount');
            $table->string('cashout_threshold_description')->nullable()->default('Transaction Levy / Stamp Duty')->after('cashout_threshold_fee');

            // Inflow charges & threshold
            $table->decimal('inflow_fee', 10, 2)->default(0.00)->after('cashout_threshold_description');
            $table->string('inflow_fee_type', 20)->default('fixed')->after('inflow_fee');
            $table->string('inflow_fee_description')->nullable()->default('Wallet Funding Fee')->after('inflow_fee_type');
            $table->decimal('inflow_threshold_amount', 12, 2)->nullable()->default(9999.99)->after('inflow_fee_description');
            $table->decimal('inflow_threshold_fee', 10, 2)->default(50.00)->after('inflow_threshold_amount');
            $table->string('inflow_threshold_description')->nullable()->default('Electronic Money Transfer Levy')->after('inflow_threshold_fee');
        });
    }

    public function down(): void
    {
        Schema::table('cashout_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cashout_fee',
                'cashout_fee_type',
                'cashout_fee_description',
                'cashout_threshold_amount',
                'cashout_threshold_fee',
                'cashout_threshold_description',
                'inflow_fee',
                'inflow_fee_type',
                'inflow_fee_description',
                'inflow_threshold_amount',
                'inflow_threshold_fee',
                'inflow_threshold_description',
            ]);
        });
    }
};
