<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Running `php artisan migrate:fresh --seed` (or `php artisan db:seed`
     * on its own) now gives you the complete demo dataset in one shot —
     * core records from FreshSeeder, then the richer PSM2 feature data
     * (attendance, leave, salary, suppliers, purchase orders, invoices,
     * payments) layered on top by Psm2FeatureSeeder. Order matters:
     * FreshSeeder must run first since Psm2FeatureSeeder updates rows
     * it creates (job_cards.completed_at, users.monthly_salary/hire_date).
     */
    public function run(): void
    {
        $this->call([
            FreshSeeder::class,
            Psm2FeatureSeeder::class,
        ]);
    }
}