<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\User;
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
    protected $description = 'Make a user a Super Admin by their email address, or revoke it';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Enter the user email address:');

        if (empty($email)) {
            $this->error('An email address is required.');
            return Command::FAILURE;
        }

        $email = trim($email);

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with email '{$email}'.");
            return Command::FAILURE;
        }

        if ($this->option('revoke')) {
            if ($user->role !== 'super_admin') {
                $this->warn("User {$user->first_name} {$user->last_name} ({$user->email}) is not a Super Admin (current role: {$user->role}).");
                return Command::SUCCESS;
            }

            $user->forceFill(['role' => 'personal'])->save();
            $this->info("Successfully revoked Super Admin from {$user->first_name} {$user->last_name} ({$user->email}). Role set to 'personal'.");
            return Command::SUCCESS;
        }

        if ($user->role === 'super_admin') {
            $this->info("User {$user->first_name} {$user->last_name} ({$user->email}) is already a Super Admin.");
            return Command::SUCCESS;
        }

        $previousRole = $user->role;

        // Clear any stale regional or coordinator jurisdiction
        if ($previousRole === 'regional_manager') {
            Region::where('regional_manager_id', $user->id)->update(['regional_manager_id' => null]);
        }
        if ($previousRole === 'coordinator') {
            SenatorialDistrict::where('coordinator_id', $user->id)->update(['coordinator_id' => null]);
        }

        $user->forceFill([
            'role' => 'super_admin',
            'status' => 'active',
        ])->save();

        $this->info("Successfully promoted {$user->first_name} {$user->last_name} ({$user->email}) from '{$previousRole}' to Super Admin.");

        return Command::SUCCESS;
    }
}
