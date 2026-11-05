<?php

namespace App\Console\Commands;

use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SimCustomer;
use App\Models\SimHistory;
use App\Models\SimRequest;
use App\Models\SimSwapRequest;
use App\Support\SimStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveActivatedSimsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sims:remove-activated
                            {--force : Delete without confirmation prompt}
                            {--dry-run : Preview matching activated SIMs without deleting}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'sim:remove-activated',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all activated SIMs and their associated records from the database';

    public function handle(): int
    {
        $this->info('Searching for activated SIMs in SimPhix database...');

        $query = Sim::query()->where('status', SimStatus::ACTIVATED);
        $totalCount = (clone $query)->count();

        if ($totalCount === 0) {
            $this->warn('No activated SIMs found in the database.');
            return self::SUCCESS;
        }

        // Summary breakdown by provider and category
        $byProvider = (clone $query)
            ->select('provider', DB::raw('count(*) as aggregate'))
            ->groupBy('provider')
            ->pluck('aggregate', 'provider')
            ->toArray();

        $this->table(
            ['Provider', 'Activated SIM Count'],
            collect($byProvider)->map(fn ($cnt, $prov) => [strtoupper((string) $prov), number_format($cnt)])->values()->all()
        );

        $this->line("Total activated SIMs found: <fg=yellow;options=bold>{$totalCount}</>");

        if ($this->option('dry-run')) {
            $this->info('[Dry Run] No records were deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Are you sure you want to permanently delete {$totalCount} activated SIM(s)? This will also remove their linked customer details, history logs, and bonus grant ledgers.",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No SIMs were removed.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Deleting activated SIMs and cascade dependencies... ');

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($query) {
                $simIds = (clone $query)->pluck('id');

                foreach ($simIds->chunk(500) as $chunkIds) {
                    SimCustomer::whereIn('sim_id', $chunkIds)->delete();
                    SimHistory::whereIn('sim_id', $chunkIds)->delete();
                    SimActivationBonusGrant::whereIn('sim_id', $chunkIds)->delete();
                    SimSwapRequest::whereIn('sim_id', $chunkIds)->delete();
                    SimRequest::whereIn('sim_id', $chunkIds)->update(['sim_id' => null]);
                    Sim::whereIn('id', $chunkIds)->delete();
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('DONE.');
        $this->info("Successfully removed {$totalCount} activated SIM(s) from the database.");

        return self::SUCCESS;
    }
}
