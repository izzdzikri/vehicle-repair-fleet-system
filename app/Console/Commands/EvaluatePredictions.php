<?php
namespace App\Console\Commands;

use App\Services\MaintenancePredictionService;
use Illuminate\Console\Command;

class EvaluatePredictions extends Command
{
    protected $signature = 'maintenance:evaluate';
    protected $description = 'Backtest the rule-based maintenance predictor against actual consecutive service dates.';

    public function handle(MaintenancePredictionService $service): int {
        $r = $service->evaluate();

        if ($r['pairs'] === 0) {
            $this->warn('No vehicle has two or more recorded services of the same interval-tracked job type yet.');
            $this->line('Backtesting needs real repeat-service history (e.g. the client\'s past job cards).');
            return self::SUCCESS;
        }

        $this->info("Backtest over {$r['pairs']} consecutive-service pair(s). Error = predicted - actual (days).");

        $rows = [];
        foreach (['baseline' => 'Baseline (flat 40 km/day)', 'current' => 'Current (best-available rate)'] as $key => $label) {
            $m = $r[$key];
            $rows[] = [$label, $m['n'], $m['mae'], $m['bias'], $m['within_7'] . '%', $m['within_14'] . '%'];
        }
        $this->table(['Model', 'n', 'MAE (days)', 'Bias (days)', 'Within 7d', 'Within 14d'], $rows);

        $typeRows = [];
        foreach ($r['by_type'] as $name => $m) {
            $typeRows[] = [$name, $m['n'], $m['mae'], $m['bias'], $m['within_14'] . '%'];
        }
        $this->table(['Job type (current model)', 'n', 'MAE', 'Bias', 'Within 14d'], $typeRows);

        return self::SUCCESS;
    }
}