<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single global settings row (id=1) controlling the fixed amount
     * credited to the Platform Welfare Wallet on every SIM activation.
     */
    public function up(): void
    {
        Schema::create('platform_welfare_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount_per_activation', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_welfare_settings');
    }
};
