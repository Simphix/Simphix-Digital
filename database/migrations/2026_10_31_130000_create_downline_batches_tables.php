<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downline_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_reference')->unique();
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_role');
            $table->string('source_type')->default('outgoing_manager'); // outgoing_manager, incoming_manager
            $table->foreignId('master_manager_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->unsignedInteger('initial_count')->default(0);
            $table->unsignedInteger('current_count')->default(0);
            $table->unsignedInteger('transferred_count')->default(0);
            $table->string('status')->default('held'); // held, partially_transferred, transferred
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_user_id', 'status']);
        });

        Schema::create('downline_batch_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('downline_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('original_referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('held'); // held, transferred
            $table->foreignId('transferred_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downline_batch_users');
        Schema::dropIfExists('downline_batches');
    }
};
