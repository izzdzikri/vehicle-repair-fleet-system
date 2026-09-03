<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Appointment;
use App\Models\User;
use App\Models\SparePart;
use App\Models\JobCardPart;
use App\Models\ServiceHistory;
use App\Models\JobType;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LabourCharge;

class JobCardController extends Controller
{
    public function index(Request $request) {
        $search  = trim((string) $request->input('search'));
        $perPage = 15;

        $applySearch = function ($query) use ($search) {
            if ($search === '') return;
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', fn($v) => $v->where('plate_number', 'like', "%{$search}%"))
                  ->orWhereHas('staff', fn($s) => $s->where('name', 'like', "%{$search}%"));
            });
        };

        // Stage tab counts, independent of pagination.
        $countScope = JobCard::query();
        $applySearch($countScope);
        $stageCounts = ['all' => (clone $countScope)->count()];
        foreach (['received', 'diagnosing', 'waiting_parts', 'repairing', 'quality_check', 'completed'] as $stage) {
            $stageCounts[$stage] = (clone $countScope)->where('current_stage', $stage)->count();
        }

        $query = JobCard::with(['vehicle', 'staff', 'appointment', 'jobType']);
        if ($request->stage) {
            $query->where('current_stage', $request->stage);
        }
        $applySearch($query);

        $jobs = $query->latest()->paginate($perPage)->withQueryString();

        return view('job-cards.index', compact('jobs', 'search', 'stageCounts'));
    }

    public function create() {
    $appointments = Appointment::where('status', 'confirmed')
        ->whereDoesntHave('jobCard')
        ->with(['vehicle', 'user'])
        ->get();

    $staff    = \App\Models\User::where('role', 'staff')
                    ->where('status', 'active')
                    ->get();
    $jobTypes = \App\Models\JobType::orderBy('category')->get();

    return view('job-cards.create', compact('appointments', 'staff', 'jobTypes'));
    }

    public function store(Request $request) {
        $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'staff_id'       => 'required|exists:users,id',
        ]);

        // Walk-in job card: appointment_id may be null if created directly
        // from the walk-in appointment (which auto-confirms), but we still
        // require a vehicle_id in that case.
        $vehicleId = null;
        $appointmentId = $request->appointment_id;

        if ($appointmentId) {
            $appointment = Appointment::findOrFail($appointmentId);
            $vehicleId   = $appointment->vehicle_id;
        } else {
            // Fallback: should not happen via normal flow, but guard anyway
            return back()->with('error', 'Please select an appointment.');
        }

        $estimatedCompletion = null;
        if ($request->job_type_id) {
            $jobType = JobType::find($request->job_type_id);
            if ($jobType) {
                $estimatedCompletion = now()->addMinutes($jobType->estimated_minutes);
            }
        }

        $jobCard = JobCard::create([
            'appointment_id'       => $appointmentId,
            'vehicle_id'           => $vehicleId,
            'staff_id'             => $request->staff_id,
            'job_type_id'          => $request->job_type_id,
            'current_stage'        => 'received',
            'diagnosis'            => $request->diagnosis,
            'estimated_completion' => $estimatedCompletion,
        ]);

        $vehicle = Vehicle::find($vehicleId);
        Notification::send(
            $request->staff_id,
            'New Job Assigned',
            "You've been assigned to ".($vehicle->plate_number ?? 'a vehicle')." (".($vehicle->brand ?? '').' '.($vehicle->model ?? '').").",
            '/staff/job-cards/' . $jobCard->id
        );

        return redirect('/admin/job-cards')->with('success', 'Job card created and assigned.');
    }

    public function show(JobCard $jobCard) {
    $jobCard->load(['vehicle', 'staff', 'parts.sparePart', 'appointment.user', 'jobType', 'labourCharges']);
    $spareParts = SparePart::where('stock', '>', 0)->orderBy('category')->get();
    $jobTypes   = \App\Models\JobType::orderBy('category')->orderBy('name')->get();
    return view('job-cards.show', compact('jobCard', 'spareParts', 'jobTypes'));
    }

    // ----------------------------------------------------------------
    // EDF (Earliest Deadline First) schedule board
    // ----------------------------------------------------------------

    public function schedule() {
        $jobs = JobCard::with(['vehicle', 'staff', 'jobType', 'appointment'])
            ->where('current_stage', '!=', 'completed')
            ->orderByRaw('estimated_completion IS NULL, estimated_completion ASC')
            ->get();

        $grouped = $jobs->groupBy(fn($j) => $j->staff->name ?? 'Unassigned');

        return view('job-cards.schedule', compact('jobs', 'grouped'));
    }

    // ----------------------------------------------------------------
    // Kanban board — drag-and-drop stage view
    // ----------------------------------------------------------------

    public function board() {
        $jobs = JobCard::with(['vehicle', 'staff', 'jobType'])
            ->orderByRaw('estimated_completion IS NULL, estimated_completion ASC')
            ->get();

        $stages  = ['received', 'diagnosing', 'waiting_parts', 'repairing', 'quality_check', 'completed'];
        $grouped = collect($stages)->mapWithKeys(fn($s) => [$s => $jobs->where('current_stage', $s)->values()]);

        return view('job-cards.board', compact('grouped', 'stages'));
    }

    public function updateStage(Request $request, JobCard $jobCard) {
        $request->validate([
            'current_stage' => 'required|in:received,diagnosing,waiting_parts,repairing,quality_check,completed',
        ]);

        $jobCard->update([
            'current_stage' => $request->current_stage,
            // Clear completed_at whenever a job is reopened, so a stale
            // timestamp doesn't corrupt performance/turnaround metrics.
            'completed_at'  => $request->current_stage === 'completed' ? now() : null,
        ]);

        if ($request->current_stage === 'completed') {
            ActivityLog::record(
                'job_card.completed',
                "Completed job card #{$jobCard->id} for ".($jobCard->vehicle->plate_number ?? '—'),
                $jobCard
            );

            // Mark appointment as completed too
            if ($jobCard->appointment) {
                $jobCard->appointment->update(['status' => 'completed']);
            }

            ServiceHistory::firstOrCreate(
                ['job_card_id' => $jobCard->id],
                [
                    'vehicle_id'   => $jobCard->vehicle_id,
                    'service_date' => now()->toDateString(),
                    'description'  => $jobCard->jobType->name
                                   ?? $jobCard->diagnosis
                                   ?? $jobCard->appointment->service_type
                                   ?? 'Service completed',
                    'cost'         => $jobCard->total_cost,
                ]
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Stage updated.']);
        }

        return back()->with('success', 'Stage updated.');
    }

    /**
     * Save technician-filled symptom checklist + notes.
     */
    public function updateSymptoms(Request $request, JobCard $jobCard) {
        $request->validate([
            'symptoms'         => 'nullable|array',
            'symptoms.*'       => 'string|max:100',
            'technician_notes' => 'nullable|string|max:2000',
        ]);

        $jobCard->update([
            'symptoms'         => $request->symptoms ?? [],
            'technician_notes' => $request->technician_notes,
        ]);

        return back()->with('success', 'Diagnosis updated.');
    }

    public function addPart(Request $request, JobCard $jobCard) {
        $request->validate([
            'spare_part_id' => 'required|exists:spare_parts,id',
            'quantity'      => 'required|integer|min:1',
        ]);

        $part = SparePart::findOrFail($request->spare_part_id);

        if ($part->stock < $request->quantity) {
            return back()->with('error', "Not enough stock. Available: {$part->stock}");
        }

        $part->decrement('stock', $request->quantity);

        JobCardPart::create([
            'job_card_id'   => $jobCard->id,
            'spare_part_id' => $part->id,
            'quantity'      => $request->quantity,
            'unit_price'    => $part->unit_price,
        ]);

        $total = $jobCard->parts()->sum(DB::raw('quantity * unit_price'));
        $jobCard->update(['total_cost' => $total]);

        return back()->with('success', "Added {$request->quantity}x {$part->name}. Stock deducted: {$part->stock} remaining.");
    }

    public function addLabour(Request $request, JobCard $jobCard) {
    $request->validate([
        'description' => 'required|string|max:150',
        'charge'      => 'required|numeric|min:1',
        'remark'      => 'nullable|string|max:200',
    ]);

    LabourCharge::create([
        'job_card_id' => $jobCard->id,
        'description' => $request->description,
        'charge'      => $request->charge,
        'remark'      => $request->remark,
    ]);

    $partsCost  = $jobCard->parts()->sum(\DB::raw('quantity * unit_price'));
    $labourCost = $jobCard->labourCharges()->sum('charge');
    $jobCard->update(['total_cost' => $partsCost + $labourCost]);

    return back()->with('success', 'Labour charge added.');
    }   

public function removeLabour(LabourCharge $labourCharge) {
    $jobCard = $labourCharge->jobCard;
    $labourCharge->delete();

    $partsCost  = $jobCard->parts()->sum(\DB::raw('quantity * unit_price'));
    $labourCost = $jobCard->labourCharges()->sum('charge');
    $jobCard->update(['total_cost' => $partsCost + $labourCost]);

    return back()->with('success', 'Labour charge removed.');
}
    
}