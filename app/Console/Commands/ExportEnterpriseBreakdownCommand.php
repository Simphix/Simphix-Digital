<?php

namespace App\Console\Commands;

use App\Models\EnterprisePartnerApplication;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ExportEnterpriseBreakdownCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enterprise:export-breakdown
                            {business? : ID, email, or business name of a specific Enterprise Business to export}
                            {--business= : Target specific Enterprise Business by ID, email, or business name}
                            {--output= : Destination path for the Enterprise Business summary CSV file}
                            {--agents-output= : Destination path for the detailed Downline Agents breakdown CSV file}
                            {--detailed : Export both the Enterprise summary and individual Downline Agents breakdown}
                            {--with-inventory-only : Only export businesses with associated SIM inventory or downline agents}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'enterprise-business:export',
        'enterprise:export',
        'simphix:export-enterprise-breakdown',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export a comprehensive breakdown of Enterprise Business users, downline activations, balances, and SIM inventory';

    public function handle(): int
    {
        $this->info('Gathering Enterprise Business metrics, downline activations, balances, and SIM inventories...');

        // 1. Fetch Enterprise Businesses
        $allEpUsers = User::query()
            ->with(['wallet'])
            ->where('role', 'business')
            ->orWhereIn('id', EnterprisePartnerApplication::select('user_id'))
            ->orderBy('id', 'asc')
            ->get();

        if ($allEpUsers->isEmpty()) {
            $this->warn('No Enterprise Business accounts found on the platform.');
            return self::SUCCESS;
        }

        // 2. Filter if specific business was requested
        $targetInput = $this->argument('business') ?: $this->option('business');
        $epUsers = $allEpUsers;

        if ($targetInput !== null && trim((string) $targetInput) !== '') {
            $needle = trim((string) $targetInput);

            if (is_numeric($needle)) {
                $epUsers = $allEpUsers->filter(fn ($u) => (int) $u->id === (int) $needle);
            }

            if ($epUsers->isEmpty()) {
                $epUsers = $allEpUsers->filter(fn ($u) => strcasecmp($u->email ?? '', $needle) === 0);
            }

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
        }

        $hasParentIdColumn = Schema::hasColumn('users', 'parent_id');
        $hasLienColumn = Schema::hasColumn('wallets', 'lien_amount');

        // Destination paths
        $timestamp = now()->format('Y-m-d_His');
        $exportDir = storage_path('app/exports');
        File::ensureDirectoryExists($exportDir);

        $outputPath = $this->option('output');
        if ($outputPath) {
            $outputPath = trim((string) $outputPath, "'\"");
            File::ensureDirectoryExists(dirname($outputPath));
        } else {
            $outputPath = "{$exportDir}/enterprise_breakdown_{$timestamp}.csv";
        }

        $detailed = (bool) $this->option('detailed') || $this->option('agents-output') !== null;
        $agentsOutputPath = $this->option('agents-output');
        if ($agentsOutputPath) {
            $agentsOutputPath = trim((string) $agentsOutputPath, "'\"");
            File::ensureDirectoryExists(dirname($agentsOutputPath));
        } elseif ($detailed) {
            $agentsOutputPath = "{$exportDir}/enterprise_agents_breakdown_{$timestamp}.csv";
        }

        $mainHandle = fopen($outputPath, 'w');
        if ($mainHandle === false) {
            $this->error("Failed to open file for writing: {$outputPath}");
            return self::FAILURE;
        }
        // Write UTF-8 BOM
        fprintf($mainHandle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Main Enterprise CSV Headers
        fputcsv($mainHandle, [
            'Enterprise ID',
            'Business Name',
            'Contact Person',
            'Email',
            'Phone',
            'CAC Number',
            'State',
            'LGA',
            'Role',
            'Account Tier',
            'Status',
            'EP Application Status',
            'EP Application Snapshot (NGN)',
            // Enterprise Own Balances
            'Enterprise Main Balance (NGN)',
            'Enterprise Commission Balance (NGN)',
            'Enterprise Hold Balance (NGN)',
            'Enterprise Lien Balance (NGN)',
            'Enterprise Total Wallet Balance (NGN)',
            // Downline Agents Count & Balances
            'Total Downline Agents Count',
            'Active Agents Count (with SIMs or Balances)',
            'Downline Agents Total Main Balance (NGN)',
            'Downline Agents Total Commission Balance (NGN)',
            'Downline Agents Combined Balance (NGN)',
            // Combined Ecosystem Balance
            'Total Ecosystem Balance (NGN)',
            // SIM Inventory & Activations: Enterprise Own (Direct)
            'Enterprise Own SIMs (Total)',
            'Enterprise Own Activated SIMs',
            'Enterprise Own Stock/Unactivated SIMs',
            'Enterprise Own Activation Rate (%)',
            // SIM Inventory & Activations: Downline Agents
            'Downline Agents SIMs (Total)',
            'Downline Agents Activated SIMs',
            'Downline Agents Stock/Unactivated SIMs',
            'Downline Agents Activation Rate (%)',
            // Total SIM Inventory & Activations (Combined Tree)
            'Total Ecosystem SIMs Allocated',
            'Total Ecosystem Activated SIMs',
            'Total Ecosystem Stock/Unactivated SIMs',
            'Total Ecosystem Activation Rate (%)',
            // Network Breakdown (Total / Activated / Stock)
            'MTN Breakdown (Total/Act/Stock)',
            'Glo Breakdown (Total/Act/Stock)',
            'Airtel Breakdown (Total/Act/Stock)',
            '9mobile Breakdown (Total/Act/Stock)',
            'Registered At',
        ]);

        $agentsHandle = null;
        if ($detailed && $agentsOutputPath) {
            $agentsHandle = fopen($agentsOutputPath, 'w');
            if ($agentsHandle !== false) {
                fprintf($agentsHandle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($agentsHandle, [
                    'Parent Enterprise ID',
                    'Parent Enterprise Name',
                    'Agent User ID',
                    'Agent Name',
                    'Agent Email',
                    'Agent Phone',
                    'Agent Status',
                    'Agent Main Balance (NGN)',
                    'Agent Commission Balance (NGN)',
                    'Agent Total Balance (NGN)',
                    'Agent Assigned SIMs (Total)',
                    'Agent Activated SIMs',
                    'Agent Stock/Unactivated SIMs',
                    'Agent Activation Rate (%)',
                    'MTN SIMs (Total/Act/Stock)',
                    'Glo SIMs (Total/Act/Stock)',
                    'Airtel SIMs (Total/Act/Stock)',
                    '9mobile SIMs (Total/Act/Stock)',
                    'Agent Created At',
                ]);
            }
        }

        // Aggregate counters for console summary
        $aggTotalBusinesses = 0;
        $aggTotalAgents = 0;
        $aggEpMainBal = 0.00;
        $aggEpCommBal = 0.00;
        $aggAgentMainBal = 0.00;
        $aggAgentCommBal = 0.00;
        $aggEpOwnSims = 0;
        $aggEpOwnActivated = 0;
        $aggAgentSims = 0;
        $aggAgentActivated = 0;
        $aggTotalSims = 0;
        $aggTotalActivated = 0;

        $withInventoryOnly = (bool) $this->option('with-inventory-only');

        $bar = $this->output->createProgressBar($epUsers->count());
        $bar->start();

        foreach ($epUsers as $epUser) {
            // Find downline agents
            $agents = User::query()
                ->with('wallet')
                ->where('role', 'business_agent')
                ->where(function ($q) use ($epUser, $hasParentIdColumn) {
                    $q->where('referred_by', $epUser->id);
                    if ($hasParentIdColumn) {
                        $q->orWhere('parent_id', $epUser->id);
                    }
                })
                ->get();

            $agentIds = $agents->pluck('id')->all();

            // Fetch all SIMs associated with this Enterprise Business tree
            $sims = Sim::query()
                ->where(function ($q) use ($epUser, $agentIds) {
                    $q->where('business_id', $epUser->id);
                    if (! empty($agentIds)) {
                        $q->orWhereIn('business_agent_id', $agentIds);
                    }
                })
                ->get(['id', 'business_id', 'business_agent_id', 'provider', 'status']);

            $totalTreeSims = $sims->count();

            // If with-inventory-only is requested, skip businesses with 0 SIMs and 0 agents
            if ($withInventoryOnly && $totalTreeSims === 0 && $agents->isEmpty()) {
                $bar->advance();
                continue;
            }

            // Enterprise Own SIMs (held directly by the business, not assigned to a sub-agent)
            $ownSims = $sims->filter(fn ($s) => empty($s->business_agent_id));
            $ownTotal = $ownSims->count();
            $ownActivated = $ownSims->where('status', SimStatus::ACTIVATED)->count();
            $ownStock = $ownTotal - $ownActivated;
            $ownRate = $ownTotal > 0 ? round(($ownActivated / $ownTotal) * 100, 2) : 0.00;

            // Downline Agents SIMs
            $agentSims = $sims->filter(fn ($s) => ! empty($s->business_agent_id));
            $agentSimsTotal = $agentSims->count();
            $agentSimsActivated = $agentSims->where('status', SimStatus::ACTIVATED)->count();
            $agentSimsStock = $agentSimsTotal - $agentSimsActivated;
            $agentRate = $agentSimsTotal > 0 ? round(($agentSimsActivated / $agentSimsTotal) * 100, 2) : 0.00;

            // Combined Ecosystem SIM totals
            $allActivated = $ownActivated + $agentSimsActivated;
            $allStock = $ownStock + $agentSimsStock;
            $combinedRate = $totalTreeSims > 0 ? round(($allActivated / $totalTreeSims) * 100, 2) : 0.00;

            // Provider breakdowns helper
            $formatProviderStats = function (Collection $collection, string $providerName): string {
                $matching = $collection->filter(fn ($s) => strtolower(trim((string) $s->provider)) === strtolower($providerName));
                $tot = $matching->count();
                $act = $matching->where('status', SimStatus::ACTIVATED)->count();
                $stk = $tot - $act;
                return "{$tot} / {$act} / {$stk}";
            };

            $mtnStats = $formatProviderStats($sims, 'mtn');
            $gloStats = $formatProviderStats($sims, 'glo');
            $airtelStats = $formatProviderStats($sims, 'airtel');
            $nineMobileStats = $formatProviderStats($sims, '9mobile');

            // Balances: Enterprise User
            $epWallet = $epUser->wallet;
            $epMainBal = $epWallet ? (float) $epWallet->balance : 0.00;
            $epCommBal = $epWallet ? (float) $epWallet->bonus : 0.00;
            $epHoldBal = $epWallet ? (float) $epWallet->hold_amount : 0.00;
            $epLienBal = ($epWallet && $hasLienColumn) ? (float) ($epWallet->lien_amount ?? 0) : 0.00;
            $epTotalBal = $epMainBal + $epCommBal;

            // Balances: Downline Agents
            $agentsMainBal = 0.00;
            $agentsCommBal = 0.00;
            $activeAgentsCount = 0;

            foreach ($agents as $agent) {
                $aWallet = $agent->wallet;
                $aMain = $aWallet ? (float) $aWallet->balance : 0.00;
                $aComm = $aWallet ? (float) $aWallet->bonus : 0.00;

                $agentsMainBal += $aMain;
                $agentsCommBal += $aComm;

                // Check if agent holds SIMs or balances
                $agentSimCount = $sims->where('business_agent_id', $agent->id)->count();
                if ($agentSimCount > 0 || ($aMain + $aComm) > 0) {
                    $activeAgentsCount++;
                }

                // If detailed agent breakdown requested, write to agent CSV
                if ($agentsHandle) {
                    $agentSimsColl = $sims->where('business_agent_id', $agent->id);
                    $aTotalSims = $agentSimsColl->count();
                    $aActSims = $agentSimsColl->where('status', SimStatus::ACTIVATED)->count();
                    $aStkSims = $aTotalSims - $aActSims;
                    $aRate = $aTotalSims > 0 ? round(($aActSims / $aTotalSims) * 100, 2) : 0.00;

                    $agentDisplayName = trim(($agent->first_name ?? '') . ' ' . ($agent->last_name ?? ''));
                    if (empty($agentDisplayName)) {
                        $agentDisplayName = $agent->name ?? ('Agent #' . $agent->id);
                    }

                    fputcsv($agentsHandle, [
                        $epUser->id,
                        $epUser->business_name ?: ($epUser->name ?? 'N/A'),
                        $agent->id,
                        $agentDisplayName,
                        $agent->email ?? 'N/A',
                        $agent->phone ?? 'N/A',
                        ucfirst($agent->status ?? 'active'),
                        number_format($aMain, 2, '.', ''),
                        number_format($aComm, 2, '.', ''),
                        number_format($aMain + $aComm, 2, '.', ''),
                        $aTotalSims,
                        $aActSims,
                        $aStkSims,
                        number_format($aRate, 2, '.', ''),
                        $formatProviderStats($agentSimsColl, 'mtn'),
                        $formatProviderStats($agentSimsColl, 'glo'),
                        $formatProviderStats($agentSimsColl, 'airtel'),
                        $formatProviderStats($agentSimsColl, '9mobile'),
                        $agent->created_at?->toDateTimeString() ?? 'N/A',
                    ]);
                }
            }

            $agentsCombinedBal = $agentsMainBal + $agentsCommBal;
            $ecosystemTotalBal = $epTotalBal + $agentsCombinedBal;

            // Application snapshot
            $latestApp = EnterprisePartnerApplication::where('user_id', $epUser->id)->latest('id')->first();
            $appStatus = $latestApp ? ucfirst($latestApp->status) : 'N/A';
            $appSnapshot = $latestApp ? (float) $latestApp->total_amount_snapshot : 0.00;

            $contactName = trim(($epUser->first_name ?? '') . ' ' . ($epUser->last_name ?? ''));
            if (empty($contactName)) {
                $contactName = $epUser->name ?? 'N/A';
            }

            // Write row to Main Enterprise CSV
            fputcsv($mainHandle, [
                $epUser->id,
                $epUser->business_name ?: $contactName,
                $contactName,
                $epUser->email ?? 'N/A',
                $epUser->phone ?? 'N/A',
                $epUser->cac_number ?? 'N/A',
                $epUser->state ?? 'N/A',
                $epUser->lga ?? 'N/A',
                ucwords(str_replace('_', ' ', $epUser->role ?? 'business')),
                $epUser->account_tier ?? 0,
                ucfirst($epUser->status ?? 'active'),
                $appStatus,
                number_format($appSnapshot, 2, '.', ''),
                number_format($epMainBal, 2, '.', ''),
                number_format($epCommBal, 2, '.', ''),
                number_format($epHoldBal, 2, '.', ''),
                number_format($epLienBal, 2, '.', ''),
                number_format($epTotalBal, 2, '.', ''),
                $agents->count(),
                $activeAgentsCount,
                number_format($agentsMainBal, 2, '.', ''),
                number_format($agentsCommBal, 2, '.', ''),
                number_format($agentsCombinedBal, 2, '.', ''),
                number_format($ecosystemTotalBal, 2, '.', ''),
                $ownTotal,
                $ownActivated,
                $ownStock,
                number_format($ownRate, 2, '.', ''),
                $agentSimsTotal,
                $agentSimsActivated,
                $agentSimsStock,
                number_format($agentRate, 2, '.', ''),
                $totalTreeSims,
                $allActivated,
                $allStock,
                number_format($combinedRate, 2, '.', ''),
                $mtnStats,
                $gloStats,
                $airtelStats,
                $nineMobileStats,
                $epUser->created_at?->toDateTimeString() ?? 'N/A',
            ]);

            // Accumulate aggregates
            $aggTotalBusinesses++;
            $aggTotalAgents += $agents->count();
            $aggEpMainBal += $epMainBal;
            $aggEpCommBal += $epCommBal;
            $aggAgentMainBal += $agentsMainBal;
            $aggAgentCommBal += $agentsCommBal;
            $aggEpOwnSims += $ownTotal;
            $aggEpOwnActivated += $ownActivated;
            $aggAgentSims += $agentSimsTotal;
            $aggAgentActivated += $agentSimsActivated;
            $aggTotalSims += $totalTreeSims;
            $aggTotalActivated += $allActivated;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        fclose($mainHandle);
        if ($agentsHandle) {
            fclose($agentsHandle);
        }

        // Summary Presentation Table
        $this->line('<fg=cyan;options=bold>SimPhix Enterprise Business Ecosystem Summary:</>');
        $this->table(
            ['Ecosystem Metric', 'Metric Value'],
            [
                ['Total Enterprise Businesses Exported', number_format($aggTotalBusinesses)],
                ['Total Downline Business Agents', number_format($aggTotalAgents)],
                ['Enterprise Main Balances (`wallet.balance`)', '₦' . number_format($aggEpMainBal, 2)],
                ['Enterprise Commission Balances (`wallet.bonus`)', '₦' . number_format($aggEpCommBal, 2)],
                ['Downline Agents Main Balances', '₦' . number_format($aggAgentMainBal, 2)],
                ['Downline Agents Commission Balances', '₦' . number_format($aggAgentCommBal, 2)],
                ['Combined Ecosystem Total Balances', '₦' . number_format($aggEpMainBal + $aggEpCommBal + $aggAgentMainBal + $aggAgentCommBal, 2)],
                ['Enterprise Own Direct SIM Stock (Allocated / Activated)', number_format($aggEpOwnSims) . ' / ' . number_format($aggEpOwnActivated)],
                ['Downline Agents SIM Inventory (Allocated / Activated)', number_format($aggAgentSims) . ' / ' . number_format($aggAgentActivated)],
                ['Overall Total SIMs Allocated', number_format($aggTotalSims)],
                ['Overall Total Activated SIMs', number_format($aggTotalActivated)],
                ['Overall Activation Rate', ($aggTotalSims > 0 ? number_format(($aggTotalActivated / $aggTotalSims) * 100, 2) : '0.00') . '%'],
            ]
        );

        $this->newLine();
        $this->info('Enterprise breakdown export generated successfully!');
        $this->line("Main Summary CSV: <fg=green;options=bold>{$outputPath}</>");

        if ($detailed && $agentsOutputPath) {
            $this->line("Detailed Downline Agents CSV: <fg=green;options=bold>{$agentsOutputPath}</>");
        }

        return self::SUCCESS;
    }
}
