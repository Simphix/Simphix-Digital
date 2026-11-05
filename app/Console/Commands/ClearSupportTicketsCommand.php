<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearSupportTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:clear
                            {--status= : Filter by ticket status (open, responded, closed)}
                            {--category= : Filter by ticket category (technical, billing, general, upgrade)}
                            {--force : Clear tickets without confirmation prompt}
                            {--dry-run : Preview matching tickets and messages without deleting}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'support:clear-tickets',
        'tickets:clear-all',
        'simphix:clear-tickets',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear support tickets and their associated messages from the database';

    public function handle(): int
    {
        $statusFilter = $this->option('status') ? strtolower(trim((string) $this->option('status'))) : null;
        $categoryFilter = $this->option('category') ? strtolower(trim((string) $this->option('category'))) : null;

        $ticketQuery = Ticket::query();

        if ($statusFilter) {
            $ticketQuery->where('status', $statusFilter);
        }

        if ($categoryFilter) {
            $ticketQuery->where('category', $categoryFilter);
        }

        $matchingTicketIds = (clone $ticketQuery)->pluck('id');
        $ticketCount = $matchingTicketIds->count();

        if ($ticketCount === 0) {
            $this->warn('No support tickets found matching the specified criteria.');
            return self::SUCCESS;
        }

        $messageCount = TicketMessage::whereIn('ticket_id', $matchingTicketIds)->count();

        // Status breakdown
        $openCount = (clone $ticketQuery)->where('status', Ticket::STATUS_OPEN)->count();
        $respondedCount = (clone $ticketQuery)->where('status', Ticket::STATUS_RESPONDED)->count();
        $closedCount = (clone $ticketQuery)->where('status', Ticket::STATUS_CLOSED)->count();

        $this->newLine();
        $this->table(
            ['Ticket Entity', 'Count'],
            [
                ['Total Support Tickets', number_format($ticketCount)],
                ['  - Open Tickets', number_format($openCount)],
                ['  - Responded Tickets', number_format($respondedCount)],
                ['  - Closed Tickets', number_format($closedCount)],
                ['Associated Ticket Messages', number_format($messageCount)],
            ]
        );

        if ($statusFilter) {
            $this->line("Filter (Status): <fg=cyan>{$statusFilter}</>");
        }
        if ($categoryFilter) {
            $this->line("Filter (Category): <fg=cyan>{$categoryFilter}</>");
        }

        if ($this->option('dry-run')) {
            $this->info('[Dry Run] No tickets or messages were deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Are you sure you want to permanently clear {$ticketCount} support ticket(s) and {$messageCount} message(s)? This action cannot be undone.",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No tickets were cleared.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing support tickets... ');

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($matchingTicketIds) {
                foreach ($matchingTicketIds->chunk(500) as $chunkIds) {
                    TicketMessage::whereIn('ticket_id', $chunkIds)->delete();
                    Ticket::whereIn('id', $chunkIds)->delete();
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('DONE.');
        $this->info("Successfully cleared {$ticketCount} support ticket(s) and {$messageCount} message(s).");

        return self::SUCCESS;
    }
}
