<?php
namespace App\Services;

use App\Models\Vehicle;
use App\Models\JobType;
use App\Models\ServiceHistory;
use App\Models\MaintenanceAlert;
use App\Models\TripLog;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

/**
 * Rule-based predictive maintenance scanner — NOT machine learning.
 *
 * For each vehicle and each interval-tracked JobType, it projects a
 * next-due date from the vehicle's last matching ServiceHistory entry
 * plus an estimated daily-km usage rate, and creates/refreshes a
 * MaintenanceAlert when that projection falls inside the alert horizon.
 *
 * Usage-rate estimation uses three tiers, best evidence first:
 *   1. trip_logs        — >= 2 logged trips spanning >= 1 day
 *   2. odometer_average — lifetime odometer / vehicle age (clamped)
 *   3. default          — flat 40 km/day
 *
 * Interval projection starts from the nominal job-type interval and,
 * once >= 3 real service gaps exist for that job type, is shrunk toward
 * the observed median gap (weight n / (n + 5)).
 *
 * evaluate() backtests the projection against real consecutive services
 * so accuracy can be reported instead of assumed.
 */
class MaintenancePredictionService
{
    private const DEFAULT_DAILY_KM = 40;
    private const MIN_DAILY_KM     = 10;
    private const MAX_DAILY_KM     = 150;

    /** Days before a predicted due date counts as "due soon" (medium). */
    private const DUE_SOON_DAYS = 14;

    /** Outer window beyond which we don't alert yet at all. */
    private const HORIZON_DAYS = 45;

    /** Minimum observed service gaps before they influence a projection. */
    private const MIN_OBSERVED_GAPS = 3;

    /** @var array<int, array{median: ?int, n: int}> */
    private array $observedCache = [];

    /**
     * @return int number of alerts created or refreshed
     */
    public function runAll(): int {
        $intervalTypes = JobType::whereNotNull('interval_km')
            ->orWhereNotNull('interval_months')
            ->get();

        if ($intervalTypes->isEmpty()) {
            return 0;
        }

        $this->observedCache = [];
        $touched = 0;

        Vehicle::with('owner')->chunk(50, function ($vehicles) use ($intervalTypes, &$touched) {
            foreach ($vehicles as $vehicle) {
                $dailyRate = $this->estimateDailyKm($vehicle);

                foreach ($intervalTypes as $jobType) {
                    if ($this->predictAndStore($vehicle, $jobType, $dailyRate)) {
                        $touched++;
                    }
                }
            }
        });

        return $touched;
    }

    public function estimateDailyKm(Vehicle $vehicle): float {
        return (float) $this->getUsageStats($vehicle)['daily_km'];
    }

    /**
     * Usage statistics for a vehicle. Keeps the original keys
     * (daily_km, is_fallback, trip_count, total_logged_km, span_days)
     * and adds method / label / note for the UI.
     */
    public function getUsageStats(Vehicle $vehicle): array {
        $trips = TripLog::where('vehicle_id', $vehicle->id)
            ->orderByDesc('trip_date')
            ->limit(20)
            ->get();

        $count   = $trips->count();
        $totalKm = (float) $trips->sum('distance_km');
        $spanDays = 0;

        if ($count >= 2) {
            $earliest = Carbon::parse($trips->min('trip_date'));
            $latest   = Carbon::parse($trips->max('trip_date'));
            $spanDays = (int) round(abs($earliest->diffInDays($latest)));

            if ($spanDays >= 1 && $totalKm > 0) {
                return [
                    'daily_km'        => round($totalKm / $spanDays, 1),
                    'is_fallback'     => false,
                    'method'          => 'trip_logs',
                    'label'           => 'Custom dynamic rate',
                    'note'            => "Calculated from {$count} recorded journeys (" . number_format($totalKm, 1) . " km over {$spanDays} days).",
                    'trip_count'      => $count,
                    'total_logged_km' => round($totalKm, 1),
                    'span_days'       => $spanDays,
                ];
            }
        }

        // Tier 2: lifetime odometer average
        $odo = $this->odometerAverage($vehicle);
        if ($odo !== null) {
            return [
                'daily_km'        => $odo,
                'is_fallback'     => true,
                'method'          => 'odometer_average',
                'label'           => 'Odometer average (lifetime)',
                'note'            => 'Estimated from odometer reading ÷ vehicle age. Log 2 or more trips across multiple days to personalise.',
                'trip_count'      => $count,
                'total_logged_km' => round($totalKm, 1),
                'span_days'       => $spanDays,
            ];
        }

        // Tier 3: flat default
        return [
            'daily_km'        => (float) self::DEFAULT_DAILY_KM,
            'is_fallback'     => true,
            'method'          => 'default',
            'label'           => 'Fallback heuristic (' . self::DEFAULT_DAILY_KM . ' km/d)',
            'note'            => 'No trip or odometer history available — using the default ' . self::DEFAULT_DAILY_KM . ' km/day. Log 2 or more trips to personalise.',
            'trip_count'      => $count,
            'total_logged_km' => round($totalKm, 1),
            'span_days'       => $spanDays,
        ];
    }

    /**
     * Lifetime average km/day = odometer / age in days. Age is floored
     * at one year so a nearly-new car with a small odometer doesn't
     * produce a wild rate. Result is clamped to a sane range.
     */
    private function odometerAverage(Vehicle $vehicle): ?float {
        $mileage = (float) $vehicle->mileage;
        $year    = (int) $vehicle->year;

        if ($mileage <= 0 || $year < 1980) {
            return null;
        }

        $ageDays = max(365, (int) round(abs(Carbon::create($year, 1, 1)->diffInDays(now()))));
        $rate    = $mileage / $ageDays;

        return round(min(self::MAX_DAILY_KM, max(self::MIN_DAILY_KM, $rate)), 1);
    }

    /**
     * Median of real gaps (days) between consecutive services of this
     * job type, across the whole fleet.
     *
     * @return array{median: ?int, n: int}
     */
    public function observedIntervalDays(JobType $jobType): array {
        if (isset($this->observedCache[$jobType->id])) {
            return $this->observedCache[$jobType->id];
        }

        $rows = ServiceHistory::query()
            ->join('job_cards', 'job_cards.id', '=', 'service_history.job_card_id')
            ->where('job_cards.job_type_id', $jobType->id)
            ->orderBy('service_history.vehicle_id')
            ->orderBy('service_history.service_date')
            ->get(['service_history.vehicle_id', 'service_history.service_date']);

        $gaps = [];
        foreach ($rows->groupBy('vehicle_id') as $list) {
            $prev = null;
            foreach ($list as $r) {
                $d = Carbon::parse($r->service_date);
                if ($prev) {
                    $g = (int) round(abs($prev->diffInDays($d)));
                    if ($g >= 7) {           // ignore same-week repeat visits
                        $gaps[] = $g;
                    }
                }
                $prev = $d;
            }
        }

        $result = ['median' => null, 'n' => count($gaps)];
        if ($gaps) {
            sort($gaps);
            $mid = intdiv(count($gaps), 2);
            $result['median'] = count($gaps) % 2
                ? $gaps[$mid]
                : (int) round(($gaps[$mid - 1] + $gaps[$mid]) / 2);
        }

        return $this->observedCache[$jobType->id] = $result;
    }

    /**
     * Core projection, shared by the live scanner and the backtest.
     * Pass $useObserved=false to get the purely rule-based date (used by
     * evaluate() so the same data isn't used to both fit and score).
     */
    public function projectDueDate(Carbon $lastDate, JobType $jobType, float $dailyRate, bool $useObserved = true): ?Carbon {
        $candidates = [];

        if ($jobType->interval_months) {
            $candidates[] = $lastDate->copy()->addMonths($jobType->interval_months);
        }
        if ($jobType->interval_km && $dailyRate > 0) {
            $candidates[] = $lastDate->copy()->addDays((int) ceil($jobType->interval_km / $dailyRate));
        }
        if (empty($candidates)) {
            return null;
        }

        /** @var Carbon $nominal */
        $nominal = min($candidates);

        if ($useObserved) {
            $obs = $this->observedIntervalDays($jobType);
            if ($obs['n'] >= self::MIN_OBSERVED_GAPS && $obs['median'] !== null) {
                $nominalDays = (int) round(abs($lastDate->diffInDays($nominal)));
                $w    = $obs['n'] / ($obs['n'] + 5);
                $days = (int) round($nominalDays * (1 - $w) + $obs['median'] * $w);
                return $lastDate->copy()->addDays($days);
            }
        }

        return $nominal;
    }

    private function predictAndStore(Vehicle $vehicle, JobType $jobType, float $dailyRate): bool {
        $lastService = ServiceHistory::where('vehicle_id', $vehicle->id)
            ->whereHas('jobCard', fn($q) => $q->where('job_type_id', $jobType->id))
            ->orderByDesc('service_date')
            ->first();

        // No baseline to project from — this projects forward from a
        // known last service, it does not guess when a never-serviced
        // vehicle will first need one.
        if (!$lastService) {
            return false;
        }

        $lastDate         = Carbon::parse($lastService->service_date);
        $predictedDueDate = $this->projectDueDate($lastDate, $jobType, $dailyRate);

        if (!$predictedDueDate) {
            return false;
        }

        $daysUntilDue = (int) now()->startOfDay()->diffInDays($predictedDueDate->copy()->startOfDay(), false);

        if ($daysUntilDue > self::HORIZON_DAYS) {
            return false;
        }

        $urgency = match(true) {
            $daysUntilDue <= 0                   => 'high',
            $daysUntilDue <= self::DUE_SOON_DAYS => 'medium',
            default                              => 'low',
        };

        $recommendation = $daysUntilDue <= 0
            ? sprintf(
                '%s is overdue by %d day(s), based on an estimated usage of %.0f km/day since the last service on %s.',
                $jobType->name, abs($daysUntilDue), $dailyRate, $lastDate->format('d M Y')
              )
            : sprintf(
                '%s predicted due around %s (~%d day(s)), based on an estimated usage of %.0f km/day since the last service on %s.',
                $jobType->name, $predictedDueDate->format('d M Y'), $daysUntilDue, $dailyRate, $lastDate->format('d M Y')
              );

        $existing = MaintenanceAlert::where('vehicle_id', $vehicle->id)
            ->where('job_type_id', $jobType->id)
            ->where('source', 'predicted')
            ->where('is_read', false)
            ->first();

        if ($existing) {
            $oldUrgency = $existing->urgency;
            $existing->update([
                'urgency'            => $urgency,
                'recommendation'     => $recommendation,
                'predicted_due_date' => $predictedDueDate->toDateString(),
            ]);

            if ($this->shouldNotifyOnEscalation($oldUrgency, $urgency)) {
                $this->dispatchNotification($vehicle, $jobType, $urgency, $recommendation, true);
            }
        } else {
            MaintenanceAlert::create([
                'vehicle_id'         => $vehicle->id,
                'job_type_id'        => $jobType->id,
                'source'             => 'predicted',
                'alert_type'         => $jobType->name . ' Due',
                'urgency'            => $urgency,
                'recommendation'     => $recommendation,
                'predicted_due_date' => $predictedDueDate->toDateString(),
                'is_read'            => false,
            ]);

            if (in_array($urgency, ['medium', 'high'], true)) {
                $this->dispatchNotification($vehicle, $jobType, $urgency, $recommendation, false);
            }
        }

        return true;
    }

    // ----------------------------------------------------------------
    // Backtest
    // ----------------------------------------------------------------

    /**
     * Backtest: for every vehicle + interval job type with >= 2 recorded
     * services, predict the date of service N+1 from service N and compare
     * with when it actually happened.
     *
     * Error = predicted - actual (days). Positive = predicted too late.
     * Two models are scored on the same pairs:
     *   - current  : rule-based with the best available usage rate
     *   - baseline : original model (flat 40 km/day)
     * Observed-interval blending is switched OFF here so the same data is
     * not used to both fit and score.
     *
     * Caveat: the usage rate is the vehicle's CURRENT estimate, not what
     * it was at the time of service N (historic trip logs rarely exist).
     * State this in the report.
     */
    public function evaluate(): array {
        $types = JobType::where(function ($q) {
            $q->whereNotNull('interval_km')->orWhereNotNull('interval_months');
        })->get();

        $current = [];
        $base    = [];
        $byType  = [];

        foreach ($types as $type) {
            Vehicle::chunk(100, function ($vehicles) use ($type, &$current, &$base, &$byType) {
                foreach ($vehicles as $vehicle) {
                    $history = ServiceHistory::where('service_history.vehicle_id', $vehicle->id)
                        ->join('job_cards', 'job_cards.id', '=', 'service_history.job_card_id')
                        ->where('job_cards.job_type_id', $type->id)
                        ->orderBy('service_history.service_date')
                        ->get(['service_history.service_date']);

                    if ($history->count() < 2) {
                        continue;
                    }

                    $rate = $this->estimateDailyKm($vehicle);

                    for ($i = 0; $i < $history->count() - 1; $i++) {
                        $from   = Carbon::parse($history[$i]->service_date);
                        $actual = Carbon::parse($history[$i + 1]->service_date);

                        $p1 = $this->projectDueDate($from, $type, $rate, false);
                        $p0 = $this->projectDueDate($from, $type, (float) self::DEFAULT_DAILY_KM, false);
                        if (!$p1 || !$p0) {
                            continue;
                        }

                        $e1 = (int) round($actual->diffInDays($p1, false));
                        $e0 = (int) round($actual->diffInDays($p0, false));

                        $current[]                = $e1;
                        $base[]                   = $e0;
                        $byType[$type->name][]    = $e1;
                    }
                }
            });
        }

        $perType = [];
        foreach ($byType as $name => $errs) {
            $perType[$name] = $this->summarise($errs);
        }

        return [
            'pairs'    => count($current),
            'current'  => $this->summarise($current),
            'baseline' => $this->summarise($base),
            'by_type'  => $perType,
        ];
    }

    private function summarise(array $errors): array {
        $n = count($errors);
        if ($n === 0) {
            return ['n' => 0, 'mae' => null, 'bias' => null, 'within_7' => null, 'within_14' => null];
        }

        $abs = array_map('abs', $errors);

        return [
            'n'         => $n,
            'mae'       => round(array_sum($abs) / $n, 1),
            'bias'      => round(array_sum($errors) / $n, 1),
            'within_7'  => round(count(array_filter($abs, fn($e) => $e <= 7))  / $n * 100, 1),
            'within_14' => round(count(array_filter($abs, fn($e) => $e <= 14)) / $n * 100, 1),
        ];
    }

    // ----------------------------------------------------------------
    // Notifications
    // ----------------------------------------------------------------

    private function shouldNotifyOnEscalation(string $oldUrgency, string $newUrgency): bool {
        $ranks = ['low' => 1, 'medium' => 2, 'high' => 3];
        $oldRank = $ranks[$oldUrgency] ?? 0;
        $newRank = $ranks[$newUrgency] ?? 0;

        return $newRank > $oldRank && $newRank >= 2;
    }

    private function dispatchNotification(Vehicle $vehicle, JobType $jobType, string $urgency, string $recommendation, bool $isEscalation = false): void {
        if (!$vehicle->relationLoaded('owner')) {
            $vehicle->load('owner');
        }

        $owner = $vehicle->owner;
        if (!$owner) {
            return;
        }

        $title = $urgency === 'high' ? '⚠️ Urgent Maintenance Due' : '🔮 Predicted Service Due';
        $message = "{$vehicle->plate_number}: {$jobType->name} Due — {$recommendation}";

        if ($owner->role === 'corporate' && $owner->company_id) {
            $targetUserIds = User::where('company_id', $owner->company_id)
                ->where('status', 'active')
                ->pluck('id');
            $url = '/client/maintenance';
        } elseif ($owner->role === 'individual' && $owner->status === 'active') {
            $targetUserIds = collect([$owner->id]);
            $url = '/customer/maintenance';
        } else {
            return;
        }

        foreach ($targetUserIds as $userId) {
            $recentNotice = Notification::where('user_id', $userId)
                ->where('url', $url)
                ->where('message', 'like', "{$vehicle->plate_number}: {$jobType->name} Due%")
                ->where('created_at', '>=', now()->subDays(7))
                ->exists();

            if (!$recentNotice || $isEscalation) {
                Notification::send($userId, $title, $message, $url);
            }
        }
    }
}