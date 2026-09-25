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
 * For each vehicle and each interval-tracked JobType, it projects a
 * next-due date from the vehicle's last matching ServiceHistory entry
 * plus an estimated daily-km usage rate, and creates/refreshes a
 * MaintenanceAlert when that projection falls inside the alert
 * horizon. This is documented plainly because the system has no
 * telematics/odometer feed to make a real ML model meaningful — the
 * "prediction" is a transparent projection an admin or the writeup's
 * limitations section can explain in one sentence.
 */
class MaintenancePredictionService
{
    /**
     * Fallback usage rate when a vehicle has fewer than 2 trip logs to
     * compute a real rate from. Roughly a typical daily commute
     * distance — a deliberately simple, documented default rather than
     * a hidden magic number.
     */
    private const DEFAULT_DAILY_KM = 40;

    /** Days before a predicted due date counts as "due soon" (medium). */
    private const DUE_SOON_DAYS = 14;

    /** Outer window beyond which we don't alert yet at all. */
    private const HORIZON_DAYS = 45;

    /**
     * Run the scan across every vehicle and every interval-tracked job
     * type. Safe to re-run repeatedly — it refreshes the existing
     * unread predicted alert for a vehicle+job-type pair instead of
     * creating duplicates.
     *
     * @return int number of alerts created or refreshed
     */
    public function runAll(): int {
        $intervalTypes = JobType::whereNotNull('interval_km')
            ->orWhereNotNull('interval_months')
            ->get();

        if ($intervalTypes->isEmpty()) {
            return 0;
        }

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

    /**
     * Estimate a vehicle's average daily mileage from its most recent
     * trip logs (there is no odometer/telematics feed, so trip logs —
     * where present — are the only ground truth for usage rate).
     */
    public function estimateDailyKm(Vehicle $vehicle): float {
        return (float) $this->getUsageStats($vehicle)['daily_km'];
    }

    /**
     * Get detailed usage statistics for a vehicle based on its trip logs,
     * including whether the calculation is using the 40 km/day heuristic fallback.
     */
    public function getUsageStats(Vehicle $vehicle): array {
        $trips = TripLog::where('vehicle_id', $vehicle->id)
            ->orderByDesc('trip_date')
            ->limit(20)
            ->get();

        $count = $trips->count();
        if ($count < 2) {
            return [
                'daily_km'        => (float) self::DEFAULT_DAILY_KM,
                'is_fallback'     => true,
                'trip_count'      => $count,
                'total_logged_km' => (float) $trips->sum('distance_km'),
                'span_days'       => 0,
            ];
        }

        $totalKm  = (float) $trips->sum('distance_km');
        $earliest = Carbon::parse($trips->min('trip_date'));
        $latest   = Carbon::parse($trips->max('trip_date'));
        $spanDays = (int) $earliest->diffInDays($latest);

        if ($spanDays < 1 || $totalKm <= 0) {
            return [
                'daily_km'        => (float) self::DEFAULT_DAILY_KM,
                'is_fallback'     => true,
                'trip_count'      => $count,
                'total_logged_km' => $totalKm,
                'span_days'       => $spanDays,
            ];
        }

        return [
            'daily_km'        => round($totalKm / $spanDays, 1),
            'is_fallback'     => false,
            'trip_count'      => $count,
            'total_logged_km' => round($totalKm, 1),
            'span_days'       => $spanDays,
        ];
    }

    /**
     * Project the next due date for one vehicle + job type, and
     * create/refresh a predicted MaintenanceAlert if it falls within
     * the alerting horizon.
     *
     * @return bool true if an alert was created or updated
     */
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

        $lastDate = Carbon::parse($lastService->service_date);

        $dueByTime = $jobType->interval_months
            ? $lastDate->copy()->addMonths($jobType->interval_months)
            : null;

        $dueByKm = ($jobType->interval_km && $dailyRate > 0)
            ? $lastDate->copy()->addDays((int) ceil($jobType->interval_km / $dailyRate))
            : null;

        $candidates = array_filter([$dueByTime, $dueByKm]);
        if (empty($candidates)) {
            return false;
        }

        /** @var Carbon $predictedDueDate */
        $predictedDueDate = min($candidates);
        $daysUntilDue = (int) now()->startOfDay()->diffInDays($predictedDueDate->copy()->startOfDay(), false);

        if ($daysUntilDue > self::HORIZON_DAYS) {
            return false;
        }

        $urgency = match(true) {
            $daysUntilDue <= 0                  => 'high',
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

    /**
     * Determines whether an urgency change represents an escalation.
     */
    private function shouldNotifyOnEscalation(string $oldUrgency, string $newUrgency): bool {
        $ranks = ['low' => 1, 'medium' => 2, 'high' => 3];
        $oldRank = $ranks[$oldUrgency] ?? 0;
        $newRank = $ranks[$newUrgency] ?? 0;

        return $newRank > $oldRank && $newRank >= 2;
    }

    /**
     * Dispatch in-app notification to the vehicle owner (individual) or all active
     * company PICs (corporate fleet), guarding against duplicate notifications within 7 days.
     */
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