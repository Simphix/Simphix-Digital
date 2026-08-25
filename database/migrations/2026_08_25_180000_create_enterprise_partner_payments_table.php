<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_partner_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('enterprise_partner_applications')->cascadeOnDelete();
            // Denormalized from the application for a fast webhook lookup
            // keyed straight off account_number, with no join needed.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('expected_amount', 15, 2);
            $table->string('account_reference')->unique();
            $table->string('account_number')->nullable()->index();
            $table->string('account_name')->nullable();
            $table->string('bank_name')->default('PalmPay');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_partner_payments');
    }
};
