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
        Schema::table('sim_activation_bonus_grants', function (Blueprint $table) {
            $table->dateTime('granted_at')->nullable()->change();
            $table->dateTime('triggered_at')->nullable()->after('period_end');
            $table->dateTime('resolved_at')->nullable()->after('granted_at');
            $table->string('resolution_type', 32)->nullable()->after('resolved_at');
            $table->foreignId('admin_id')->nullable()->after('resolution_type')->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable()->after('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sim_activation_bonus_grants', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn([
                'triggered_at',
                'resolved_at',
                'resolution_type',
                'admin_id',
                'admin_notes',
            ]);
            $table->dateTime('granted_at')->nullable(false)->change();
        });
    }
};
