<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The single shared bank account platform wallet cash-outs pay into —
     * deliberately not tied to any individual admin's personal
     * withdrawal_accounts row, since platform revenue isn't any one
     * person's money. See docs/platform-wallets.md.
     */
    public function up(): void
    {
        Schema::create('platform_withdrawal_account', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code');
            $table->string('bank_name');
            $table->string('account_no');
            $table->string('account_name');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_withdrawal_account');
    }
};
