<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fintava_wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('virtual_account_id')->nullable()->constrained('virtual_accounts')->nullOnDelete();
            $table->string('deposit_reference')->index();
            $table->string('customer_reference')->unique();
            $table->string('sender_account');
            $table->string('receiver_account');
            $table->decimal('amount', 12, 2);
            $table->decimal('fee_charged', 10, 2)->default(0.00);
            $table->decimal('net_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('pending')->index();
            $table->string('provider_reference')->nullable()->index();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fintava_wallet_transfers');
    }
};
