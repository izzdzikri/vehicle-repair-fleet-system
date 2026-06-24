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
}
