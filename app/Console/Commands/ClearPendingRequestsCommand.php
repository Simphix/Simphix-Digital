<?php

namespace App\Console\Commands;

use App\Models\CacRegistration;
use App\Models\CashOutRequest;
use App\Models\LoanRequest;
use App\Models\MoniepointPosRequest;
use App\Models\PlatformCashOutRequest;
use App\Models\SimRequest;
use App\Models\SimSwapRequest;
use App\Models\TravelRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearPendingRequestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:clear-pending
                            {--force : Clear without confirmation prompt}
                            {--type= : Specific request type to clear (sim, swap, loan, pos, travel, cashout, cac)}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'simphix:clear-pending-requests',
        'pending-requests:clear',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all pending requests across SIM cards, swaps, loans, POS terminals, travel bookings, cashouts, and CAC registrations';

    public function handle(): int
    {
        $filterType = $this->option('type') ? strtolower(trim((string) $this->option('type'))) : null;

        $validTypes = ['sim', 'swap', 'loan', 'pos', 'travel', 'cashout', 'cac'];
        if ($filterType && ! in_array($filterType, $validTypes, true)) {
            $this->error("Invalid request type [{$filterType}]. Valid options are: " . implode(', ', $validTypes));
            return self::FAILURE;
        }

        $counts = [];

        if (! $filterType || $filterType === 'sim') {
            $counts['SIM Card Requests'] = [
                'type' => 'sim',
                'model' => SimRequest::class,
                'query' => SimRequest::query()->where('status', 'pending'),
            ];
        }

        if (! $filterType || $filterType === 'swap') {
            $counts['SIM Swap Requests'] = [
                'type' => 'swap',
                'model' => SimSwapRequest::class,
                'query' => SimSwapRequest::query()->where('status', 'pending'),
            ];
        }

        if (! $filterType || $filterType === 'loan') {
            $counts['Moniepoint Loan Requests'] = [
                'type' => 'loan',
                'model' => LoanRequest::class,
                'query' => LoanRequest::query()->whereIn('status', [LoanRequest::STATUS_PENDING, LoanRequest::STATUS_UNDER_REVIEW]),
            ];
        }

        if (! $filterType || $filterType === 'pos') {
            $counts['Moniepoint POS Requests'] = [
                'type' => 'pos',
                'model' => MoniepointPosRequest::class,
                'query' => MoniepointPosRequest::query()->whereIn('status', [MoniepointPosRequest::STATUS_PENDING, MoniepointPosRequest::STATUS_UNDER_REVIEW]),
            ];
        }

        if (! $filterType || $filterType === 'travel') {
            $counts['Travel & Tour Bookings'] = [
                'type' => 'travel',
                'model' => TravelRequest::class,
                'query' => TravelRequest::query()->whereIn('status', [TravelRequest::STATUS_PENDING, TravelRequest::STATUS_REVIEWING]),
            ];
        }

        if (! $filterType || $filterType === 'cashout') {
            $counts['User Cashout Requests'] = [
                'type' => 'cashout_user',
                'model' => CashOutRequest::class,
                'query' => CashOutRequest::query()->where('status', 'pending'),
            ];
            $counts['Platform Cashout Requests'] = [
                'type' => 'cashout_platform',
                'model' => PlatformCashOutRequest::class,
                'query' => PlatformCashOutRequest::query()->where('status', 'pending'),
            ];
        }

        if (! $filterType || $filterType === 'cac') {
            $counts['CAC Registrations'] = [
                'type' => 'cac',
                'model' => CacRegistration::class,
                'query' => CacRegistration::query()->whereIn('status', ['submitted', 'under_review']),
            ];
        }

        $tableData = [];
        $totalPending = 0;

        foreach ($counts as $label => &$entry) {
            $c = (clone $entry['query'])->count();
            $entry['count'] = $c;
            $totalPending += $c;
            $tableData[] = [$label, number_format($c)];
        }
        unset($entry);

        if ($totalPending === 0) {
            $this->warn('No pending requests found in the database' . ($filterType ? " for type [{$filterType}]." : '.'));
            return self::SUCCESS;
        }

        $this->table(['Request Queue', 'Pending Count'], $tableData);
        $this->line("Total pending requests found: <fg=yellow;options=bold>{$totalPending}</>");

        if (! $this->option('force')) {
            $scope = $filterType ? "pending [{$filterType}]" : 'pending';
            $confirmed = $this->confirm(
                "Are you sure you want to permanently clear all {$totalPending} {$scope} request(s)? This action cannot be undone.",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No requests were cleared.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing pending requests... ');

        DB::transaction(function () use ($counts) {
            foreach ($counts as $entry) {
                if ($entry['count'] > 0) {
                    $entry['query']->delete();
                }
            }
        });

        $this->info('DONE.');
        $this->info("Successfully cleared {$totalPending} pending request(s).");

        return self::SUCCESS;
    }
}
