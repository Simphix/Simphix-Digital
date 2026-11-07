<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $walletIds = DB::table('platform_wallets')
            ->where('type', 'utilities_revenue')
            ->pluck('id');

        if ($walletIds->isNotEmpty()) {
            DB::table('platform_wallet_transactions')
                ->whereIn('platform_wallet_id', $walletIds)
                ->delete();

            DB::table('platform_wallets')
                ->whereIn('id', $walletIds)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible
    }
};
