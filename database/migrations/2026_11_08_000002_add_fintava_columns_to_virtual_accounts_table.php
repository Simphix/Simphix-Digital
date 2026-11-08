<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('virtual_accounts', 'provider_customer_id')) {
                $table->string('provider_customer_id')->nullable()->after('account_reference')->index();
            }
            if (! Schema::hasColumn('virtual_accounts', 'provider_wallet_id')) {
                $table->string('provider_wallet_id')->nullable()->after('provider_customer_id');
            }
            if (! Schema::hasColumn('virtual_accounts', 'provider_email')) {
                $table->string('provider_email')->nullable()->after('provider_wallet_id');
            }
            if (! Schema::hasColumn('virtual_accounts', 'provider_phone')) {
                $table->string('provider_phone')->nullable()->after('provider_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('virtual_accounts', 'provider_customer_id')) {
                $table->dropIndex(['provider_customer_id']);
                $drop[] = 'provider_customer_id';
            }
            if (Schema::hasColumn('virtual_accounts', 'provider_wallet_id')) {
                $drop[] = 'provider_wallet_id';
            }
            if (Schema::hasColumn('virtual_accounts', 'provider_email')) {
                $drop[] = 'provider_email';
            }
            if (Schema::hasColumn('virtual_accounts', 'provider_phone')) {
                $drop[] = 'provider_phone';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
