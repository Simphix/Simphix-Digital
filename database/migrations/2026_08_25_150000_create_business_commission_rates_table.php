<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_commission_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->cascadeOnDelete();
            // Null = the EP's default rate for any category not otherwise
            // overridden below, mirroring ServicePrice's own nullable-category
            // convention.
            $table->foreignId('service_fields_id')->nullable()->constrained('service_fields')->nullOnDelete();
            $table->decimal('price', 15, 2);
            $table->decimal('commission', 15, 2);
            $table->timestamps();

            $table->unique(['business_id', 'service_fields_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_commission_rates');
    }
};
