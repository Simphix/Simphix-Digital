<?php

namespace App\Services;

use App\Events\SimActivated;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\User;
use App\Support\RoleHierarchy;
use App\Support\SimStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single place for every SIM status transition: the admin -> Regional Manager
 * -> Coordinator -> Partner -> User assignment cascade, plus activation and
 * the admin lifecycle actions (deactivate/suspend/unassign).
 */
class SimAssignmentService
{
    /**
     * Admin assigns a currently-unassigned SIM to a user. Which FK/status it
     * lands on depends on the target's role in the hierarchy.
     */
    public function adminAssign(Sim $sim, User $target, User $actor): void
    {
        if ($sim->status !== SimStatus::UNASSIGNED) {
            throw new \RuntimeException('Only unassigned SIMs can be assigned this way.');
        }

        DB::transaction(function () use ($sim, $target, $actor) {
            $fields = match ($target->role) {
                'regional_manager' => ['regional_manager_id' => $target->id, 'status' => SimStatus::ASSIGNED_TO_RM],
                'coordinator' => ['coordinator_id' => $target->id, 'status' => SimStatus::ASSIGNED_TO_COORDINATOR],
                // Business (Enterprise Partner) is structurally the
                // "coordinator" of this 2-tier chain — an intermediate role
                // that never touches user_id, same as 'coordinator' above.
                // business_agent is the terminal tier before 'personal', so
                // it uses user_id as its own self-held marker, same as
                // 'partner' does in the default arm below.
                'business' => ['business_id' => $target->id, 'status' => SimStatus::ASSIGNED_TO_BUSINESS],
                'business_agent' => ['business_agent_id' => $target->id, 'user_id' => $target->id, 'status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT],
                default => ['user_id' => $target->id, 'partner_id' => $target->role === 'partner' ? $target->id : null, 'status' => SimStatus::ASSIGNED_TO_PARTNER],
            };

            $from = $sim->status;
            $sim->update($fields);
            $this->logHistory($sim, $from, $sim->status, $actor, "Admin assigned to {$target->name} ({$target->role})");
        });
    }

    /**
     * $from delegates a SIM they currently hold down to their direct subordinate $to
     * (Regional Manager -> Coordinator, Coordinator -> Partner, Partner -> User).
     */
    public function assignDown(Sim $sim, User $from, User $to): void
    {
        $expectedNextRole = RoleHierarchy::nextRole($from->role);
        if (!$expectedNextRole || $expectedNextRole !== $to->role) {
            throw new \RuntimeException("You can only assign to a " . str_replace('_', ' ', (string) $expectedNextRole) . '.');
        }

        $holds = match ($from->role) {
            'regional_manager' => $sim->regional_manager_id === $from->id && $sim->status === SimStatus::ASSIGNED_TO_RM,
            'coordinator' => $sim->coordinator_id === $from->id && $sim->status === SimStatus::ASSIGNED_TO_COORDINATOR,
            // A partner's own held SIMs and SIMs they've already handed to a
            // downline user share the same status/partner_id — both
            // adminAssign() and this method's own 'partner' target branch
            // set user_id to the partner's OWN id when it's still with them
            // (never left null), and only overwrite it with the downline
            // user's id once handed further down. So user_id === partner_id
            // is what "still with the partner" actually means here.
            'partner' => $sim->partner_id === $from->id && $sim->status === SimStatus::ASSIGNED_TO_PARTNER && $sim->user_id === $sim->partner_id,
            // 'business' mirrors 'coordinator' above (intermediate tier,
            // never touches user_id); 'business_agent' mirrors 'partner'
            // (terminal tier, user_id === business_agent_id means self-held).
            'business' => $sim->business_id === $from->id && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS,
            'business_agent' => $sim->business_agent_id === $from->id && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT && $sim->user_id === $sim->business_agent_id,
            default => false,
        };

        if (!$holds) {
            throw new \RuntimeException('You do not currently hold this SIM at the required stage.');
        }

        DB::transaction(function () use ($sim, $from, $to) {
            $fields = match ($to->role) {
                'coordinator' => ['coordinator_id' => $to->id, 'status' => SimStatus::ASSIGNED_TO_COORDINATOR],
                'partner' => ['partner_id' => $to->id, 'user_id' => $to->id, 'status' => SimStatus::ASSIGNED_TO_PARTNER],
                'business_agent' => ['business_agent_id' => $to->id, 'user_id' => $to->id, 'status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT],
                // 'personal' is a terminal target reachable from two
                // different source tiers (Partner and Business Agent), so
                // it must stay from-role-aware to pick the right status.
                'personal' => $from->role === 'business_agent'
                    ? ['user_id' => $to->id, 'status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT]
                    : ['user_id' => $to->id, 'status' => SimStatus::ASSIGNED_TO_PARTNER],
                default => throw new \RuntimeException('Invalid target role.'),
            };

            $fromStatus = $sim->status;
            $sim->update($fields);
            $this->logHistory($sim, $fromStatus, $sim->status, $from, "Delegated to {$to->name} ({$to->role})");
        });
    }

    /**
     * $collector pulls a SIM back from their direct subordinate $currentHolder
     * into their own held stock (Coordinator -> Regional Manager, Partner ->
     * Coordinator) — the mirror of assignDown(). Only reclaims a SIM that
     * both currently sits with $currentHolder at the expected stage AND
     * traces back to this specific $collector (not some other upline's
     * lineage), so a regional_manager can't collect a coordinator's SIM that
     * actually came from a different regional_manager.
     */
    public function collectUp(Sim $sim, User $collector, User $currentHolder): void
    {
        $expectedHolderRole = RoleHierarchy::nextRole($collector->role);
        if (!$expectedHolderRole || $expectedHolderRole !== $currentHolder->role) {
            throw new \RuntimeException("You can only collect from a " . str_replace('_', ' ', (string) $expectedHolderRole) . '.');
        }

        $holds = match ($currentHolder->role) {
            'coordinator' => $sim->coordinator_id === $currentHolder->id
                && $sim->status === SimStatus::ASSIGNED_TO_COORDINATOR
                && $sim->regional_manager_id === $collector->id,
            'partner' => $sim->partner_id === $currentHolder->id
                && $sim->user_id === $sim->partner_id
                && $sim->status === SimStatus::ASSIGNED_TO_PARTNER
                && $sim->coordinator_id === $collector->id,
            // Mirrors the 'partner' case: reclaim from a business_agent back
            // to the Enterprise Partner (business) that dispatched it.
            'business_agent' => $sim->business_agent_id === $currentHolder->id
                && $sim->user_id === $sim->business_agent_id
                && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT
                && $sim->business_id === $collector->id,
            default => false,
        };

        if (!$holds) {
            throw new \RuntimeException('This SIM is not currently held by that user under you.');
        }

        DB::transaction(function () use ($sim, $collector, $currentHolder) {
            $fields = match ($collector->role) {
                'regional_manager' => ['coordinator_id' => null, 'status' => SimStatus::ASSIGNED_TO_RM],
                'coordinator' => ['partner_id' => null, 'user_id' => null, 'status' => SimStatus::ASSIGNED_TO_COORDINATOR],
                'business' => ['business_agent_id' => null, 'user_id' => null, 'status' => SimStatus::ASSIGNED_TO_BUSINESS],
                default => throw new \RuntimeException('Invalid collector role.'),
            };

            $fromStatus = $sim->status;
            $sim->update($fields);
            $this->logHistory($sim, $fromStatus, $sim->status, $collector, "Collected back from {$currentHolder->name} ({$currentHolder->role})");
        });
    }

    /**
     * Admin-approved swap: move a SIM currently held at the Coordinator or
     * Partner tier to a different holder at that same tier. Status and any
     * higher-tier FK columns are left untouched — this is a lateral move,
     * not a cascade.
     */
    public function swapHolder(Sim $sim, User $newHolder, User $actor): void
    {
        $column = match (true) {
            $sim->status === SimStatus::ASSIGNED_TO_COORDINATOR => 'coordinator_id',
            $sim->status === SimStatus::ASSIGNED_TO_PARTNER && $sim->user_id !== $sim->partner_id => 'user_id',
            $sim->status === SimStatus::ASSIGNED_TO_BUSINESS => 'business_id',
            $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT && $sim->user_id !== $sim->business_agent_id => 'user_id',
            default => throw new \RuntimeException('This SIM is not in a swappable state.'),
        };

        DB::transaction(function () use ($sim, $newHolder, $actor, $column) {
            $from = $sim->status;
            $sim->update([$column => $newHolder->id]);
            $this->logHistory($sim, $from, $sim->status, $actor, "Swapped to {$newHolder->name} ({$newHolder->role}) via admin-approved swap request.");
        });
    }

    /**
     * Mark a SIM active, whether triggered by admin approval of a user's
     * activation request or a direct admin action. Fires SimActivated so the
     * Commission Engine can pay out the chain.
     *
     * The status flip + history row is the one thing this method absolutely
     * guarantees: once it returns without throwing, the SIM is durably
     * ACTIVATED. SimActivated's side effects (commission payouts, welfare
     * wallet credit, activation-bonus job) are deliberately dispatched via
     * DB::afterCommit() rather than fired inline — this method is normally
     * called from inside a caller's own transaction that also debits the
     * activating user's wallet (see SimsController::activateForDownlineUser),
     * so firing the event inline would let an unrelated failure deep in the
     * commission chain (a misconfigured ServicePrice, a locked row, ...) roll
     * back that already-legitimate debit + activation. Deferring to
     * afterCommit means the debit and the ACTIVATED status are locked in
     * first, no matter what happens to the side effects afterward; any
     * failure there is caught and logged instead of being allowed to
     * surface as a confusing error on an activation that actually succeeded.
     */
    public function activate(Sim $sim, User $actor): void
    {
        if ($sim->status === SimStatus::ACTIVATED) {
            throw new \RuntimeException('This SIM is already activated.');
        }

        if ($this->isActivationDisabled($sim->category)) {
            throw new \RuntimeException('Activation is currently disabled for this SIM category.');
        }

        DB::transaction(function () use ($sim, $actor) {
            $from = $sim->status;
            $sim->update(['status' => SimStatus::ACTIVATED]);
            $this->logHistory($sim, $from, SimStatus::ACTIVATED, $actor, 'SIM activated.');

            DB::afterCommit(function () use ($sim, $actor) {
                try {
                    event(new SimActivated($sim->fresh(), $actor));
                } catch (\Throwable $e) {
                    Log::error('SIM activated successfully but a post-activation side effect (commission/welfare payout) failed.', [
                        'sim_id' => $sim->id,
                        'sim_number' => $sim->number,
                        'actor_id' => $actor->id,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            });
        });
    }

    /**
     * Admin-controlled kill-switch, keyed off the category's ServiceField
     * row (the same one-per-category pricing template the SIM Plans page
     * already manages) — not a per-physical-SIM flag.
     */
    private function isActivationDisabled(string $category): bool
    {
        return \App\Models\ServiceField::whereHas('service', fn ($q) => $q->where('name', 'simcard'))
            ->where('field_name', $category)
            ->value('activation_disabled') ?? false;
    }

    public function deactivate(Sim $sim, User $actor): void
    {
        $from = $sim->status;
        $sim->update(['status' => SimStatus::DEACTIVATED]);
        $this->logHistory($sim, $from, SimStatus::DEACTIVATED, $actor, 'SIM deactivated.');
    }

    public function suspend(Sim $sim, User $actor): void
    {
        $from = $sim->status;
        $sim->update(['status' => SimStatus::SUSPENDED]);
        $this->logHistory($sim, $from, SimStatus::SUSPENDED, $actor, 'SIM suspended.');
    }

    public function unassign(Sim $sim, User $actor): void
    {
        $from = $sim->status;
        $sim->update([
            'user_id' => null,
            'partner_id' => null,
            'coordinator_id' => null,
            'regional_manager_id' => null,
            'business_id' => null,
            'business_agent_id' => null,
            'status' => SimStatus::UNASSIGNED,
        ]);
        $this->logHistory($sim, $from, SimStatus::UNASSIGNED, $actor, 'SIM unassigned.');
    }

    /**
     * Recovery for a SIM stuck mid-handoff: delegated to a downline personal
     * user (user_id points at them) but never actually activated — status
     * is still ASSIGNED_TO_PARTNER/ASSIGNED_TO_BUSINESS_AGENT, so it never
     * shows up as ACTIVATED anywhere. Reverts user_id back to the holder's
     * own id (partner_id or business_agent_id), which is this codebase's
     * "still self-held, not yet handed to anyone" marker — leaving the SIM
     * exactly as if it had never been delegated, so the holder sees it back
     * in their own inventory and can re-delegate/activate it cleanly.
     *
     * Callers are responsible for confirming the target was never actually
     * debited before calling this — see sim:revert-stuck-to-partner, which
     * checks for a matching Transaction row before including a SIM here.
     */
    public function revertToHolder(Sim $sim, User $actor, string $note): void
    {
        $holderId = $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT
            ? $sim->business_agent_id
            : $sim->partner_id;

        if (!in_array($sim->status, [SimStatus::ASSIGNED_TO_PARTNER, SimStatus::ASSIGNED_TO_BUSINESS_AGENT], true) || !$holderId) {
            throw new \RuntimeException('This SIM is not in a delegated-but-unactivated state that can be reverted to a holder.');
        }

        if ($sim->user_id === $holderId) {
            throw new \RuntimeException('This SIM is already sitting with its holder — nothing to revert.');
        }

        DB::transaction(function () use ($sim, $holderId, $actor, $note) {
            $from = $sim->status;
            $sim->update(['user_id' => $holderId]);
            $this->logHistory($sim, $from, $sim->status, $actor, $note);
        });
    }

    private function logHistory(Sim $sim, ?string $from, string $to, User $actor, string $note): void
    {
        SimHistory::create([
            'sim_id' => $sim->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->id,
            'note' => $note,
        ]);
    }
}
