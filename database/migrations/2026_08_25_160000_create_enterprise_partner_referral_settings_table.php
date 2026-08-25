<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_partner_referral_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('regional_manager_amount', 15, 2)->default(0.00);
            $table->decimal('coordinator_amount', 15, 2)->default(0.00);
            $table->decimal('partner_amount', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_partner_referral_settings');
    }
};
