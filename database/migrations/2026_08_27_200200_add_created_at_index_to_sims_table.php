<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * number/category/provider/status are already indexed (create_sims_and_
     * sim_requests_tables.php); regional_manager_id/coordinator_id/
     * business_id/business_agent_id/partner_id are auto-indexed by MySQL as
     * foreignId()->constrained() FKs. Only created_at, needed for the SIMs
     * Operations dashboard's date-range and trend queries, was missing.
     */
    public function up(): void
    {
        Schema::table('sims', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sims', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
