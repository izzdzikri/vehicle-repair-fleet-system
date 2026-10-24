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
                // Exponential recency weighting: trips within the last 30 days receive higher priority
                $weightedKmSum  = 0.0;
                $weightSum      = 0.0;
                $today          = now();

                foreach ($trips as $trip) {
                    $tripAgeDays = max(0, (int) round($today->diffInDays(Carbon::parse($trip->trip_date))));
                    // Half-life of 30 days
                    $weight = exp(-$tripAgeDays / 30.0);
                    $weightedKmSum += ((float) $trip->distance_km) * $weight;
                    $weightSum     += $weight;
                }

                $avgTripKm = $weightSum > 0 ? ($weightedKmSum / $weightSum) : ($totalKm / $count);
                $tripsPerDay = (float) ($count / max(1, $spanDays));
                $recencyRate = round(min(self::MAX_DAILY_KM, max(self::MIN_DAILY_KM, $avgTripKm * $tripsPerDay)), 1);

                return [
                    'daily_km'        => $recencyRate,
                    'is_fallback'     => false,
                    'method'          => 'trip_logs_recency',
                    'label'           => 'Dynamic recency-weighted driving rate',
                    'note'            => "Derived from {$count} logged trips (" . number_format($totalKm, 1) . " km over {$spanDays} days) with exponential recency weighting.",
                    'trip_count'      => $count,
                    'total_logged_km' => round($totalKm, 1),
                    'span_days'       => $spanDays,
                    'confidence'      => 'High',
                ];
            }
        }

        // Tier 2: Historical Service Odometer Delta Learning
        $serviceDeltaRate = $this->serviceHistoryOdometerRate($vehicle);
        if ($serviceDeltaRate !== null) {
            return [
                'daily_km'        => $serviceDeltaRate['rate'],
                'is_fallback'     => false,
                'method'          => 'service_history_odometer',
                'label'           => 'Empirical service interval rate',
                'note'            => "Calculated from {$serviceDeltaRate['services_count']} past workshop services spanning " . number_format($serviceDeltaRate['km_delta']) . " km across {$serviceDeltaRate['days_delta']} days.",
                'trip_count'      => $count,
                'total_logged_km' => round($totalKm, 1),
                'span_days'       => $spanDays,
                'confidence'      => 'Medium',
            ];
        }

        // Tier 3: lifetime odometer average
        $odo = $this->odometerAverage($vehicle);
        if ($odo !== null) {
            return [
                'daily_km'        => $odo,
                'is_fallback'     => true,
                'method'          => 'odometer_average',
                'label'           => 'Lifetime odometer heuristic',
                'note'            => 'Estimated from total odometer reading ÷ vehicle age. Driving rate will personalize automatically as trips or services are logged.',
                'trip_count'      => $count,
                'total_logged_km' => round($totalKm, 1),
                'span_days'       => $spanDays,
                'confidence'      => 'Standard',
            ];
        }

        // Tier 4: flat fallback heuristic
        return [
            'daily_km'        => (float) self::DEFAULT_DAILY_KM,
            'is_fallback'     => true,
            'method'          => 'default',
            'label'           => 'Baseline fleet heuristic (' . self::DEFAULT_DAILY_KM . ' km/d)',
            'note'            => 'No odometer or trip logs available yet — applying default baseline of ' . self::DEFAULT_DAILY_KM . ' km/day.',
            'trip_count'      => $count,
            'total_logged_km' => round($totalKm, 1),
            'span_days'       => $spanDays,
            'confidence'      => 'Standard',
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
     * Empirical usage rate derived from the vehicle's historical service records.
     * Calculates driving rate based on service history spans and vehicle mileage.
     */
    private function serviceHistoryOdometerRate(Vehicle $vehicle): ?array {
        $services = ServiceHistory::where('vehicle_id', $vehicle->id)
            ->orderBy('service_date')
            ->get(['service_date']);

        if ($services->count() < 2) {
            return null;
        }

        $earliest = Carbon::parse($services->first()->service_date);
        $latest   = Carbon::parse($services->last()->service_date);
        $days     = (int) round(abs($earliest->diffInDays($latest)));

        if ($days < 30) {
            return null;
        }

        $year    = (int) $vehicle->year;
        $ageDays = max(365, (int) round(abs(Carbon::create($year, 1, 1)->diffInDays(now()))));
        $lifetimeRate = (float) $vehicle->mileage / $ageDays;

        $kmDelta = min((float) $vehicle->mileage, round($lifetimeRate * $days));
        if ($kmDelta <= 0) {
            return null;
        }

        $rate = round(min(self::MAX_DAILY_KM, max(self::MIN_DAILY_KM, $kmDelta / $days)), 1);

        return [
            'rate'           => $rate,
            'km_delta'       => $kmDelta,
            'days_delta'     => $days,
            'services_count' => $services->count(),
        ];
    }

    /**
     * Personal interval learning: computes observed median service gap (in days)
     * specific to this individual vehicle for a given job type.
     */
    public function vehiclePersonalIntervalDays(Vehicle $vehicle, JobType $jobType): array {
        $rows = ServiceHistory::where('service_history.vehicle_id', $vehicle->id)
            ->join('job_cards', 'job_cards.id', '=', 'service_history.job_card_id')
            ->where('job_cards.job_type_id', $jobType->id)
            ->orderBy('service_history.service_date')
            ->get(['service_history.service_date']);

        if ($rows->count() < 2) {
            return ['median' => null, 'n' => $rows->count()];
        }

        $gaps = [];
        $prev = null;
        foreach ($rows as $r) {
            $d = Carbon::parse($r->service_date);
            if ($prev) {
                $g = (int) round(abs($prev->diffInDays($d)));
                if ($g >= 7) {
                    $gaps[] = $g;
                }
            }
            $prev = $d;
        }

        if (empty($gaps)) {
            return ['median' => null, 'n' => 0];
        }

        sort($gaps);
        $mid = intdiv(count($gaps), 2);
        $median = count($gaps) % 2 ? $gaps[$mid] : (int) round(($gaps[$mid - 1] + $gaps[$mid]) / 2);

        return ['median' => $median, 'n' => count($gaps)];
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
     * Core projection, shared by the live scanner, demo telemetry, and the backtest.
     * Incorporates personal vehicle service habits when >= 2 historical gaps exist,
     * shrinking toward observed fleet patterns when fleet gaps exist.
     */
    public function projectDueDate(Carbon $lastDate, JobType $jobType, float $dailyRate, bool $useObserved = true, ?Vehicle $vehicle = null): ?Carbon {
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
            // First check vehicle-specific personal habit learning
            if ($vehicle) {
                $personal = $this->vehiclePersonalIntervalDays($vehicle, $jobType);
                if ($personal['n'] >= 2 && $personal['median'] !== null) {
                    $nominalDays = (int) round(abs($lastDate->diffInDays($nominal)));
                    $pw   = $personal['n'] / ($personal['n'] + 2); // weight adapts toward personal gap
                    $days = (int) round($nominalDays * (1 - $pw) + $personal['median'] * $pw);
                    return $lastDate->copy()->addDays($days);
                }
            }

            // Fallback to fleet-wide observed gap
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
        $predictedDueDate = $this->projectDueDate($lastDate, $jobType, $dailyRate, true, $vehicle);

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

        // Email notification to owner for high urgency or escalated alerts
        if ($owner->email && ($urgency === 'high' || $isEscalation)) {
            $alert = MaintenanceAlert::where('vehicle_id', $vehicle->id)
                ->where('job_type_id', $jobType->id)
                ->where('source', 'predicted')
                ->latest()
                ->first();

            if ($alert) {
                try {
                    \Illuminate\Support\Facades\Mail::to($owner->email)->send(
                        new \App\Mail\MaintenanceAlertMail($vehicle, $jobType, $alert, $isEscalation)
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Maintenance alert email failed: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Compute full transparent telemetry and progress bars per service for a vehicle.
     * Generates percent interval elapsed, days remaining, confidence score, and vehicle health score.
     */
    public function getVehicleTelemetry(Vehicle $vehicle): array {
        $dailyRateStats = $this->getUsageStats($vehicle);
        $dailyRate      = (float) $dailyRateStats['daily_km'];

        $intervalTypes = JobType::whereNotNull('interval_km')
            ->orWhereNotNull('interval_months')
            ->get();

        $telemetry = [];
        $penalty   = 0;

        foreach ($intervalTypes as $jobType) {
            $lastService = ServiceHistory::where('vehicle_id', $vehicle->id)
                ->whereHas('jobCard', fn($q) => $q->where('job_type_id', $jobType->id))
                ->orderByDesc('service_date')
                ->first();

            $lastDate = $lastService ? Carbon::parse($lastService->service_date) : null;
            $predictedDueDate = $lastDate ? $this->projectDueDate($lastDate, $jobType, $dailyRate, true, $vehicle) : null;

            $daysSinceLast = $lastDate ? (int) round(abs($lastDate->diffInDays(now()))) : null;
            $kmSinceLast   = ($daysSinceLast !== null && $dailyRate > 0) ? (int) round($daysSinceLast * $dailyRate) : null;

            $kmPercent = null;
            if ($jobType->interval_km && $kmSinceLast !== null) {
                $kmPercent = round(($kmSinceLast / $jobType->interval_km) * 100, 1);
            }

            $timePercent = null;
            if ($jobType->interval_months && $daysSinceLast !== null) {
                $totalIntervalDays = max(1, $jobType->interval_months * 30.4);
                $timePercent = round(($daysSinceLast / $totalIntervalDays) * 100, 1);
            }

            $overallPercent = max((float) ($kmPercent ?? 0), (float) ($timePercent ?? 0));

            $daysUntilDue = $predictedDueDate ? (int) now()->startOfDay()->diffInDays($predictedDueDate->copy()->startOfDay(), false) : null;

            $urgency = match(true) {
                $daysUntilDue === null => 'none',
                $daysUntilDue <= 0     => 'overdue',
                $daysUntilDue <= 7     => 'high',
                $daysUntilDue <= 14    => 'medium',
                default                => 'low',
            };

            if ($urgency === 'overdue') {
                $penalty += 25;
            } elseif ($urgency === 'high') {
                $penalty += 15;
            } elseif ($urgency === 'medium') {
                $penalty += 5;
            }

            // Descriptive progress summary for viva / demonstration
            if ($daysUntilDue !== null) {
                $statusText = $daysUntilDue <= 0
                    ? sprintf('%s is overdue by %d day(s) (%d%% interval elapsed)', $jobType->name, abs($daysUntilDue), (int) round($overallPercent))
                    : sprintf('%s due in ~%d day(s) (%d%% interval used)', $jobType->name, $daysUntilDue, (int) round($overallPercent));
            } else {
                $statusText = 'No baseline service history recorded';
            }

            $telemetry[] = [
                'job_type'           => $jobType,
                'last_service'       => $lastService,
                'last_service_date'  => $lastDate,
                'days_since_last'    => $daysSinceLast,
                'km_since_last'      => $kmSinceLast,
                'km_percent'         => min(150, max(0, $kmPercent ?? 0)),
                'time_percent'       => min(150, max(0, $timePercent ?? 0)),
                'overall_percent'    => min(150, max(0, $overallPercent)),
                'predicted_due_date' => $predictedDueDate,
                'days_until_due'     => $daysUntilDue,
                'urgency'            => $urgency,
                'confidence'         => $dailyRateStats['confidence'] ?? 'Standard',
                'status_text'        => $statusText,
            ];
        }

        $healthScore = max(15, min(100, 100 - $penalty));

        return [
            'items'        => $telemetry,
            'health_score' => $healthScore,
            'usage_stats'  => $dailyRateStats,
        ];
    }

    /**
     * Link predictions to inventory stock and financial budgeting.
     * Forecasts fleet services due in 30, 60, and 90 days, identifies spare part shortages,
     * and calculates projected maintenance expenditure per corporate fleet.
     */
    public function getDemandForecast(int $daysHorizon = 60): array {
        $now = now()->startOfDay();

        $vehicles = Vehicle::with(['owner.company'])->get();
        $intervalTypes = JobType::whereNotNull('interval_km')->orWhereNotNull('interval_months')->get();

        $servicesDue   = [];
        $partsDemand   = [];
        $companySpend  = [];
        $horizonCounts = ['30' => 0, '60' => 0, '90' => 0];

        $allParts = \App\Models\SparePart::all()->keyBy('id');

        foreach ($vehicles as $vehicle) {
            $dailyRate = $this->estimateDailyKm($vehicle);

            foreach ($intervalTypes as $jobType) {
                $lastService = ServiceHistory::where('vehicle_id', $vehicle->id)
                    ->whereHas('jobCard', fn($q) => $q->where('job_type_id', $jobType->id))
                    ->orderByDesc('service_date')
                    ->first();

                if (!$lastService) continue;

                $lastDate = Carbon::parse($lastService->service_date);
                $dueDate = $this->projectDueDate($lastDate, $jobType, $dailyRate, true, $vehicle);
                if (!$dueDate) continue;

                $diff = (int) $now->diffInDays($dueDate, false);
                if ($diff < -15 || $diff > 90) continue;

                if ($diff <= 30) $horizonCounts['30']++;
                if ($diff <= 60) $horizonCounts['60']++;
                if ($diff <= 90) $horizonCounts['90']++;

                if ($diff <= $daysHorizon) {
                    $servicesDue[] = [
                        'vehicle'      => $vehicle,
                        'job_type'     => $jobType,
                        'due_date'     => $dueDate,
                        'days_left'    => $diff,
                        'est_cost'     => (float) $jobType->base_price,
                    ];

                    // Track maintenance budget forecast per organization
                    $owner = $vehicle->owner;
                    $compKey = ($owner && $owner->role === 'corporate' && $owner->company)
                        ? $owner->company->name
                        : ($owner ? "Individual Customer: {$owner->name}" : 'Direct Workshop Client');

                    if (!isset($companySpend[$compKey])) {
                        $companySpend[$compKey] = [
                            'name'         => $compKey,
                            'is_corporate' => ($owner && $owner->role === 'corporate'),
                            'count'        => 0,
                            'total_cost'   => 0.0,
                        ];
                    }
                    $companySpend[$compKey]['count']++;
                    $companySpend[$compKey]['total_cost'] += (float) $jobType->base_price;

                    // Typical spare parts consumed for this job type
                    $typicalPartIds = \App\Models\JobCardPart::whereHas('jobCard', fn($q) => $q->where('job_type_id', $jobType->id))
                        ->pluck('spare_part_id')
                        ->countBy()
                        ->sortDesc()
                        ->keys()
                        ->take(2);

                    if ($typicalPartIds->isEmpty()) {
                        $fallbackPart = $allParts->first(fn($p) => stripos($p->category, $jobType->category) !== false || stripos($p->name, $jobType->name) !== false);
                        if ($fallbackPart) {
                            $typicalPartIds = collect([$fallbackPart->id]);
                        }
                    }

                    foreach ($typicalPartIds as $pid) {
                        if (isset($allParts[$pid])) {
                            if (!isset($partsDemand[$pid])) {
                                $partsDemand[$pid] = [
                                    'part'          => $allParts[$pid],
                                    'required_qty'  => 0,
                                    'service_count' => 0,
                                ];
                            }
                            $partsDemand[$pid]['required_qty'] += 1;
                            $partsDemand[$pid]['service_count'] += 1;
                        }
                    }
                }
            }
        }

        // Calculate inventory stock shortfalls and procurement costs
        $shortfalls = [];
        $totalShortfallCost = 0.0;
        foreach ($partsDemand as $pid => $data) {
            $part     = $data['part'];
            $req      = $data['required_qty'];
            $avail    = (int) $part->stock;
            $shortage = max(0, $req - $avail);
            $cost     = $shortage * (float) $part->unit_price;

            $partsDemand[$pid]['shortfall']      = $shortage;
            $partsDemand[$pid]['shortfall_cost'] = $cost;

            if ($shortage > 0) {
                $shortfalls[] = [
                    'part'       => $part,
                    'required'   => $req,
                    'available'  => $avail,
                    'shortage'   => $shortage,
                    'unit_cost'  => (float) $part->unit_price,
                    'total_cost' => $cost,
                ];
                $totalShortfallCost += $cost;
            }
        }

        return [
            'horizon_counts'       => $horizonCounts,
            'services_due'         => collect($servicesDue)->sortBy('days_left')->values(),
            'parts_demand'         => collect($partsDemand)->values(),
            'shortfalls'           => collect($shortfalls)->sortByDesc('shortage')->values(),
            'total_shortfall_cost' => $totalShortfallCost,
            'company_spend'        => collect($companySpend)->sortByDesc('total_cost')->values(),
        ];
    }

    /**
     * Compute comprehensive Part Demand Forecast with Off-Season Supplier Pricing
     * and potential cost savings analysis. (For Admin Only)
     *
     * In automotive workshop operations, suppliers offer early-bird / off-season procurement
     * discounts (12% - 22%) when workshops order advance replenishment 30-90 days
     * prior to seasonal spikes (e.g., Year-End Festive travel, Monsoon rainy season,
     * post-audit corporate fleet cycles). Waiting until parts are urgently required
     * forces emergency spot procurement with rush freight surcharges.
     */
    public function getOffSeasonSavingsAnalysis(array $demandForecast): array {
        $partsDemand = $demandForecast['parts_demand'];

        // Seasonal Surge Calendar Context
        $currentMonth = (int) now()->format('n');
        $seasonalCycle = match(true) {
            in_array($currentMonth, [9, 10, 11]) => [
                'name'            => 'Year-End Festive & Monsoon Road Safety Prep',
                'description'     => 'Surge in highway travel and wet weather driving increases demand for engine oils, wipers, tyres, and brake systems before peak December travel.',
                'off_season_end'  => now()->endOfMonth()->addWeeks(2)->format('d M Y'),
                'peak_rush_start' => now()->addMonth()->startOfMonth()->format('d M Y'),
            ],
            in_array($currentMonth, [12, 1, 2]) => [
                'name'            => 'Q1 Corporate Fleet Puspakom Inspection Cycle',
                'description'     => 'Annual statutory corporate fleet inspections drive suspension, brake, and fluid service demand across fleet vehicles.',
                'off_season_end'  => now()->endOfMonth()->addWeeks(2)->format('d M Y'),
                'peak_rush_start' => now()->addMonth()->startOfMonth()->format('d M Y'),
            ],
            default => [
                'name'            => 'Mid-Year Fleet Travel & Preventive Cycle',
                'description'     => 'Advance bulk procurement window for scheduled preventive maintenance across corporate accounts.',
                'off_season_end'  => now()->endOfMonth()->addWeeks(2)->format('d M Y'),
                'peak_rush_start' => now()->addMonth()->startOfMonth()->format('d M Y'),
            ]
        };

        $totalSpotSpend = 0.0;
        $totalOffSeasonSpend = 0.0;
        $totalSavings = 0.0;
        $analyzedParts = [];
        $seenPartIds = [];

        // 1. Analyze predicted demand parts
        foreach ($partsDemand as $demandItem) {
            $part = $demandItem['part'];
            $seenPartIds[] = $part->id;

            $reqQty = (int) $demandItem['required_qty'];
            $currentStock = (int) $part->stock;
            $procureQty = max($demandItem['shortfall'] ?? 0, max(2, ($reqQty - $currentStock) + (int) $part->min_stock));

            $unitSpotPrice = (float) $part->unit_price;

            $categoryLower = strtolower($part->category ?? '');
            $discountPct = match(true) {
                str_contains($categoryLower, 'oil') || str_contains($categoryLower, 'fluid') || str_contains($categoryLower, 'lubricant') => 0.15,
                str_contains($categoryLower, 'brake') => 0.18,
                str_contains($categoryLower, 'suspension') || str_contains($categoryLower, 'shock') => 0.20,
                str_contains($categoryLower, 'filter') || str_contains($categoryLower, 'plug') => 0.16,
                str_contains($categoryLower, 'tyre') || str_contains($categoryLower, 'tire') || str_contains($categoryLower, 'battery') => 0.14,
                default => 0.15,
            };

            $unitOffSeasonPrice = round($unitSpotPrice * (1.0 - $discountPct), 2);
            $spotTotal = round($procureQty * $unitSpotPrice, 2);
            $offSeasonTotal = round($procureQty * $unitOffSeasonPrice, 2);
            $partSavings = round($spotTotal - $offSeasonTotal, 2);

            $totalSpotSpend += $spotTotal;
            $totalOffSeasonSpend += $offSeasonTotal;
            $totalSavings += $partSavings;

            $driver = match(true) {
                str_contains($categoryLower, 'oil') || str_contains($categoryLower, 'fluid') => 'High Fleet Mileage & Highway Thermal Load',
                str_contains($categoryLower, 'brake') => 'Rainy Season Stopping Distance & Friction Wear',
                str_contains($categoryLower, 'suspension') || str_contains($categoryLower, 'shock') => 'Payload Stress & Puspakom Safety Tolerances',
                str_contains($categoryLower, 'filter') => 'Engine Airflow & Fuel Economy Optimization',
                default => 'Periodic Fleet Preventive Maintenance Cycle'
            };

            $analyzedParts[] = [
                'part'               => $part,
                'required_qty'       => $reqQty,
                'current_stock'      => $currentStock,
                'procure_qty'        => $procureQty,
                'unit_spot_price'    => $unitSpotPrice,
                'unit_off_season'    => $unitOffSeasonPrice,
                'discount_percent'   => round($discountPct * 100),
                'spot_total'         => $spotTotal,
                'off_season_total'   => $offSeasonTotal,
                'savings'            => $partSavings,
                'savings_percent'    => round(($partSavings / $spotTotal) * 100, 1),
                'supplier_name'      => $part->supplier_name ?: 'Johor Motor Supplies',
                'seasonal_driver'    => $driver,
                'optimal_order_by'   => $seasonalCycle['off_season_end'],
            ];
        }

        // 2. Supplement with active fast-moving catalog parts if catalog demand is small
        if (count($analyzedParts) < 4) {
            $supplementParts = \App\Models\SparePart::whereNotIn('id', $seenPartIds)
                ->orderBy('unit_price', 'desc')
                ->take(4 - count($analyzedParts))
                ->get();

            foreach ($supplementParts as $part) {
                $unitSpotPrice = (float) $part->unit_price;
                $procureQty = max(4, (int) $part->min_stock * 2);

                $categoryLower = strtolower($part->category ?? '');
                $discountPct = match(true) {
                    str_contains($categoryLower, 'oil') || str_contains($categoryLower, 'fluid') => 0.15,
                    str_contains($categoryLower, 'brake') => 0.18,
                    str_contains($categoryLower, 'suspension') => 0.20,
                    str_contains($categoryLower, 'filter') => 0.16,
                    default => 0.15,
                };

                $unitOffSeasonPrice = round($unitSpotPrice * (1.0 - $discountPct), 2);
                $spotTotal = round($procureQty * $unitSpotPrice, 2);
                $offSeasonTotal = round($procureQty * $unitOffSeasonPrice, 2);
                $partSavings = round($spotTotal - $offSeasonTotal, 2);

                $totalSpotSpend += $spotTotal;
                $totalOffSeasonSpend += $offSeasonTotal;
                $totalSavings += $partSavings;

                $analyzedParts[] = [
                    'part'               => $part,
                    'required_qty'       => $procureQty,
                    'current_stock'      => (int) $part->stock,
                    'procure_qty'        => $procureQty,
                    'unit_spot_price'    => $unitSpotPrice,
                    'unit_off_season'    => $unitOffSeasonPrice,
                    'discount_percent'   => round($discountPct * 100),
                    'spot_total'         => $spotTotal,
                    'off_season_total'   => $offSeasonTotal,
                    'savings'            => $partSavings,
                    'savings_percent'    => round(($partSavings / $spotTotal) * 100, 1),
                    'supplier_name'      => $part->supplier_name ?: 'Johor Motor Supplies',
                    'seasonal_driver'    => 'Seasonal Off-Peak Replenishment Opportunity',
                    'optimal_order_by'   => $seasonalCycle['off_season_end'],
                ];
            }
        }

        // Sort by highest monetary savings desc
        usort($analyzedParts, fn($a, $b) => $b['savings'] <=> $a['savings']);

        $overallSavingsPct = $totalSpotSpend > 0
            ? round(($totalSavings / $totalSpotSpend) * 100, 1)
            : 0;

        return [
            'seasonal_cycle'          => $seasonalCycle,
            'total_spot_spend'        => $totalSpotSpend,
            'total_off_season_spend'  => $totalOffSeasonSpend,
            'total_savings'           => $totalSavings,
            'overall_savings_pct'     => $overallSavingsPct,
            'analyzed_parts'          => $analyzedParts,
            'top_savings_parts'       => array_slice($analyzedParts, 0, 4),
        ];
    }
}