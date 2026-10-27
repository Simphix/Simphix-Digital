<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('wallets', 'virtual_promo')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropColumn('virtual_promo');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('wallets', 'virtual_promo')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->decimal('virtual_promo', 15, 2)->default(0.00)->after('bonus');
            });
        }
    }
};
