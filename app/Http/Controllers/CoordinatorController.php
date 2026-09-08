<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use Illuminate\Http\Request;

/**
 * Coordinator is a read + comment-only tier, entirely separate from the
 * staff `job_cards.manage_all` permission. It does not grant edit rights
 * over stage/parts/labour/diagnosis — those actions aren't even exposed
 * in the coordinator views. The only write action a coordinator has is
 * logging a check-in note.
 *
 * DESIGN DECISION (unscoped by design): a coordinator can view and check
 * in on ALL active job cards, with no per-coordinator ownership filter.
 * This is intentional, not an oversight — coordinators monitor workshop-
 * wide progress, not a personal queue, so there is no "their" job card
 * to scope to. Access control here is entirely role-based
 * (middleware `role:coordinator`); no additional ownership check is
 * needed on top of it. If a future requirement introduces coordinators
 * assigned to specific areas/staff, that would need an explicit
 * ownership column (e.g. `coordinator_id` or an area/zone assignment)
 * and a check mirroring the staff `job_cards.manage_all` pattern —
 * do not assume this file already does that.
 */
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

    /**
     * Coordinators can view any job card's detail — see class-level
     * doc-comment for why this is intentionally unscoped.
     */
    public function showJobCard(JobCard $jobCard) {
        $jobCard->load([
            'vehicle', 'staff', 'jobType', 'appointment.user',
            'parts.sparePart', 'labourCharges', 'checkins.coordinator',
        ]);

        return view('coordinator.job-card-show', compact('jobCard'));
    }

    /**
     * Coordinators can log a check-in on any active job card — see
     * class-level doc-comment. No ownership check is added here beyond
     * `auth()->id()` being recorded as the check-in's author, which is
     * for attribution only, not authorization.
     */
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