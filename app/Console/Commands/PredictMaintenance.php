<?php
namespace App\Console\Commands;

use App\Services\MaintenancePredictionService;
use Illuminate\Console\Command;

class PredictMaintenance extends Command
{
    protected $signature = 'maintenance:predict';
    protected $description = 'Scan all vehicles and generate/refresh predicted maintenance alerts from service history and usage rate.';

    public function handle(MaintenancePredictionService $service): int {
        $touched = $service->runAll();
        $this->info("Predictive maintenance scan complete — {$touched} alert(s) created or refreshed.");
        return self::SUCCESS;
    }
}