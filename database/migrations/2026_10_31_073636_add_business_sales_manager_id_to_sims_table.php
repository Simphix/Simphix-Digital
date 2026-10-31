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
        Schema::table('sims', function (Blueprint $table) {
            $table->foreignId('business_sales_manager_id')
                ->nullable()
                ->after('coordinator_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_sales_manager_id');
        });
    }
};
