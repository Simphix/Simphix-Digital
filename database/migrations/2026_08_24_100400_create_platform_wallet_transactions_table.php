<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger for platform_wallets. `related` traces each row back to the
     * SIM/transaction that caused it (no metadata-JSON parsing needed);
     * `source` is a free-form slug (sim_activation, airtime_topup, ...) so a
     * future revenue-generating service just picks a new slug, no schema
     * change — see docs/platform-wallets.md.
     */
    public function up(): void
    {
        Schema::create('platform_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_ref')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('type')->comment('credit, debit, cashout_debit, cashout_refund');
            $table->string('source')->comment('sim_activation, airtime_topup, verification_bvn, cashout, ...');
            $table->nullableMorphs('related');
            $table->text('description')->nullable();
            $table->decimal('old_balance', 15, 2);
            $table->decimal('new_balance', 15, 2);
            $table->string('performed_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_wallet_transactions');
    }
};
