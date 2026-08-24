<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A platform wallet's cash-out request, awaiting super_admin approval
     * before the PalmPay payout is sent — mirrors cash_out_requests, but
     * scoped to platform_wallets instead of a user's personal wallet.
     * bank_code/bank_name/account_no/account_name are a SNAPSHOT of
     * platform_withdrawal_account at request time, same rationale as
     * cash_out_requests's own snapshot.
     */
    public function up(): void
    {
        Schema::create('platform_cash_out_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_wallet_transaction_id')->nullable()
                ->constrained('platform_wallet_transactions', 'id', 'pcor_wallet_transaction_id_foreign')
                ->nullOnDelete();
            $table->string('bank_code');
            $table->string('bank_name');
            $table->string('account_no');
            $table->string('account_name');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_cash_out_requests');
    }
};
