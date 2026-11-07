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
        Schema::create('platform_bsm_commission_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount_per_activation', 15, 2)->default(0.00);
            $table->string('commission_type', 20)->default('fixed');
            $table->string('description')->nullable()->default('BSM Central Commission');
            $table->boolean('is_active')->default(true);
            $table->boolean('deduct_from_sim_operations')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_bsm_commission_settings');
    }
};
