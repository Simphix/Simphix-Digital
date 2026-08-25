<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_upgrade_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('minimum_wallet_balance', 15, 2)->default(10000.00);
            $table->decimal('partner_commission_amount', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_upgrade_settings');
    }
};
