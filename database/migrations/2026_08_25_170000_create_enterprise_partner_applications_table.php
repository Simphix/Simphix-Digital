<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_partner_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('sim_category')->default('POS SIM');
            $table->string('provider')->default('mtn');
            $table->unsignedInteger('quantity')->default(100);
            $table->decimal('unit_price_snapshot', 15, 2);
            $table->decimal('total_amount_snapshot', 15, 2);
            $table->string('status')->default('pending_payment');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_partner_applications');
    }
};
