<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\RoleHierarchy;
use App\Support\StaffAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearManagerDownlinesCommand extends Command
{
    /**
     * Catalog distribution roles that belong to the platform referral hierarchy.
     */
    public const CATALOG_ROLES = [
        'regional_manager',
        'coordinator',
        'partner',
        'business',
        'business_agent',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'downlines:clear
                            {--manager-id= : Clear downlines for a specific upline manager ID}
                            {--manager-role= : Clear downlines for upline managers of a specific role (regional_manager, coordinator, partner, business, business_agent)}
                            {--customers=remove : Action for personal/agent customer downlines: "remove" (set upline to null) or "master" (reassign to Master Manager)}
                            {--dry-run : Simulate and preview the downline clearance without modifying database}
                            {--force : Force execution without interactive confirmation}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'managers:clear-downlines',
        'downlines:clear-upline-managers',
        'upline:clear-downlines',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear downlines on upline managers: moves catalog role members to Platform Master Manager and removes uplines completely from admin/staff accounts';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $managerIdOption = $this->option('manager-id');
        $managerRoleOption = $this->option('manager-role');
        $customersOption = strtolower(trim((string) $this->option('customers')));

        $customerAction = in_array($customersOption, ['master', 'reassign'], true) ? 'master' : 'remove';

        $this->newLine();
        $this->line('<fg=cyan;options=bold>=========================================================</>');
        $this->line('<fg=cyan;options=bold>       SIMPHIX UPLINE MANAGERS DOWNLINE CLEARANCE        </>');
        $this->line('<fg=cyan;options=bold>=========================================================</>');

        if ($isDryRun) {
            $this->warn(' [DRY-RUN MODE ACTIVE] No changes will be written to the database.');
        }

        // 1. Resolve Platform Level Master Manager
        $masterManager = User::platformMasterManager();
        $this->line("<fg=gray>Platform Master Manager:</> <fg=yellow>{$masterManager->name}</> (#{$masterManager->id}, {$masterManager->email})");

        // 2. Identify targeted upline managers
        $managerQuery = User::query()
            ->where('id', '!=', $masterManager->id)
            ->whereHas('referrals');

        if ($managerIdOption) {
            $managerId = (int) $managerIdOption;
            if ($managerId === $masterManager->id) {
                $this->error('The Platform Level Master Manager is the platform holding seat and cannot have its downlines cleared.');
                return self::FAILURE;
            }

            $targetManager = User::find($managerId);
            if (! $targetManager) {
                $this->error("Target manager with ID #{$managerId} does not exist.");
                return self::FAILURE;
            }

            $managerQuery->where('id', $managerId);
        }

        if ($managerRoleOption) {
            $targetRole = strtolower(trim((string) $managerRoleOption));
            $managerQuery->where('role', $targetRole);
        }

        $uplineManagers = $managerQuery->get();
        $uplineManagerIds = $uplineManagers->pluck('id')->all();

        // 3. Find only downlines whose current upline violates the role hierarchy.
        $downlineUsers = User::query()
            ->whereIn('referred_by', $uplineManagerIds)
            ->with('referrer:id,role')
            ->get();

        // Internal accounts are invalid under any upline, including the
        // platform master seat, so include them while respecting explicit filters.
        $staffWithUpline = User::query()
            ->whereNotNull('referred_by')
            ->whereIn('role', StaffAccess::ROLES)
            ->with('referrer:id,role')
            ->when($managerIdOption, fn ($query) => $query->where('referred_by', (int) $managerIdOption))
            ->when($managerRoleOption, fn ($query) => $query->whereHas('referrer', fn ($referrer) => $referrer->where('role', strtolower(trim((string) $managerRoleOption)))))
            ->get();

        $allTargetedUsers = $downlineUsers->concat($staffWithUpline)->unique('id')
            ->filter(fn (User $user) => ! $this->hasExpectedUpline($user, $masterManager->id))
            ->values();

        if ($allTargetedUsers->isEmpty()) {
            $this->info('No downlines found on targeted upline managers and no staff accounts carry an upline.');
            $this->newLine();
            return self::SUCCESS;
        }

        // 5. Categorize each affected user based on requirements:
        //    - Catalog role: move to / retain in Platform Level Master Manager
        //    - Admin / Staff: remove upline completely (referred_by = null)
        //    - Customer / Personal: remove upline completely (or master if requested)
        $catalogMoves = collect();
        $staffRemovals = collect();
        $customerRemovals = collect();

        foreach ($allTargetedUsers as $user) {
            if ($user->isStaff() || in_array($user->role, StaffAccess::ROLES, true)) {
                // Admin / Staff role -> remove upline completely
                $staffRemovals->push([
                    'user'           => $user,
                    'current_upline' => $user->referred_by,
                    'new_upline'     => null,
                    'reason'         => 'Admin/Staff accounts must have no upline manager',
                ]);
            } elseif (in_array($user->role, self::CATALOG_ROLES, true)) {
                // Catalog distribution role -> retain in / move to Platform Level Master Manager
                if ($user->referred_by !== $masterManager->id) {
                    $catalogMoves->push([
                        'user'           => $user,
                        'current_upline' => $user->referred_by,
                        'new_upline'     => $masterManager->id,
                        'reason'         => 'Catalog role moved to Platform Level Master Manager',
                    ]);
                }
            } else {
                // Customer / Retail role (personal, agent, etc.)
                $targetUpline = ($customerAction === 'master') ? $masterManager->id : null;
                $customerRemovals->push([
                    'user'           => $user,
                    'current_upline' => $user->referred_by,
                    'new_upline'     => $targetUpline,
                    'reason'         => $targetUpline === null
                        ? 'Customer downline cleared from upline manager'
                        : 'Customer downline moved to Master Manager',
                ]);
            }
        }

        // Summary of identified changes
        $this->newLine();
        $this->table(
            ['Category', 'Count', 'Destination / Action'],
            [
                ['Upline Managers Affected', count($uplineManagerIds), 'Downlines cleared'],
                ['Catalog Role Downlines', $catalogMoves->count(), "Assigned to Master Manager (#{$masterManager->id})"],
                ['Admin/Staff Accounts', $staffRemovals->count(), 'Upline removed completely (NULL)'],
                ['Customer/Retail Downlines', $customerRemovals->count(), $customerAction === 'master' ? "Assigned to Master Manager (#{$masterManager->id})" : 'Upline removed completely (NULL)'],
            ]
        );

        // Preview sample of changes
        $sampleRecords = $catalogMoves->concat($staffRemovals)->concat($customerRemovals)->take(15);
        if ($sampleRecords->isNotEmpty()) {
            $this->newLine();
            $this->line('<fg=yellow;options=bold>Sample of Users to Update:</>');
            $this->table(
                ['User ID', 'Name', 'Role', 'Current Upline ID', 'New Upline ID', 'Action'],
                $sampleRecords->map(fn ($item) => [
                    $item['user']->id,
                    $item['user']->name,
                    ucwords(str_replace('_', ' ', $item['user']->role)),
                    $item['current_upline'] ?? '<none>',
                    $item['new_upline'] ?? '<none (removed)>',
                    $item['reason'],
                ])
            );
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info("Dry run complete. {$allTargetedUsers->count()} user(s) evaluated. No database modifications were made.");
            $this->newLine();
            return self::SUCCESS;
        }

        // Confirmation prompt if not forced
        if (! $isForce) {
            $totalToUpdate = $catalogMoves->count() + $staffRemovals->count() + $customerRemovals->count();
            if ($totalToUpdate === 0) {
                $this->info('All evaluated downlines and staff accounts already match desired state.');
                return self::SUCCESS;
            }

            if (! $this->confirm("Are you sure you want to clear downlines and update {$totalToUpdate} user(s)?", false)) {
                $this->warn('Operation cancelled by operator.');
                return self::SUCCESS;
            }
        }

        // 6. Execute updates in a database transaction
        $updatedCatalog = 0;
        $updatedStaff = 0;
        $updatedCustomers = 0;

        DB::transaction(function () use (
            $catalogMoves,
            $staffRemovals,
            $customerRemovals,
            $masterManager,
            &$updatedCatalog,
            &$updatedStaff,
            &$updatedCustomers
        ) {
            // Update catalog roles -> Master Manager
            if ($catalogMoves->isNotEmpty()) {
                $catalogIds = $catalogMoves->pluck('user.id')->all();
                $updatedCatalog = User::whereIn('id', $catalogIds)->update([
                    'referred_by' => $masterManager->id,
                ]);
            }

            // Update admin/staff -> null
            if ($staffRemovals->isNotEmpty()) {
                $staffIds = $staffRemovals->pluck('user.id')->all();
                $updatedStaff = User::whereIn('id', $staffIds)->update([
                    'referred_by' => null,
                ]);
            }

            // Update customers -> null (or master manager)
            if ($customerRemovals->isNotEmpty()) {
                $customerIds = $customerRemovals->pluck('user.id')->all();
                $newUpline = $customerRemovals->first()['new_upline'];
                $updatedCustomers = User::whereIn('id', $customerIds)->update([
                    'referred_by' => $newUpline,
                ]);
            }
        });

        Log::info('Downlines cleared on upline managers via CLI command', [
            'catalog_moved_to_master' => $updatedCatalog,
            'staff_uplines_removed'   => $updatedStaff,
            'customer_uplines_cleared'=> $updatedCustomers,
            'master_manager_id'       => $masterManager->id,
        ]);

        $this->newLine();
        $this->info('Downlines successfully cleared on upline managers:');
        $this->line(" - <fg=green;options=bold>{$updatedCatalog}</> catalog role downlines moved/retained under Master Manager (#{$masterManager->id})");
        $this->line(" - <fg=green;options=bold>{$updatedStaff}</> admin/staff accounts had uplines removed completely");
        $this->line(" - <fg=green;options=bold>{$updatedCustomers}</> customer/retail downlines cleared");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Whether the user's current upline is valid for their role.
     * The platform master manager is an intentional holding upline for catalog
     * accounts, regardless of the account's normal direct-parent role.
     */
    private function hasExpectedUpline(User $user, int $masterManagerId): bool
    {
        if ($user->isStaff() || in_array($user->role, StaffAccess::ROLES, true)) {
            return false;
        }

        if ((int) $user->referred_by === $masterManagerId) {
            return in_array($user->role, self::CATALOG_ROLES, true);
        }

        $allowedParentRoles = [];
        foreach (RoleHierarchy::CHAIN as $parentRole => $childRole) {
            if ($childRole === $user->role) {
                $allowedParentRoles[] = $parentRole;
            }
        }

        // Both personal and agent accounts can be referred by either catalog chain.
        if (in_array($user->role, ['personal', 'agent'], true)) {
            $allowedParentRoles = ['partner', 'business_agent'];
        }

        return in_array($user->referrer?->role, $allowedParentRoles, true);
    }
}
