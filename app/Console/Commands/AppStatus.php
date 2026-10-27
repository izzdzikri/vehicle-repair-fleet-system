<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AppStatus extends Command
{
    protected $signature = 'app:status';
    protected $description = 'Check backend health, database connections, migrations, and core dataset counts';

    public function handle(): int
    {
        $this->info("=== Teraju Fleet System: Backend Health & Status ===");
        $this->newLine();

        // 1. Environment Info
        $this->table(
            ['Environment Variable', 'Value'],
            [
                ['APP_NAME', config('app.name')],
                ['APP_ENV', config('app.env')],
                ['APP_DEBUG', config('app.debug') ? 'true (development)' : 'false (production)'],
                ['APP_URL', config('app.url')],
                ['CACHE_STORE', config('cache.default')],
                ['SESSION_DRIVER', config('session.driver')],
                ['QUEUE_CONNECTION', config('queue.default')],
            ]
        );
        $this->newLine();

        // 2. Database Connection Check
        $dbConnected = false;
        try {
            $connection = DB::connection();
            $connection->getPdo();
            $driver = $connection->getDriverName();
            $database = $connection->getDatabaseName();
            $this->info("✓ Database Connected: [{$driver}] Database: '{$database}'");
            $dbConnected = true;
        } catch (\Throwable $e) {
            $this->error("✗ Database Connection Failed: " . $e->getMessage());
        }
        $this->newLine();

        if (!$dbConnected) {
            $this->warn("Tip: If using podman/docker for MariaDB, run: podman start teraju-db");
            return 1;
        }

        // 3. Core Tables & Record Counts
        if (Schema::hasTable('users')) {
            $counts = [
                ['Total Users', DB::table('users')->count()],
                [' - Admins', DB::table('users')->where('role', 'admin')->count()],
                [' - Coordinators', DB::table('users')->where('role', 'coordinator')->count()],
                [' - Staff / Mechanics', DB::table('users')->where('role', 'staff')->count()],
                [' - Corporate PICs', DB::table('users')->where('role', 'corporate')->count()],
                [' - Individual Customers', DB::table('users')->where('role', 'individual')->count()],
                ['Companies', Schema::hasTable('companies') ? DB::table('companies')->count() : 0],
                ['Vehicles', Schema::hasTable('vehicles') ? DB::table('vehicles')->count() : 0],
                ['Appointments', Schema::hasTable('appointments') ? DB::table('appointments')->count() : 0],
                ['Job Cards', Schema::hasTable('job_cards') ? DB::table('job_cards')->count() : 0],
                ['Spare Parts (SKUs)', Schema::hasTable('spare_parts') ? DB::table('spare_parts')->count() : 0],
                ['Invoices', Schema::hasTable('invoices') ? DB::table('invoices')->count() : 0],
                ['Maintenance Alerts', Schema::hasTable('maintenance_alerts') ? DB::table('maintenance_alerts')->count() : 0],
            ];
            $this->table(['Core Entity', 'Record Count'], $counts);
        } else {
            $this->warn("Users table not found. Have migrations been executed? Run: php artisan migrate");
        }
        $this->newLine();

        // 4. Storage Link Check
        $publicStorage = public_path('storage');
        $storageLinked = File::exists($publicStorage) || is_link($publicStorage);
        if ($storageLinked) {
            $this->info("✓ Storage Link: public/storage exists");
        } else {
            $this->warn("! Storage Link: public/storage is missing. Run: php artisan storage:link");
        }

        $this->newLine();
        $this->info("Backend status check complete.");
        return 0;
    }
}
