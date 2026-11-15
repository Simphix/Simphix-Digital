<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin
                            {email? : The email address of the user to make super admin}
                            {--revoke : Revoke super admin status and revert user to personal role}';

    /**
     * The command aliases.
     *
     * @var array
     */
    protected $aliases = [
        'admin:make',
        'user:make-admin',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Temporarily disabled; manage admin accounts from the Super Admin web dashboard';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->error('Temporarily disabled. Manage admin accounts from the Super Admin web dashboard.');

        return Command::FAILURE;
    }
}
