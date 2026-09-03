<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Appointment;
use App\Models\SparePart;
use App\Models\ServiceHistory;
use App\Models\Vehicle;
use App\Models\User;

class ReportController extends Controller
{
    public function index() {
        // Job cards by stage
        $stages = ['received','diagnosing','waiting_parts','repairing','quality_check','completed'];
        $stageCounts = [];
        foreach ($stages as $stage) {
            $stageCounts[] = JobCard::where('current_stage', $stage)->count();
        }

        // Monthly revenue (last 6 months)
        $months  = [];
        $revenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date     = now()->subMonths($i);
            $months[] = $date->format('M Y');
            $revenue[] = ServiceHistory::whereYear('service_date', $date->year)
                ->whereMonth('service_date', $date->month)
                ->sum('cost');
        }

        // Full appointment lifecycle counts
        $apptPending   = Appointment::where('status', 'pending')->count();
        $apptConfirmed = Appointment::where('status', 'confirmed')->count();
        $apptCompleted = Appointment::where('status', 'completed')->count();
        $apptCancelled = Appointment::where('status', 'cancelled')->count();

        // Walk-in vs booked
        $walkinCount = Appointment::where('is_walkin', true)->count();
        $bookedCount = Appointment::where('is_walkin', false)->count();

        // Top stats
        $totalRevenue   = ServiceHistory::sum('cost');
        $completedJobs  = JobCard::where('current_stage', 'completed')->count();
        $totalVehicles  = Vehicle::count();
        $totalCustomers = User::whereIn('role', ['individual', 'corporate'])->count();

        // Low stock parts
        $lowStockParts = SparePart::whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')->get();

        // Recent completed jobs
        $recentCompleted = JobCard::with(['vehicle', 'staff', 'jobType'])
            ->where('current_stage', 'completed')
            ->latest()->take(8)->get();

        return view('reports.index', compact(
            'stageCounts', 'stages',
            'months', 'revenue',
            'apptPending', 'apptConfirmed', 'apptCompleted', 'apptCancelled',
            'walkinCount', 'bookedCount',
            'totalRevenue', 'completedJobs', 'totalVehicles', 'totalCustomers',
            'lowStockParts', 'recentCompleted'
        ));
    }

    /**
     * Streams a CSV covering monthly revenue and recent completed jobs.
     * No package needed — plain fputcsv against the output stream.
     */
    public function export() {
        $months  = [];
        $revenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date      = now()->subMonths($i);
            $months[]  = $date->format('M Y');
            $revenue[] = ServiceHistory::whereYear('service_date', $date->year)
                ->whereMonth('service_date', $date->month)
                ->sum('cost');
        }

        $recentCompleted = JobCard::with(['vehicle', 'staff', 'jobType'])
            ->where('current_stage', 'completed')
            ->latest()->take(100)->get();

        $lowStockParts = SparePart::whereColumn('stock', '<=', 'min_stock')->orderBy('stock')->get();

        $filename = 'workshop_report_' . now()->format('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($months, $revenue, $recentCompleted, $lowStockParts) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Monthly Revenue (Last 6 Months)']);
            fputcsv($out, ['Month', 'Revenue (RM)']);
            foreach ($months as $i => $m) {
                fputcsv($out, [$m, number_format($revenue[$i], 2, '.', '')]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Low Stock Parts']);
            fputcsv($out, ['Part Name', 'Brand', 'Stock', 'Min Stock']);
            foreach ($lowStockParts as $part) {
                fputcsv($out, [$part->name, $part->brand ?? '-', $part->stock, $part->min_stock]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Recent Completed Jobs']);
            fputcsv($out, ['Job ID', 'Vehicle', 'Job Type', 'Staff', 'Cost (RM)', 'Completed On']);
            foreach ($recentCompleted as $job) {
                fputcsv($out, [
                    $job->id,
                    $job->vehicle->plate_number ?? '-',
                    $job->jobType->name ?? '-',
                    $job->staff->name ?? '-',
                    number_format($job->total_cost, 2, '.', ''),
                    $job->updated_at->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }
}