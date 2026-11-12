<?php

namespace App\Services;

use App\Models\DownlineBatch;
use App\Models\DownlineBatchUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DownlineBatchTransferService
{
    /**
     * Determine the eligible destination manager roles for a given downline batch.
     *
     * @param DownlineBatch $batch
     * @return array<string>
     */
    public function eligibleDestinationRoles(DownlineBatch $batch): array
    {
        // 1. If batch has source_role, that directly defines the manager seat level that was replaced:
        if (!empty($batch->source_role) && $batch->source_type !== 'role_change') {
            return match ($batch->source_role) {
                'regional_manager' => ['regional_manager'],
                'coordinator'      => ['coordinator'],
                'partner'          => ['partner'],
                'business'         => ['business'],
                'business_agent'   => ['business_agent'],
                default            => [$batch->source_role],
            };
        }

        // 2. If source_role is null, deduce from the member users' roles
        $memberRoles = $batch->batchUsers()
            ->where('status', DownlineBatchUser::STATUS_HELD)
            ->join('users', 'downline_batch_users.user_id', '=', 'users.id')
            ->pluck('users.role')
            ->unique()
            ->values()
            ->all();

        if (empty($memberRoles)) {
            return [
                'regional_manager',
                'coordinator',
                'partner',
                'business',
                'business_agent',
            ];
        }

        $eligible = [];
        foreach ($memberRoles as $role) {
            $uplines = match ($role) {
                'regional_manager'            => ['regional_manager'],
                'coordinator'               => ['regional_manager'],
                'partner'                   => ['coordinator'],
                'business'                  => ['regional_manager', 'coordinator', 'partner'],
                'business_agent'            => ['business'],
                'personal', 'agent', 'user' => ['partner', 'business_agent'],
                default                     => [],
            };
            $eligible = array_merge($eligible, $uplines);
        }

        $eligible = array_values(array_unique($eligible));

        return !empty($eligible) ? $eligible : [
            'regional_manager',
            'coordinator',
            'partner',
            'business',
            'business_agent',
        ];
    }

    /**
     * Bulk transfer users from a held DownlineBatch to a new destination manager.
     *
     * @param DownlineBatch $batch The batch holding detached downline users
     * @param User $destinationManager The new upline manager receiving the users
     * @param array $userIds Specific user IDs to transfer (empty array means transfer all remaining held users in batch)
     * @param User|null $actor The administrator executing the bulk transfer
     * @return int Number of users successfully transferred
     */
    public function transfer(DownlineBatch $batch, User $destinationManager, array $userIds = [], ?User $actor = null): int
    {
        if ($destinationManager->status !== 'active') {
            throw new \RuntimeException('The destination manager account is not active.');
        }

        if ($destinationManager->role === 'super_admin') {
            throw new \RuntimeException('Users cannot be transferred to a super administrator account.');
        }

        if ($batch->master_manager_id && $destinationManager->id === (int) $batch->master_manager_id) {
            throw new \RuntimeException('Users cannot be transferred to the Platform Master Manager.');
        }

        if ($batch->source_user_id && $destinationManager->id === (int) $batch->source_user_id) {
            throw new \RuntimeException('Downlines cannot be transferred back to the outgoing source manager.');
        }

        $eligibleRoles = $this->eligibleDestinationRoles($batch);
        if (!in_array($destinationManager->role, $eligibleRoles, true)) {
            $formattedEligible = implode(', ', array_map(fn ($r) => ucwords(str_replace('_', ' ', $r)), $eligibleRoles));
            $actualRole = ucwords(str_replace('_', ' ', $destinationManager->role));
            throw new \RuntimeException("The destination manager has an ineligible role ({$actualRole}). Users from this batch can only be transferred to: {$formattedEligible}.");
        }

        return DB::transaction(function () use ($batch, $destinationManager, $userIds, $actor) {
            $query = $batch->batchUsers()
                ->where('status', DownlineBatchUser::STATUS_HELD);

            if (!empty($userIds)) {
                $query->whereIn('user_id', $userIds);
            }

            $batchUsers = $query->lockForUpdate()->get();

            if ($batchUsers->isEmpty()) {
                throw new \RuntimeException('No eligible held users in this batch to transfer.');
            }

            $targetUserIds = $batchUsers->pluck('user_id')->all();

            // 1. Reassign referred_by upline on users table
            User::whereIn('id', $targetUserIds)->update([
                'referred_by' => $destinationManager->id,
            ]);

            // 2. Update downline_batch_users records
            $now = now();
            DownlineBatchUser::where('batch_id', $batch->id)
                ->whereIn('user_id', $targetUserIds)
                ->update([
                    'status'                 => DownlineBatchUser::STATUS_TRANSFERRED,
                    'current_referred_by'    => $destinationManager->id,
                    'transferred_to_user_id' => $destinationManager->id,
                    'transferred_at'         => $now,
                    'transferred_by'         => $actor?->id,
                ]);

            // 3. Sync batch counts and status
            $batch->syncCounts();

            Log::info('Downline batch users bulk transferred', [
                'batch_id'            => $batch->id,
                'batch_reference'     => $batch->batch_reference,
                'destination_user_id' => $destinationManager->id,
                'destination_role'    => $destinationManager->role,
                'transferred_count'   => count($targetUserIds),
                'transferred_by'      => $actor?->id,
            ]);

            return count($targetUserIds);
        });
    }

    /**
     * Query potential destination managers searchable by name, email, or phone,
     * restricted strictly to the eligible upline roles for the batch.
     *
     * @param DownlineBatch|string|null $batchOrSearch
     * @param string|null $search
     * @param string|null $role
     * @return Builder
     */
    public function destinationManagerQuery($batchOrSearch = null, ?string $search = null, ?string $role = null): Builder
    {
        $batch = null;
        if ($batchOrSearch instanceof DownlineBatch) {
            $batch = $batchOrSearch;
        } else {
            $search = $batchOrSearch;
        }

        $eligibleRoles = $batch
            ? $this->eligibleDestinationRoles($batch)
            : ['regional_manager', 'coordinator', 'partner', 'business', 'business_agent'];

        $query = User::query()
            ->whereIn('role', $eligibleRoles)
            ->where('status', 'active')
            ->where('role', '!=', 'super_admin');

        if ($batch) {
            if ($batch->master_manager_id) {
                $query->where('id', '!=', $batch->master_manager_id);
            }
            if ($batch->source_user_id) {
                $query->where('id', '!=', $batch->source_user_id);
            }
        }

        if ($role && in_array($role, $eligibleRoles, true)) {
            $query->where('role', $role);
        }

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
     * Fetch candidates collection for a batch.
     */
    public function destinationManagers(DownlineBatch $batch, ?string $search = null, ?string $role = null, int $limit = 50): Collection
    {
        return $this->destinationManagerQuery($batch, $search, $role)->limit($limit)->get();
    }
}
