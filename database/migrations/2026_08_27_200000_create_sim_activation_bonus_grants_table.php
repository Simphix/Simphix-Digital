<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotency ledger for the SIM activation data bonus: one row per
     * (sim, rolling-anniversary period) that actually received a grant. The
     * composite unique constraint is the race-proof backstop — see
     * App\Support\SimBonusPeriod and App\Jobs\GrantActivationBonusData.
     */
    public function up(): void
    {
        Schema::create('sim_activation_bonus_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sim_id')->constrained('sims')->cascadeOnDelete();
            $table->unsignedInteger('period_index');
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->dateTime('granted_at');
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->string('status')->default('completed');
            $table->timestamps();

            $table->unique(['sim_id', 'period_index'], 'sim_bonus_grant_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_activation_bonus_grants');
    }
};
