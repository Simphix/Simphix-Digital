<?php

namespace App\Console\Commands;

use App\Jobs\SendAdminNotificationJob;
use App\Models\AdminNotification;
use Illuminate\Console\Command;

class DispatchScheduledNotifications extends Command
{
    protected $signature = 'notifications:dispatch-due';

    protected $description = 'Dispatch push notifications whose scheduled_at time has arrived';

    public function handle(): int
    {
        $due = AdminNotification::due()->get();

        foreach ($due as $notification) {
            $notification->update(['status' => 'sending']);
            SendAdminNotificationJob::dispatch($notification);
        }

        $this->info("Dispatched {$due->count()} due notification(s).");

        return self::SUCCESS;
    }
}
