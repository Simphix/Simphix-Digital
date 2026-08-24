<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform-owned revenue pools (welfare, utilities revenue, ...), kept
     * separate from the per-user `wallets` table since `transactions.user_id`
     * and `reports.user_id` are both NOT NULL — a platform-level credit has
     * no owning user and can't live there. One row per `type`; new wallet
     * kinds just need a new type slug, no migration.
     */
    public function up(): void
    {
        Schema::create('platform_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('name');
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('total_credited', 15, 2)->default(0.00);
            $table->decimal('total_debited', 15, 2)->default(0.00);
            $table->string('currency', 10)->default('NGN');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_wallets');
    }
};
