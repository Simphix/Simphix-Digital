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
                            {--force : Delete without confirmation prompt}
                            {--dry-run : Preview matching enterprise partners and SIMs without deleting}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'enterprise:remove-all',
        'simphix:remove-enterprise-partners',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all Enterprise Partners from the SimPhix DB including their activated and unactivated SIMs';

    public function handle(): int
    {
        $this->info('Searching for Enterprise Partners and their associated SIMs...');

        // 1. Identify Enterprise Partners (role = business or users with EP applications)
        $epUserIds = User::query()
            ->where('role', 'business')
            ->orWhereIn('id', EnterprisePartnerApplication::select('user_id'))
            ->pluck('id')
            ->unique();

        if ($epUserIds->isEmpty()) {
            $this->warn('No Enterprise Partners found in the database.');
            return self::SUCCESS;
        }

        // 2. Identify downline Business Agents linked to these Enterprise Partners
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

        // 3. Identify SIMs associated with these Enterprise Partners and Business Agents
        $simQuery = Sim::query()->where(function ($q) use ($epUserIds, $businessAgentUserIds, $allTargetUserIds) {
            $q->whereIn('business_id', $epUserIds)
                ->orWhereIn('business_agent_id', $businessAgentUserIds)
                ->orWhereIn('user_id', $allTargetUserIds)
                ->orWhereIn('status', [SimStatus::ASSIGNED_TO_BUSINESS, SimStatus::ASSIGNED_TO_BUSINESS_AGENT]);
        });

        $totalSims = (clone $simQuery)->count();
        $activatedSims = (clone $simQuery)->where('status', SimStatus::ACTIVATED)->count();
        $unactivatedSims = $totalSims - $activatedSims;

        $epApplicationsCount = EnterprisePartnerApplication::whereIn('user_id', $epUserIds)->count();
        $epPaymentsCount = EnterprisePartnerPayment::whereIn('user_id', $epUserIds)->count();

        $this->table(
            ['Entity', 'Count'],
            [
                ['Enterprise Partners (role = business)', number_format($epUserIds->count())],
                ['Downline Business Agents (role = business_agent)', number_format($businessAgentUserIds->count())],
                ['Activated SIMs', number_format($activatedSims)],
                ['Unactivated SIMs (Assigned/Stock)', number_format($unactivatedSims)],
                ['Total Associated SIMs', number_format($totalSims)],
                ['Enterprise Partner Applications', number_format($epApplicationsCount)],
                ['Enterprise Partner Payments', number_format($epPaymentsCount)],
            ]
        );

        if ($this->option('dry-run')) {
            $this->info('[Dry Run] No records were deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Are you sure you want to permanently delete {$epUserIds->count()} Enterprise Partner(s), {$businessAgentUserIds->count()} Business Agent(s), and {$totalSims} associated SIM(s)?",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No records were deleted.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Purging Enterprise Partners and associated SIMs... ');

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

        $this->info('DONE.');
        $this->info("Successfully removed {$epUserIds->count()} Enterprise Partner(s), {$businessAgentUserIds->count()} Business Agent(s), and {$totalSims} associated SIM(s).");

        return self::SUCCESS;
    }
}
