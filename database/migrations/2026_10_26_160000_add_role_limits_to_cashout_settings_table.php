<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashout_settings', function (Blueprint $table) {
            $table->json('role_limits')->nullable()->after('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('cashout_settings', function (Blueprint $table) {
            $table->dropColumn('role_limits');
        });
    }
};
