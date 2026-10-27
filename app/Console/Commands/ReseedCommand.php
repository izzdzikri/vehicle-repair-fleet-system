<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ReseedCommand extends Command
{
    protected $signature = 'app:reseed {--force : Force the operation to run when in production}';
    protected $description = 'Safely wipe and re-seed the complete demo dataset (FreshSeeder + Psm2FeatureSeeder)';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            if (!$this->confirm('Application is in PRODUCTION. Are you sure you want to truncate and reseed all data?')) {
                $this->warn('Reseed cancelled.');
                return 0;
            }
        }

        $this->info('Starting database re-seed...');
        $start = microtime(true);

        try {
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DatabaseSeeder',
                '--force' => true,
            ], $this->output);

            if ($exitCode !== 0) {
                $this->error('Database seeding failed.');
                return $exitCode;
            }

            // Flush volatile cached query badges
            Cache::forget('sidebar_counts_admin');
            Cache::forget('sidebar_counts_coordinator');
            Cache::forget('maintenance_predictions_autorun');

            $elapsed = round((microtime(true) - $start) * 1000, 1);
            $this->newLine();
            $this->info("✓ Database successfully re-seeded in {$elapsed} ms.");

            // Display status
            $this->call('app:status');

            return 0;
        } catch (\Throwable $e) {
            $this->error('Reseed encountered an error: ' . $e->getMessage());
            return 1;
        }
    }
}
