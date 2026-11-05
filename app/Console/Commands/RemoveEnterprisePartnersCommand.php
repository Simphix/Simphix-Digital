<?php

namespace App\Console\Commands;

use App\Models\BusinessCommissionRate;
use App\Models\DeviceVerificationChallenge;
use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerPayment;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SimCustomer;
use App\Models\SimHistory;
use App\Models\SimRequest;
use App\Models\SimSwapRequest;
use App\Models\Transaction;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveEnterprisePartnersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enterprise-partners:purge
                            {business? : ID, email, or business name of a specific Enterprise Business to clear}
                            {--business= : Target specific Enterprise Business by ID, email, or business name}
                            {--select : Interactively select from existing Enterprise Businesses}
                            {--all : Clear all Enterprise Businesses without prompting for selection}
                            {--force : Delete without confirmation prompt}
                            {--dry-run : Preview matching enterprise partner(s) and SIMs without deleting}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'enterprise:remove-all',
        'enterprise:clear-business',
        'enterprise:clear',
        'simphix:remove-enterprise-partners',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove selected or all Enterprise Partners from the SimPhix DB including their activated and unactivated SIMs';

    public function handle(): int
    {
        $this->info('Searching for Enterprise Partners and their associated SIMs...');

        // 1. Fetch all Enterprise Businesses on the platform
        $allEpUsers = User::query()
            ->where('role', 'business')
            ->orWhereIn('id', EnterprisePartnerApplication::select('user_id'))
            ->orderBy('id', 'asc')
            ->get();

        if ($allEpUsers->isEmpty()) {
            $this->warn('No Enterprise Partners found in the database.');
            return self::SUCCESS;
        }

        // 2. Determine target Enterprise Business(es)
        $targetInput = $this->argument('business') ?: $this->option('business');
        $targetAll = (bool) $this->option('all');
        $forceSelect = (bool) $this->option('select');
        $epUsers = collect();
        $isAll = true;

        if ($targetInput !== null && trim((string) $targetInput) !== '') {
            $isAll = false;
            $needle = trim((string) $targetInput);

            // Match by exact ID
            if (is_numeric($needle)) {
                $epUsers = $allEpUsers->filter(fn ($u) => (int) $u->id === (int) $needle);
            }

            // Match by email
            if ($epUsers->isEmpty()) {
                $epUsers = $allEpUsers->filter(fn ($u) => strcasecmp($u->email ?? '', $needle) === 0);
            }

            // Match by business_name or full name
            if ($epUsers->isEmpty()) {
                $epUsers = $allEpUsers->filter(function ($u) use ($needle) {
                    $bName = (string) ($u->business_name ?? '');
                    $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                    $userName = (string) ($u->name ?? '');

                    return stripos($bName, $needle) !== false
                        || stripos($fullName, $needle) !== false
                        || stripos($userName, $needle) !== false;
                });
            }

            if ($epUsers->isEmpty()) {
                $this->error("No Enterprise Business found matching '{$needle}'.");
                return self::FAILURE;
            }
        } elseif ($targetAll || ($this->option('force') && ! $forceSelect) || (! $forceSelect && $allEpUsers->count() === 1)) {
            $epUsers = $allEpUsers;
        } else {
            // Interactive selection
            if ($this->input->isInteractive()) {
                $tableRows = $allEpUsers->map(function ($u) {
                    $name = $u->business_name ?: trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                    if (empty($name)) {
                        $name = $u->name ?? ('User #' . $u->id);
                    }
                    $simCount = Sim::where('business_id', $u->id)->orWhere('user_id', $u->id)->count();
                    $agentCount = User::where('role', 'business_agent')
                        ->where(function ($q) use ($u) {
                            $q->where('referred_by', $u->id);
                            if (Schema::hasColumn('users', 'parent_id')) {
                                $q->orWhere('parent_id', $u->id);
                            }
                        })->count();

                    return [
                        $u->id,
                        $name,
                        $u->email ?? 'N/A',
                        $u->phone ?? 'N/A',
                        number_format($simCount),
                        number_format($agentCount),
                    ];
                })->all();

                $this->newLine();
                $this->line('<fg=cyan;options=bold>Existing Enterprise Businesses on SimPhix:</>');
                $this->table(['ID', 'Business Name', 'Email', 'Phone', 'SIMs', 'Agents'], $tableRows);

                // Build choices
                $choices = [
                    'all' => 'ALL Enterprise Businesses (' . $allEpUsers->count() . ' total)',
                ];
                foreach ($allEpUsers as $u) {
                    $name = $u->business_name ?: trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                    if (empty($name)) {
                        $name = $u->name ?? ('User #' . $u->id);
                    }
                    $choices[(string) $u->id] = sprintf('[#%d] %s (%s)', $u->id, $name, $u->email ?? 'no email');
                }

                $selectedKey = $this->choice(
                    'Select the Enterprise Business to clear (or select "all"):',
                    $choices,
                    'all'
                );

                if ($selectedKey === 'all' || $selectedKey === $choices['all']) {
                    $epUsers = $allEpUsers;
                    $isAll = true;
                } else {
                    $isAll = false;
                    $selectedId = null;
                    if (isset($choices[$selectedKey])) {
                        $selectedId = (int) $selectedKey;
                    } else {
                        $foundKey = array_search($selectedKey, $choices, true);
                        if ($foundKey !== false && $foundKey !== 'all') {
                            $selectedId = (int) $foundKey;
                        } elseif (preg_match('/\[#(\d+)\]/', (string) $selectedKey, $matches)) {
                            $selectedId = (int) $matches[1];
                        }
                    }

                    $epUsers = $allEpUsers->where('id', $selectedId);
                    if ($epUsers->isEmpty()) {
                        $this->error('Invalid selection.');
                        return self::FAILURE;
                    }
                }
            } else {
                // Non-interactive fallback defaults to all
                $epUsers = $allEpUsers;
            }
        }

        $epUserIds = $epUsers->pluck('id')->unique();

        // 3. Identify downline Business Agents linked to targeted Enterprise Partner(s)
        $businessAgentUserIds = User::query()
            ->where('role', 'business_agent')
            ->where(function ($q) use ($epUserIds) {
                $q->whereIn('referred_by', $epUserIds);
                if (Schema::hasColumn('users', 'parent_id')) {
                    $q->orWhereIn('parent_id', $epUserIds);
                }
            })
            ->pluck('id')
            ->unique();

        $allTargetUserIds = $epUserIds->merge($businessAgentUserIds)->unique();

        // 4. Identify SIMs associated with targeted Enterprise Partner(s) and Business Agents
        $simQuery = Sim::query()->where(function ($q) use ($epUserIds, $businessAgentUserIds, $allTargetUserIds, $isAll) {
            $q->whereIn('business_id', $epUserIds)
                ->orWhereIn('business_agent_id', $businessAgentUserIds)
                ->orWhereIn('user_id', $allTargetUserIds);

            if ($isAll) {
                $q->orWhereIn('status', [SimStatus::ASSIGNED_TO_BUSINESS, SimStatus::ASSIGNED_TO_BUSINESS_AGENT]);
            }
        });

        $totalSims = (clone $simQuery)->count();
        $activatedSims = (clone $simQuery)->where('status', SimStatus::ACTIVATED)->count();
        $unactivatedSims = $totalSims - $activatedSims;

        $epApplicationsCount = EnterprisePartnerApplication::whereIn('user_id', $epUserIds)->count();
        $epPaymentsCount = EnterprisePartnerPayment::whereIn('user_id', $epUserIds)->count();

        // Summary presentation
        $this->newLine();
        if (! $isAll && $epUsers->count() === 1) {
            $singleEp = $epUsers->first();
            $displayName = $singleEp->business_name ?: trim(($singleEp->first_name ?? '') . ' ' . ($singleEp->last_name ?? ''));
            if (empty($displayName)) {
                $displayName = $singleEp->name ?? ('User #' . $singleEp->id);
            }
            $this->line("<fg=yellow;options=bold>Targeted Enterprise Business:</> <fg=green;options=bold>{$displayName}</> (ID: #{$singleEp->id}, {$singleEp->email})");
        }

        $summaryRows = [
            ['Enterprise Partners Targeted', number_format($epUserIds->count())],
            ['Downline Business Agents (role = business_agent)', number_format($businessAgentUserIds->count())],
            ['Activated SIMs', number_format($activatedSims)],
            ['Unactivated SIMs (Assigned/Stock)', number_format($unactivatedSims)],
            ['Total Associated SIMs', number_format($totalSims)],
            ['Enterprise Partner Applications', number_format($epApplicationsCount)],
            ['Enterprise Partner Payments', number_format($epPaymentsCount)],
        ];

        $this->table(['Entity', 'Count'], $summaryRows);

        if ($this->option('dry-run')) {
            $this->info('[Dry Run] No records were deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $confirmPrompt = $isAll
                ? "Are you sure you want to permanently delete {$epUserIds->count()} Enterprise Partner(s), {$businessAgentUserIds->count()} Business Agent(s), and {$totalSims} associated SIM(s)?"
                : sprintf(
                    "Are you sure you want to permanently delete Enterprise Business '%s' (ID #%d), %d Business Agent(s), and %d associated SIM(s)?",
                    $epUsers->first()->business_name ?: ($epUsers->first()->name ?? ('User #' . $epUsers->first()->id)),
                    $epUsers->first()->id,
                    $businessAgentUserIds->count(),
                    $totalSims
                );

            $confirmed = $this->confirm($confirmPrompt, false);

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No records were deleted.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Purging Enterprise Partner(s) and associated SIMs... ');

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($epUserIds, $businessAgentUserIds, $allTargetUserIds, $simQuery) {
                $simIds = (clone $simQuery)->pluck('id');

                // Delete SIM dependent records in chunks
                foreach ($simIds->chunk(500) as $chunkIds) {
                    SimCustomer::whereIn('sim_id', $chunkIds)->delete();
                    SimHistory::whereIn('sim_id', $chunkIds)->delete();
                    SimActivationBonusGrant::whereIn('sim_id', $chunkIds)->delete();
                    SimSwapRequest::whereIn('sim_id', $chunkIds)->delete();
                    SimRequest::whereIn('sim_id', $chunkIds)->delete();
                    Sim::whereIn('id', $chunkIds)->delete();
                }

                // Delete Enterprise Partner specific application and payment records
                $appIds = EnterprisePartnerApplication::whereIn('user_id', $epUserIds)->pluck('id');
                EnterprisePartnerApplicationItem::whereIn('application_id', $appIds)->delete();
                EnterprisePartnerApplication::whereIn('id', $appIds)->delete();
                EnterprisePartnerPayment::whereIn('user_id', $epUserIds)->delete();
                BusinessCommissionRate::whereIn('business_id', $epUserIds)->delete();

                // Clean user financial records and authentication references
                Transaction::whereIn('user_id', $allTargetUserIds)->delete();
                Wallet::whereIn('user_id', $allTargetUserIds)->delete();
                VirtualAccount::whereIn('user_id', $allTargetUserIds)->delete();
                DeviceVerificationChallenge::whereIn('user_id', $allTargetUserIds)->delete();
                TrustedDevice::whereIn('user_id', $allTargetUserIds)->delete();

                // Nullify any referring relations pointing to deleted users
                User::whereIn('referred_by', $allTargetUserIds)->update(['referred_by' => null]);
                if (Schema::hasColumn('users', 'parent_id')) {
                    User::whereIn('parent_id', $allTargetUserIds)->update(['parent_id' => null]);
                }

                // Finally, delete the users
                User::whereIn('id', $allTargetUserIds)->delete();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('DONE.');
        $this->info("Successfully removed {$epUserIds->count()} Enterprise Partner(s), {$businessAgentUserIds->count()} Business Agent(s), and {$totalSims} associated SIM(s).");

        return self::SUCCESS;
    }
}
