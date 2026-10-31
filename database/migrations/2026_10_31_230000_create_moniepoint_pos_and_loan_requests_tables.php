<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Moniepoint POS Requests table
        Schema::create('moniepoint_pos_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('pos_type'); // 'smart_pos', 'traditional_pos', 'mini_pos'
            $table->string('business_name')->nullable();
            $table->text('delivery_address')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index(); // 'pending', 'under_review', 'contacted', 'dispatched', 'delivered', 'declined'
            $table->text('admin_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Loan Requests table (for Moniepoint business owners)
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable(); // Optional email
            $table->string('moniepoint_account_number')->index();
            $table->decimal('amount_requested', 14, 2);
            $table->string('business_name')->nullable();
            $table->string('loan_tenure')->nullable(); // e.g. '30_days', '60_days', '90_days'
            $table->text('purpose')->nullable();
            $table->string('status')->default('pending')->index(); // 'pending', 'under_review', 'contacted', 'approved', 'disbursed', 'declined'
            $table->text('admin_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_requests');
        Schema::dropIfExists('moniepoint_pos_requests');
    }
};
