<?php

use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\SimCustomer;
use App\Support\SimStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add subscription lifecycle columns to sims table
        Schema::table('sims', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('status')->index();
            $table->timestamp('subscription_expires_at')->nullable()->after('activated_at')->index();
        });

        // 2. Create sim_subscriptions table to track 365-day subscription periods and reactivations
        Schema::create('sim_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sim_id')->constrained('sims')->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->index();
            $table->decimal('fee', 15, 2)->default(0.00);
            $table->string('payment_method')->default('partner_wallet')->index(); // partner_wallet, funding_account, admin_wallet, initial_activation
            $table->string('wallet_type')->nullable(); // main, bonus
            $table->string('payment_reference')->nullable()->index();
            $table->string('account_number')->nullable()->index();
            $table->string('account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('status')->default('active')->index(); // pending, active, expired
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Backfill existing activated SIMs with 365-day lifetime from activation
        $activatedSims = Sim::where('status', SimStatus::ACTIVATED)->get();

        foreach ($activatedSims as $sim) {
            // Find activation timestamp from SimHistory first
            $activationHistory = SimHistory::where('sim_id', $sim->id)
                ->where('to_status', SimStatus::ACTIVATED)
                ->oldest()
                ->first();

            $actTimestamp = $activationHistory?->created_at;

            // Fallback to customer creation date
            if (! $actTimestamp) {
                $customer = SimCustomer::where('sim_id', $sim->id)->first();
                $actTimestamp = $customer?->created_at;
            }

            // Fallback to sim record timestamp
            if (! $actTimestamp) {
                $actTimestamp = $sim->created_at ?? now();
            }

            $expiresAt = (clone $actTimestamp)->addDays(365);
            $isExpired = $expiresAt->isPast();

            DB::table('sims')->where('id', $sim->id)->update([
                'activated_at' => $actTimestamp,
                'subscription_expires_at' => $expiresAt,
            ]);

            DB::table('sim_subscriptions')->insert([
                'sim_id' => $sim->id,
                'partner_id' => $sim->partner_id,
                'starts_at' => $actTimestamp,
                'expires_at' => $expiresAt,
                'fee' => 0.00,
                'payment_method' => 'initial_activation',
                'wallet_type' => 'main',
                'payment_reference' => 'INIT-ACT-' . $sim->id,
                'status' => $isExpired ? 'expired' : 'active',
                'paid_at' => $actTimestamp,
                'notes' => 'Backfilled from activation history',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sim_subscriptions');

        Schema::table('sims', function (Blueprint $table) {
            $table->dropIndex(['activated_at']);
            $table->dropIndex(['subscription_expires_at']);
            $table->dropColumn(['activated_at', 'subscription_expires_at']);
        });
    }
};
