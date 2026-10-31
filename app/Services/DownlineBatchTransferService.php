<?php

namespace App\Services;

use App\Models\DownlineBatch;
use App\Models\DownlineBatchUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DownlineBatchTransferService
{
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
                'transferred_count'   => count($targetUserIds),
                'transferred_by'      => $actor?->id,
            ]);

            return count($targetUserIds);
        });
    }

    /**
     * Query potential destination managers searchable by name, email, or phone.
     */
    public function destinationManagerQuery(?string $search = null, ?string $role = null)
    {
        $managerRoles = [
            'regional_manager',
            'coordinator',
            'partner',
            'business',
            'business_agent',
        ];

        $query = User::query()
            ->whereIn('role', $managerRoles)
            ->where('status', 'active');

        if ($role && in_array($role, $managerRoles, true)) {
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

        return $query->orderBy('role')->orderBy('first_name');
    }
}
