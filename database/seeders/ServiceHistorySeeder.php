<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceHistorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('service_histories')->insert([
            [
                'job_card_id' => 1,
                'vehicle_id' => 10,
                'service_date' => Carbon::now()->subDays(30),
                'description' => 'Oil & Filter Change',
                'cost' => 83.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_card_id' => 2,
                'vehicle_id' => 12,
                'service_date' => Carbon::now()->subDays(25),
                'description' => 'Brake Pad Replacement',
                'cost' => 145.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
