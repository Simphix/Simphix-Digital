<?php

namespace App\Services;

use App\Models\BusinessCommissionRate;
use App\Models\DownlineBatch;
use App\Models\DownlineBatchUser;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin tool for retiring an upline "seat" wholesale onto a fresh account of
 * the same role: every downline user (referred_by), the role's jurisdiction
 * (Region/SenatorialDistrict), every SIM the outgoing manager holds anywhere
 * in their cascade lineage, and (for Enterprise Partners) their custom
 * pricing overrides all move to the incoming user in one transaction.
 *
 * Deliberately does not touch the outgoing user's wallet balance or
 * CommissionEarning history — those are personal financial records, not
 * part of the "seat" being handed over.
 */
class ManagerReplacementService
{
    /**
     * Every role in RoleHierarchy::CHAIN that actually holds a downline/SIM
     * position (i.e. every role that isn't the terminal 'personal' leaf),
     * mapped to the Sim column that marks their holding in the cascade.
     */
    private const ROLE_COLUMNS = [
        'regional_manager' => 'regional_manager_id',
        'coordinator' => 'coordinator_id',
        'partner' => 'partner_id',
        'business' => 'business_id',
        'business_agent' => 'business_agent_id',
    ];

    /**
     * Roles whose Sim column doubles as the "self-held" marker via user_id
     * (user_id === partner_id/business_agent_id means the SIM never left
     * this holder's own hands) — see SimAssignmentService for the same
     * invariant. Reassigning the holder column for these roles must also
     * move user_id for any SIM still self-held, or the SIM would end up
     * pointing at a user_id that no longer matches its own lineage.
     */
    private const SELF_HOLDING_ROLES = ['partner', 'business_agent'];

    public static function roleColumn(string $role): ?string
    {
        return self::ROLE_COLUMNS[$role] ?? null;
    }

    /** Every role this tool can replace a manager for. */
    public static function replaceableRoles(): array
    {
        return array_keys(self::ROLE_COLUMNS);
    }

    /**
     * Query all platform users eligible to be selected as replacement (searchable by name, email, or phone).
     * Excludes the outgoing manager and super administrators.
     */
    public function candidateQuery(User $outgoing, ?string $search = null): Builder
    {
        $query = User::query()
            ->where('id', '!=', $outgoing->id)
            ->where('role', '!=', 'super_admin')
            ->where('status', 'active');

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('middle_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('first_name')->orderBy('last_name');
    }

    /**
     * Fetch candidates list with optional search term.
     */
    public function candidates(User $outgoing, ?string $search = null, int $limit = 50): Collection
    {
        return $this->candidateQuery($outgoing, $search)->limit($limit)->get();
    }

    /**
     * Legacy helper: Users who hold the same role and are vacant.
     */
    public function vacantCandidates(User $outgoing): Collection
    {
        return $this->candidates($outgoing);
    }

    /**
     * Reasons $incoming is not eligible to receive $outgoing's seat, empty
     * when eligible. Re-checked at execution time.
     */
    public function vacancyErrors(User $outgoing, User $incoming): array
    {
        $errors = [];

        if ($incoming->id === $outgoing->id) {
            $errors[] = 'The replacement must be a different account than the outgoing manager.';
        }

        if ($incoming->status !== 'active') {
            $errors[] = 'The replacement account is not active.';
        }

        if ($incoming->role === 'super_admin') {
            $errors[] = 'A super administrator cannot be placed into a regional manager/coordinator seat.';
        }

        return $errors;
    }

    /**
     * A preview of what this swap will move, for the admin confirmation
     * screen before anything is written.
     */
    public function impactSummary(User $outgoing): array
    {
        $column = self::roleColumn($outgoing->role);

        return [
            'downline_count' => $outgoing->referrals()->count(),
            'sim_count' => $column ? Sim::where($column, $outgoing->id)->count() : 0,
            'jurisdiction_label' => $outgoing->jurisdictionLabel(),
            'pricing_override_count' => $outgoing->role === 'business'
                ? BusinessCommissionRate::where('business_id', $outgoing->id)->count()
                : 0,
        ];
    }

    /**
     * Move $outgoing's seat (jurisdiction + SIM holdings + pricing overrides) onto $incoming.
     * Automatically upgrades $incoming to the exact role of $outgoing if not already matching.
     * Places downlines of both $outgoing and $incoming under the Platform Level Master Manager in batches
     * so they can be monitored and transferred to other managers in bulk operations.
     */
    public function replace(User $outgoing, User $incoming, User $actor): array
    {
        $errors = $this->vacancyErrors($outgoing, $incoming);
        if (!empty($errors)) {
            throw new \RuntimeException(implode(' ', $errors));
        }

        $column = self::roleColumn($outgoing->role);
        if (!$column) {
            throw new \RuntimeException('This role does not hold a downline/jurisdiction position that can be replaced.');
        }

        return DB::transaction(function () use ($outgoing, $incoming, $actor, $column) {
            $masterManager = User::platformMasterManager();

            // 1. Place Outgoing Manager's downline under Platform Master Manager in a batch
            $outgoingDownlines = User::where('referred_by', $outgoing->id)->get();
            $outgoingBatch = null;

            if ($outgoingDownlines->isNotEmpty()) {
                $outgoingBatch = DownlineBatch::create([
                    'batch_reference'   => DownlineBatch::generateReference('OUT', $outgoing),
                    'source_user_id'    => $outgoing->id,
                    'source_role'       => $outgoing->role,
                    'source_type'       => 'outgoing_manager',
                    'master_manager_id' => $masterManager->id,
                    'title'             => "Downline from Outgoing {$outgoing->name} (" . str_replace('_', ' ', $outgoing->role) . ")",
                    'notes'             => "Detached upon manager replacement by {$incoming->name} (executed by {$actor->name})",
                    'initial_count'     => $outgoingDownlines->count(),
                    'current_count'     => $outgoingDownlines->count(),
                    'transferred_count' => 0,
                    'status'            => DownlineBatch::STATUS_HELD,
                    'created_by'        => $actor->id,
                ]);

                $now = now();
                $batchUsers = $outgoingDownlines->map(fn (User $u) => [
                    'batch_id'             => $outgoingBatch->id,
                    'user_id'              => $u->id,
                    'original_referred_by' => $outgoing->id,
                    'current_referred_by'  => $masterManager->id,
                    'status'               => DownlineBatchUser::STATUS_HELD,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ])->all();

                DownlineBatchUser::insert($batchUsers);

                User::where('referred_by', $outgoing->id)->update(['referred_by' => $masterManager->id]);
            }

            // 2. Place Incoming Manager's existing downlines under Platform Master Manager in a batch
            $incomingDownlines = User::where('referred_by', $incoming->id)->get();
            $incomingBatch = null;

            if ($incomingDownlines->isNotEmpty()) {
                $incomingBatch = DownlineBatch::create([
                    'batch_reference'   => DownlineBatch::generateReference('INC', $incoming),
                    'source_user_id'    => $incoming->id,
                    'source_role'       => $incoming->role,
                    'source_type'       => 'incoming_manager',
                    'master_manager_id' => $masterManager->id,
                    'title'             => "Downline from Replacement {$incoming->name} (" . str_replace('_', ' ', $incoming->role) . ")",
                    'notes'             => "Detached upon taking over {$outgoing->name}'s seat (executed by {$actor->name})",
                    'initial_count'     => $incomingDownlines->count(),
                    'current_count'     => $incomingDownlines->count(),
                    'transferred_count' => 0,
                    'status'            => DownlineBatch::STATUS_HELD,
                    'created_by'        => $actor->id,
                ]);

                $now = now();
                $batchUsers = $incomingDownlines->map(fn (User $u) => [
                    'batch_id'             => $incomingBatch->id,
                    'user_id'              => $u->id,
                    'original_referred_by' => $incoming->id,
                    'current_referred_by'  => $masterManager->id,
                    'status'               => DownlineBatchUser::STATUS_HELD,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ])->all();

                DownlineBatchUser::insert($batchUsers);

                User::where('referred_by', $incoming->id)->update(['referred_by' => $masterManager->id]);
            }

            // 3. Upgrade Incoming User to the exact role of the outgoing manager
            $roleUpgraded = false;
            $oldRole = $incoming->role;
            if ($incoming->role !== $outgoing->role) {
                $incoming->role = $outgoing->role;
                $incoming->save();
                $roleUpgraded = true;
            }

            // 4. Transfer Jurisdiction
            if ($outgoing->role === 'regional_manager') {
                Region::where('regional_manager_id', $outgoing->id)->update(['regional_manager_id' => $incoming->id]);
            } elseif ($outgoing->role === 'coordinator') {
                SenatorialDistrict::where('coordinator_id', $outgoing->id)->update(['coordinator_id' => $incoming->id]);
            }

            // 5. Transfer SIM holdings
            $simsMoved = $this->reassignSims($outgoing, $incoming, $actor, $column);

            // 6. Transfer Pricing overrides if business
            if ($outgoing->role === 'business') {
                BusinessCommissionRate::where('business_id', $outgoing->id)->update(['business_id' => $incoming->id]);
            }

            Log::info('Manager replacement executed with downline batching', [
                'role'                     => $outgoing->role,
                'outgoing_user_id'         => $outgoing->id,
                'incoming_user_id'         => $incoming->id,
                'outgoing_downlines_count' => $outgoingDownlines->count(),
                'incoming_downlines_count' => $incomingDownlines->count(),
                'outgoing_batch_ref'       => $outgoingBatch?->batch_reference,
                'incoming_batch_ref'       => $incomingBatch?->batch_reference,
                'sims_moved'               => $simsMoved,
                'role_upgraded'            => $roleUpgraded,
                'actor_id'                 => $actor->id,
            ]);

            return [
                'outgoing_batch'           => $outgoingBatch,
                'incoming_batch'           => $incomingBatch,
                'outgoing_downlines_count' => $outgoingDownlines->count(),
                'incoming_downlines_count' => $incomingDownlines->count(),
                'sims_moved'               => $simsMoved,
                'role_upgraded'            => $roleUpgraded,
                'old_role'                 => $oldRole,
            ];
        });
    }

    /**
     * Moves every SIM $outgoing holds (anywhere in their cascade lineage,
     * not just self-held stock) onto $incoming, and logs one SimHistory
     * row per SIM so the reassignment is auditable the same way every
     * other holder change is. Status is left untouched — this is a holder
     * swap, not a state transition.
     */
    private function reassignSims(User $outgoing, User $incoming, User $actor, string $column): int
    {
        $sims = Sim::where($column, $outgoing->id)->get(['id', 'status', 'user_id']);
        if ($sims->isEmpty()) {
            return 0;
        }

        $ids = $sims->pluck('id');
        Sim::whereIn('id', $ids)->update([$column => $incoming->id]);

        if (in_array($outgoing->role, self::SELF_HOLDING_ROLES, true)) {
            $selfHeldIds = $sims->where('user_id', $outgoing->id)->pluck('id');
            if ($selfHeldIds->isNotEmpty()) {
                Sim::whereIn('id', $selfHeldIds)->update(['user_id' => $incoming->id]);
            }
        }

        $note = "Holder reassigned via admin manager replacement: {$outgoing->name} ({$outgoing->role}) -> {$incoming->name}.";
        $now = now();
        $rows = $sims->map(fn (Sim $sim) => [
            'sim_id' => $sim->id,
            'from_status' => $sim->status,
            'to_status' => $sim->status,
            'actor_id' => $actor->id,
            'note' => $note,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            SimHistory::insert($chunk);
        }

        return $ids->count();
    }
}
