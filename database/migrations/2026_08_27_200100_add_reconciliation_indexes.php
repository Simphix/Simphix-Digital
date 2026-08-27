<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every reconciler in App\Services\Reconciliation filters heavily on
     * these type/status/created_at combinations, none of which were
     * previously indexed. transactions.user_id/reports.user_id are already
     * indexed automatically (MySQL InnoDB auto-indexes foreignId()
     * constraints), so they're not repeated here.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['type', 'status', 'created_at']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['type', 'status', 'created_at']);
            $table->index('phone_number');
        });

        Schema::table('cash_out_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->index(['status', 'event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['type', 'status', 'created_at']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['type', 'status', 'created_at']);
            $table->dropIndex(['phone_number']);
        });

        Schema::table('cash_out_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropIndex(['status', 'event_type', 'created_at']);
        });
    }
};
