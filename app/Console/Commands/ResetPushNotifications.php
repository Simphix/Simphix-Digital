<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Services\VapidKeyGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetPushNotifications extends Command
{
    protected $signature = 'notifications:reset
                            {--subject= : New VAPID subject URL or mailto address (defaults to APP_URL from the env file)}
                            {--env-file= : Environment file to update (defaults to the application .env)}
                            {--dry-run : Show what would be reset without changing anything}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Reset web push notifications for a new server or domain by rotating VAPID keys and clearing old subscriptions';

    public function handle(VapidKeyGenerator $keyGenerator): int
    {
        $envFile = $this->option('env-file') ?: base_path('.env');
        if (! is_file($envFile) || ! is_readable($envFile)) {
            $this->error("Environment file not found or unreadable: {$envFile}");
            return self::FAILURE;
        }

        $originalEnv = file_get_contents($envFile);
        if ($originalEnv === false) {
            $this->error("Could not read environment file: {$envFile}");
            return self::FAILURE;
        }

        $subject = trim((string) ($this->option('subject') ?: $this->envValue($originalEnv, 'APP_URL') ?: config('app.url')));
        if (! $this->validSubject($subject)) {
            $this->error('Provide a valid HTTPS URL or mailto address with --subject, or set APP_URL in the environment file.');
            return self::FAILURE;
        }

        $subscriptionCount = PushSubscription::count();
        $this->info('Push notification reset preview');
        $this->line("VAPID subject: {$subject}");
        $this->line("Subscriptions to remove: {$subscriptionCount}");
        $this->line('Notification history and delivery records will be preserved.');

        if ($this->option('dry-run')) {
            $this->comment('Dry run complete. No keys, subscriptions, or caches were changed.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Rotate VAPID keys and remove all existing push subscriptions?', false)) {
            $this->warn('Reset cancelled.');
            return self::SUCCESS;
        }

        try {
            $keys = $keyGenerator->generate();
        } catch (\Throwable $exception) {
            $this->error('Could not generate VAPID keys. Check that PHP OpenSSL is enabled and can load its OpenSSL configuration.');
            $this->line($exception->getMessage());
            return self::FAILURE;
        }
        $updatedEnv = $this->setEnvValues($originalEnv, [
            'VAPID_SUBJECT' => $subject,
            'VAPID_PUBLIC_KEY' => $keys['publicKey'],
            'VAPID_PRIVATE_KEY' => $keys['privateKey'],
        ]);

        if (file_put_contents($envFile, $updatedEnv, LOCK_EX) === false) {
            $this->error("Could not update environment file: {$envFile}");
            return self::FAILURE;
        }

        try {
            DB::transaction(fn () => PushSubscription::query()->delete());
        } catch (\Throwable $exception) {
            file_put_contents($envFile, $originalEnv, LOCK_EX);
            $this->error('Database reset failed; the environment file was restored. ' . $exception->getMessage());
            return self::FAILURE;
        }

        Artisan::call('config:clear');
        Artisan::call('queue:restart');

        $this->newLine();
        $this->info('Push notification system reset successfully.');
        $this->line("- Updated VAPID subject to {$subject}");
        $this->line('- Generated and saved a new VAPID key pair');
        $this->line("- Removed {$subscriptionCount} old device subscription(s)");
        $this->line('- Cleared cached configuration and signalled queue workers to restart');
        $this->comment('Users must enable push notifications again on the new domain.');

        return self::SUCCESS;
    }

    private function envValue(string $contents, string $key): ?string
    {
        if (! preg_match('/^(?:export\s+)?' . preg_quote($key, '/') . '=(.*)$/m', $contents, $matches)) {
            return null;
        }

        return trim(trim($matches[1]), "\"'");
    }

    private function validSubject(string $subject): bool
    {
        if (str_starts_with($subject, 'mailto:')) {
            return filter_var(substr($subject, 7), FILTER_VALIDATE_EMAIL) !== false;
        }

        return filter_var($subject, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($subject, PHP_URL_SCHEME)) === 'https';
    }

    /** @param array<string, string> $values */
    private function setEnvValues(string $contents, array $values): string
    {
        foreach ($values as $key => $value) {
            $line = $key . '=' . $value;
            $pattern = '/^(?:export\s+)?' . preg_quote($key, '/') . '=.*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents, 1);
            } else {
                $contents = rtrim($contents, "\r\n") . "\n" . $line . "\n";
            }
        }

        return $contents;
    }
}
