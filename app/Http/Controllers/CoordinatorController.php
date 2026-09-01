<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use Illuminate\Http\Request;

class CoordinatorController extends Controller
{
    public function dashboard() {
        $activeJobs = JobCard::with(['vehicle', 'staff', 'jobType'])
            ->where('current_stage', '!=', 'completed')
            ->orderByRaw('estimated_completion IS NULL, estimated_completion ASC')
            ->get();

        $staleCount   = $activeJobs->filter(fn($j) => $j->is_stale)->count();
        $overdueCount = $activeJobs->filter(fn($j) => $j->estimated_completion && $j->estimated_completion->isPast())->count();

        return view('coordinator.dashboard', [
            'urgentJobs'   => $activeJobs->take(8),
            'totalActive'  => $activeJobs->count(),
            'staleCount'   => $staleCount,
            'overdueCount' => $overdueCount,
        ]);
    }

    public function jobCards(Request $request) {
        $query = JobCard::with(['vehicle', 'staff', 'jobType'])
            ->where('current_stage', '!=', 'completed');

        if ($request->filter === 'stale') {
            $query->where('updated_at', '<', now()->subHours(3));
        } elseif ($request->filter === 'overdue') {
            $query->whereNotNull('estimated_completion')->where('estimated_completion', '<', now());
        }

        $jobs = $query->orderByRaw('estimated_completion IS NULL, estimated_completion ASC')->get();

        $totalActive  = JobCard::where('current_stage', '!=', 'completed')->count();
        $staleTotal   = JobCard::where('current_stage', '!=', 'completed')
            ->where('updated_at', '<', now()->subHours(3))->count();
        $overdueTotal = JobCard::where('current_stage', '!=', 'completed')
            ->whereNotNull('estimated_completion')->where('estimated_completion', '<', now())->count();

        return view('coordinator.job-cards', compact('jobs', 'totalActive', 'staleTotal', 'overdueTotal'));
    }

    public function showJobCard(JobCard $jobCard) {
        $jobCard->load([
            'vehicle', 'staff', 'jobType', 'appointment.user',
            'parts.sparePart', 'labourCharges', 'checkins.coordinator',
        ]);

        return view('coordinator.job-card-show', compact('jobCard'));
    }

    public function storeCheckin(Request $request, JobCard $jobCard) {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $jobCard->checkins()->create([
            'coordinator_id' => auth()->id(),
            'note'           => $request->note,
        ]);

        return back()->with('success', 'Check-in logged.');
    }
}