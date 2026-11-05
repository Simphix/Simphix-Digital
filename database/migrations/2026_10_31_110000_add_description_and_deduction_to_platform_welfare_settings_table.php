<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_welfare_settings', function (Blueprint $table) {
            $table->string('welfare_type', 20)->default('fixed')->after('amount_per_activation');
            $table->string('description')->nullable()->default('Platform Welfare Contribution')->after('welfare_type');
            $table->boolean('deduct_from_sim_operations')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('platform_welfare_settings', function (Blueprint $table) {
            $table->dropColumn(['welfare_type', 'description', 'deduct_from_sim_operations']);
        });
    }
};
