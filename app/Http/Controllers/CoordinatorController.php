<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use Illuminate\Http\Request;

/**
 * Coordinator is a workshop-assistant / mechanic's-helper role — NOT the
 * read-only monitoring tier it was originally designed as. Per an explicit
 * product decision (superseding that earlier design), a coordinator can:
 *
 *   - Update job card stage, diagnosis, symptoms, parts, and labour
 *     charges — the SAME edit surface as a regular mechanic, including
 *     marking a job fully completed. Enforced via JobCard::canBeEditedBy(),
 *     which grants coordinators unconditional edit access, same tier as
 *     admins. These mutations are handled by JobCardController (see the
 *     coordinator route group in routes/web.php), not duplicated here, so
 *     mechanic-facing and coordinator-facing edits share one code path.
 *   - Manage spare parts inventory (add/edit/delete stock), granted via
 *     User::hasPermission('inventory.manage') returning true for the
 *     coordinator role specifically.
 *   - Log check-in notes on any job card — a coordinator-only feature on
 *     top of the shared mechanic actions, for leaving a status note that
 *     isn't tied to a specific stage change. This remains the one action
 *     unique to this controller.
 *
 * Scope stays intentionally UNSCOPED: a coordinator can view and act on
 * ALL active job cards, not just ones "assigned" to her. This is by
 * design — she exists to pick up whatever menial/admin work a busy
 * mechanic hasn't had time for, across the whole workshop, not to own a
 * personal queue. Access control is entirely role-based (middleware
 * `role:coordinator` + the canBeEditedBy/hasPermission checks above); no
 * additional per-job ownership check is layered on top.
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
        // Remember the last-used filter tab across visits, same pattern
        // as JobCardController/AppointmentController — "All Active"
        // always passes an explicit filter=all so it can update/clear
        // the remembered value too.
        if ($request->has('filter')) {
            session(['coordinator_filter' => $request->query('filter')]);
            $activeFilter = $request->query('filter');
        } else {
            $activeFilter = session('coordinator_filter', 'all');
        }
        if ($activeFilter === 'all') $activeFilter = null;

        $query = JobCard::with(['vehicle', 'staff', 'jobType'])
            ->where('current_stage', '!=', 'completed');

        if ($activeFilter === 'stale') {
            $query->where('updated_at', '<', now()->subHours(3));
        } elseif ($activeFilter === 'overdue') {
            $query->whereNotNull('estimated_completion')->where('estimated_completion', '<', now());
        }

        $jobs = $query->orderByRaw('estimated_completion IS NULL, estimated_completion ASC')->get();

        $totalActive  = JobCard::where('current_stage', '!=', 'completed')->count();
        $staleTotal   = JobCard::where('current_stage', '!=', 'completed')
            ->where('updated_at', '<', now()->subHours(3))->count();
        $overdueTotal = JobCard::where('current_stage', '!=', 'completed')
            ->whereNotNull('estimated_completion')->where('estimated_completion', '<', now())->count();

        return view('coordinator.job-cards', compact('jobs', 'totalActive', 'staleTotal', 'overdueTotal', 'activeFilter'));
    }

    /**
     * Coordinators can log a check-in note on any active job card — see
     * class-level doc-comment. No ownership check is added here beyond
     * `auth()->id()` being recorded as the check-in's author, which is
     * for attribution only, not authorization (JobCard::canBeEditedBy
     * already grants coordinators unconditional access on the routes
     * this controller doesn't directly handle).
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