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
        Schema::create('travel_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category'); // 'umrah', 'hajj', 'international'
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('price_range')->nullable();
            $table->string('duration')->nullable();
            $table->json('highlights')->nullable();
            $table->boolean('is_featured')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('travel_package_id')->nullable()->constrained('travel_packages')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('package_name');
            $table->string('package_type')->default('umrah')->index(); // 'umrah', 'hajj', 'international', 'custom'
            $table->unsignedInteger('number_of_travelers')->default(1);
            $table->string('departure_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index(); // 'pending', 'reviewing', 'contacted', 'approved', 'completed', 'cancelled'
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
        Schema::dropIfExists('travel_requests');
        Schema::dropIfExists('travel_packages');
    }
};
