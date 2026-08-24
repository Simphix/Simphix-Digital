<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-configurable platform margin per service/role price, mirroring
     * `commission` — defaults to 0 so no Utilities Revenue accrues until an
     * admin explicitly sets a margin on a service.
     */
    public function up(): void
    {
        Schema::table('service_prices', function (Blueprint $table) {
            $table->decimal('margin_percent', 5, 2)->default(0.00)->after('commission');
        });
    }

    public function down(): void
    {
        Schema::table('service_prices', function (Blueprint $table) {
            $table->dropColumn('margin_percent');
        });
    }
};
