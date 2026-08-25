<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New parallel hierarchy tier: Enterprise Partner (business) -> Business
     * Agent -> personal end user. Mirrors coordinator_id/partner_id exactly —
     * same invariant applies (user_id === business_agent_id means "still
     * held by the business agent"; handing off to a personal customer
     * changes user_id but keeps business_id/business_agent_id intact so the
     * commission chain survives).
     */
    public function up(): void
    {
        Schema::table('sims', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('partner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('business_agent_id')->nullable()->after('business_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_agent_id');
            $table->dropConstrainedForeignId('business_id');
        });
    }
};
