<?php

namespace App\Services;

use App\Models\BusinessCommissionRate;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\User;
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
     * Users who could receive $outgoing's seat: same role, active, and
     * holding nothing yet (no downline, no SIMs, no jurisdiction, no
     * pricing overrides) — i.e. truly vacant.
     */
    public function vacantCandidates(User $outgoing): Collection
    {
        $column = self::roleColumn($outgoing->role);
        if (!$column) {
            return collect();
        }

        $query = User::query()
            ->where('role', $outgoing->role)
            ->where('id', '!=', $outgoing->id)
            ->where('status', 'active')
            ->whereDoesntHave('referrals')
            ->whereNotExists(function ($q) use ($column) {
                $q->select(DB::raw(1))
                    ->from('sims')
                    ->whereColumn("sims.$column", 'users.id');
            });

        if ($outgoing->role === 'regional_manager') {
            $query->whereDoesntHave('regionAsManager');
        } elseif ($outgoing->role === 'coordinator') {
            $query->whereDoesntHave('districtAsCoordinator');
        } elseif ($outgoing->role === 'business') {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('business_commission_rates')
                    ->whereColumn('business_commission_rates.business_id', 'users.id');
            });
        }

        return $query->orderBy('first_name')->orderBy('last_name')->get();
    }

    /**
     * Reasons $incoming is not eligible to receive $outgoing's seat, empty
     * when eligible. Re-checked at execution time (not just when the
     * candidate list was rendered) in case anything changed in between.
     */
    public function vacancyErrors(User $outgoing, User $incoming): array
    {
        $errors = [];

        if ($incoming->id === $outgoing->id) {
            $errors[] = 'The replacement must be a different account than the outgoing manager.';
        }

        if ($incoming->role !== $outgoing->role) {
            $errors[] = 'The replacement must hold the same role as the outgoing manager.';
        }

        if ($incoming->status !== 'active') {
            $errors[] = 'The replacement account is not active.';
        }

        $column = self::roleColumn($incoming->role);
        if (!$column) {
            $errors[] = 'This role does not hold a downline/jurisdiction position that can be replaced.';
            return $errors;
        }

        if ($incoming->referrals()->exists()) {
            $errors[] = 'The replacement already has downline users reporting to them.';
        }

        if (Sim::where($column, $incoming->id)->exists()) {
            $errors[] = 'The replacement already holds SIMs.';
        }

        if ($incoming->role === 'regional_manager' && $incoming->regionAsManager()->exists()) {
            $errors[] = 'The replacement already manages a region.';
        }

        if ($incoming->role === 'coordinator' && $incoming->districtAsCoordinator()->exists()) {
            $errors[] = 'The replacement already coordinates a senatorial district.';
        }

        if ($incoming->role === 'business' && BusinessCommissionRate::where('business_id', $incoming->id)->exists()) {
            $errors[] = 'The replacement already has custom pricing configured.';
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
     * Move $outgoing's entire seat (jurisdiction + downline + SIM holdings
     * + pricing overrides) onto $incoming. Throws RuntimeException with a
     * human-readable reason if $incoming isn't actually vacant.
     */
    public function replace(User $outgoing, User $incoming, User $actor): void
    {
        $errors = $this->vacancyErrors($outgoing, $incoming);
        if (!empty($errors)) {
            throw new \RuntimeException(implode(' ', $errors));
        }

        $column = self::roleColumn($outgoing->role);

        DB::transaction(function () use ($outgoing, $incoming, $actor, $column) {
            if ($outgoing->role === 'regional_manager') {
                Region::where('regional_manager_id', $outgoing->id)->update(['regional_manager_id' => $incoming->id]);
            } elseif ($outgoing->role === 'coordinator') {
                SenatorialDistrict::where('coordinator_id', $outgoing->id)->update(['coordinator_id' => $incoming->id]);
            }

            User::where('referred_by', $outgoing->id)->update(['referred_by' => $incoming->id]);

            $simsMoved = $this->reassignSims($outgoing, $incoming, $actor, $column);

            if ($outgoing->role === 'business') {
                BusinessCommissionRate::where('business_id', $outgoing->id)->update(['business_id' => $incoming->id]);
            }

            Log::info('Manager replacement executed', [
                'role' => $outgoing->role,
                'outgoing_user_id' => $outgoing->id,
                'incoming_user_id' => $incoming->id,
                'sims_moved' => $simsMoved,
                'actor_id' => $actor->id,
            ]);
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
