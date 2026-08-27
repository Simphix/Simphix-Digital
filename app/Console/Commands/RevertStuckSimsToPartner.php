<?php

namespace App\Console\Commands;

use App\Models\Sim;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;
use Illuminate\Console\Command;

/**
 * Recovery tool for the activation-transaction-rollback bug (fixed
 * alongside this command) — see docs/sim-activation-recovery.md for the
 * full runbook. A partner/business_agent delegates a SIM to a
 * downline personal user (user_id set to them, status stays
 * ASSIGNED_TO_PARTNER/ASSIGNED_TO_BUSINESS_AGENT), the activation attempt
 * then throws somewhere downstream (commission/welfare payout) and the
 * whole transaction rolls back — but the earlier, already-committed
 * assignDown() hand-off is not part of that transaction, so the SIM is
 * left sitting in limbo: delegated to a specific customer, never
 * activated, and — critically — that customer was never actually
 * debited (the debit only happens inside the same rolled-back
 * activation attempt).
 *
 * This reverts user_id back to the holder's own id (SimAssignmentService::
 * revertToHolder()), putting the SIM back in the partner's/agent's own
 * inventory exactly as if it had never been delegated, ready to be
 * re-delegated and activated cleanly through the fixed code path.
 *
 * Safety: only ever touches SIMs at ASSIGNED_TO_PARTNER/
 * ASSIGNED_TO_BUSINESS_AGENT whose user_id differs from the holder id
 * (i.e. still mid-handoff, not activated), and skips — rather than
 * reverts — any SIM where a debit Transaction referencing its number
 * already exists, since that needs manual review, not a blind revert.
 * Always run with --dry-run first.
 */
class RevertStuckSimsToPartner extends Command
{
    protected $signature = 'sim:revert-stuck-to-partner
        {--dry-run : Preview affected SIMs without changing anything}
        {--sim=* : Only consider these specific SIM IDs}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Reverts SIMs stuck mid-handoff (delegated to a downline user but never activated, and never debited) back to their holding partner/business_agent.';

    public function handle(SimAssignmentService $service): int
    {
        $simIds = $this->option('sim');

        $candidates = Sim::whereIn('status', [SimStatus::ASSIGNED_TO_PARTNER, SimStatus::ASSIGNED_TO_BUSINESS_AGENT])
            ->when(!empty($simIds), fn ($q) => $q->whereIn('id', $simIds))
            ->with('user')
            ->get()
            ->filter(function (Sim $sim) {
                $holderId = $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT
                    ? $sim->business_agent_id
                    : $sim->partner_id;

                return $holderId && $sim->user_id && $sim->user_id !== $holderId;
            });

        if ($candidates->isEmpty()) {
            $this->info('No stuck SIMs found.');

            return self::SUCCESS;
        }

        $toRevert = collect();
        $rows = [];

        foreach ($candidates as $sim) {
            $target = $sim->user;

            $hasDebit = Transaction::where('user_id', $sim->user_id)
                ->where('type', 'debit')
                ->where('description', 'like', '%' . $sim->number . '%')
                ->exists();

            if ($hasDebit) {
                $rows[] = [$sim->id, $sim->number, $sim->category, $target?->email ?? $sim->user_id, 'SKIP — a debit referencing this number already exists'];

                continue;
            }

            $toRevert->push($sim);
            $rows[] = [$sim->id, $sim->number, $sim->category, $target?->email ?? $sim->user_id, 'Revert to holder'];
        }

        $this->table(['Sim ID', 'Number', 'Category', 'Currently held by (user)', 'Action'], $rows);

        $skippedCount = $candidates->count() - $toRevert->count();
        if ($skippedCount > 0) {
            $this->warn("{$skippedCount} SIM(s) skipped — a debit transaction referencing them already exists. Review those manually; do not blindly revert a SIM someone may have already paid for.");
        }

        if ($toRevert->isEmpty()) {
            $this->info('Nothing to revert.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: no changes made. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Revert {$toRevert->count()} SIM(s) back to their holding partner/business_agent?")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $actor = User::where('role', 'super_admin')->orderBy('id')->first();
        if (!$actor) {
            $this->error('No super_admin user found to record as the actor for this audit trail. Aborting.');

            return self::FAILURE;
        }

        $reverted = 0;
        foreach ($toRevert as $sim) {
            try {
                $service->revertToHolder(
                    $sim,
                    $actor,
                    'Reverted to holder: stuck mid-handoff with no matching customer debit — recovered via sim:revert-stuck-to-partner after the activation-transaction-rollback bug fix.'
                );
                $reverted++;
            } catch (\Throwable $e) {
                $this->error("Failed to revert SIM {$sim->id} ({$sim->number}): {$e->getMessage()}");
            }
        }

        $this->info("Reverted {$reverted} of {$toRevert->count()} SIM(s).");

        return self::SUCCESS;
    }
}
