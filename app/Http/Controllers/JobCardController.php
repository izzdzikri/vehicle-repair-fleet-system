<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\JobType;
use App\Models\LabourCharge;
use App\Models\Notification;
use App\Models\ServiceHistory;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobCardController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $perPage = 15;

        // Remember the last-used stage tab across visits (e.g. via
        // sidebar navigation, not just browser back) so returning to
        // this page keeps you on the tab you were last looking at
        // instead of resetting to "All". The "All" tab always passes an
        // explicit stage=all so it can update/clear the remembered
        // value too, rather than being indistinguishable from a fresh
        // page load.
        if ($request->has('stage')) {
            session(['job_cards_filter_stage' => $request->query('stage')]);
            $activeStage = $request->query('stage');
        } else {
            $activeStage = session('job_cards_filter_stage', 'all');
        }
        $filterStage = $activeStage === 'all' ? null : $activeStage;

        $applySearch = function ($query) use ($search) {
            if ($search === '') {
                return;
            }
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%"))
                    ->orWhereHas('staff', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        };

        // Stage tab counts, independent of pagination (single aggregate query).
        $countScope = JobCard::query();
        $applySearch($countScope);
        $rawCounts = (clone $countScope)
            ->selectRaw("
                count(*) as all_count,
                sum(case when current_stage = 'received' then 1 else 0 end) as received,
                sum(case when current_stage = 'diagnosing' then 1 else 0 end) as diagnosing,
                sum(case when current_stage = 'waiting_parts' then 1 else 0 end) as waiting_parts,
                sum(case when current_stage = 'repairing' then 1 else 0 end) as repairing,
                sum(case when current_stage = 'quality_check' then 1 else 0 end) as quality_check,
                sum(case when current_stage = 'completed' then 1 else 0 end) as completed
            ")->first();

        $stageCounts = [
            'all' => (int) ($rawCounts->all_count ?? 0),
            'received' => (int) ($rawCounts->received ?? 0),
            'diagnosing' => (int) ($rawCounts->diagnosing ?? 0),
            'waiting_parts' => (int) ($rawCounts->waiting_parts ?? 0),
            'repairing' => (int) ($rawCounts->repairing ?? 0),
            'quality_check' => (int) ($rawCounts->quality_check ?? 0),
            'completed' => (int) ($rawCounts->completed ?? 0),
        ];

        $query = JobCard::with(['vehicle', 'staff', 'appointment', 'jobType']);
        if ($filterStage) {
            $query->where('current_stage', $filterStage);
        }
        $applySearch($query);

        $jobs = $query->latest()->paginate($perPage)->withQueryString();

        return view('job-cards.index', compact('jobs', 'search', 'stageCounts', 'activeStage'));
    }

    public function create()
    {
        $appointments = Appointment::where('status', 'confirmed')
            ->whereDoesntHave('jobCard')
            ->with(['vehicle', 'user'])
            ->get();

        $staff = User::where('role', 'staff')
            ->where('status', 'active')
            ->get();
        $jobTypes = JobType::orderBy('category')->get();

        return view('job-cards.create', compact('appointments', 'staff', 'jobTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'staff_id' => 'required|exists:users,id',
        ]);

        // Walk-in job card: appointment_id may be null if created directly
        // from the walk-in appointment (which auto-confirms), but we still
        // require a vehicle_id in that case.
        $vehicleId = null;
        $appointmentId = $request->appointment_id;

        if ($appointmentId) {
            $appointment = Appointment::findOrFail($appointmentId);
            $vehicleId = $appointment->vehicle_id;
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
            'appointment_id' => $appointmentId,
            'vehicle_id' => $vehicleId,
            'staff_id' => $request->staff_id,
            'job_type_id' => $request->job_type_id,
            'current_stage' => 'received',
            'diagnosis' => $request->diagnosis,
            'estimated_completion' => $estimatedCompletion,
        ]);

        $vehicle = Vehicle::find($vehicleId);
        Notification::send(
            $request->staff_id,
            'New Job Assigned',
            "You've been assigned to ".($vehicle->plate_number ?? 'a vehicle').' ('.($vehicle->brand ?? '').' '.($vehicle->model ?? '').').',
            '/staff/job-cards/'.$jobCard->id
        );

        return redirect('/admin/job-cards')->with('success', 'Job card created and assigned.');
    }

    public function show(JobCard $jobCard)
    {
        $jobCard->load(['vehicle', 'staff', 'parts.sparePart', 'appointment.user', 'jobType', 'labourCharges', 'checkins.coordinator']);
        $spareParts = SparePart::where('stock', '>', 0)->orderBy('category')->get();
        $jobTypes = JobType::orderBy('category')->orderBy('name')->get();
        $canEdit = $jobCard->canBeEditedBy(auth()->user());

        return view('job-cards.show', compact('jobCard', 'spareParts', 'jobTypes', 'canEdit'));
    }

    // ----------------------------------------------------------------
    // EDF (Earliest Deadline First) schedule board
    // ----------------------------------------------------------------

    public function schedule()
    {
        $jobs = JobCard::with(['vehicle', 'staff', 'jobType', 'appointment'])
            ->where('current_stage', '!=', 'completed')
            ->orderByRaw('estimated_completion IS NULL, estimated_completion ASC')
            ->get();

        $grouped = $jobs->groupBy(fn ($j) => $j->staff->name ?? 'Unassigned');

        return view('job-cards.schedule', compact('jobs', 'grouped'));
    }

    // ----------------------------------------------------------------
    // Kanban board — drag-and-drop stage view
    // ----------------------------------------------------------------

    public function board()
    {
        $jobs = JobCard::with(['vehicle', 'staff', 'jobType'])
            ->orderByRaw('estimated_completion IS NULL, estimated_completion ASC')
            ->get();

        $stages = ['received', 'diagnosing', 'waiting_parts', 'repairing', 'quality_check', 'completed'];
        $grouped = collect($stages)->mapWithKeys(fn ($s) => [$s => $jobs->where('current_stage', $s)->values()]);

        return view('job-cards.board', compact('grouped', 'stages'));
    }

    /**
     * Central permission gate for all job-card WRITE actions below.
     * Viewing (show, board, schedule) stays open to every staff member —
     * only mutations are restricted to the assigned mechanic, admins,
     * coordinators, and anyone holding job_cards.manage_all.
     */
    private function assertCanEdit(Request $request, JobCard $jobCard): void
    {
        if ($jobCard->canBeEditedBy(auth()->user())) {
            return;
        }

        if ($request->wantsJson()) {
            abort(response()->json([
                'success' => false,
                'message' => 'This job card is assigned to another staff member. You can view it but not make changes.',
            ], 403));
        }

        abort(403, 'This job card is assigned to another staff member. You can view it but not make changes.');
    }

    public function updateStage(Request $request, JobCard $jobCard)
    {
        $this->assertCanEdit($request, $jobCard);

        $request->validate([
            'current_stage' => 'required|in:received,diagnosing,waiting_parts,repairing,quality_check,completed',
        ]);

        $currentStage = $jobCard->current_stage;
        $targetStage = $request->current_stage;

        // Enforce valid workshop state transitions
        if ($currentStage !== $targetStage) {
            $allowedTransitions = [
                'received' => ['diagnosing'],
                'diagnosing' => ['waiting_parts', 'repairing'],
                'waiting_parts' => ['repairing', 'diagnosing'],
                'repairing' => ['waiting_parts', 'quality_check'],
                'quality_check' => ['completed', 'repairing'],
                'completed' => auth()->user()->role === 'admin' ? ['repairing', 'quality_check'] : [],
            ];

            $allowed = $allowedTransitions[$currentStage] ?? [];
            if (! in_array($targetStage, $allowed, true)) {
                $msg = "Invalid stage transition from '{$currentStage}' to '{$targetStage}'. Stages must follow the workshop workflow.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return back()->with('error', $msg);
            }
        }

        // If an admin reopens a completed job, roll back service history and restore appointment status
        if ($currentStage === 'completed' && $targetStage !== 'completed') {
            ServiceHistory::where('job_card_id', $jobCard->id)->delete();
            if ($jobCard->appointment) {
                $jobCard->appointment->update(['status' => 'confirmed']);
            }
        }

        $jobCard->update([
            'current_stage' => $targetStage,
            'completed_at' => $targetStage === 'completed' ? now() : null,
        ]);

        if ($targetStage === 'completed') {
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
                    'vehicle_id' => $jobCard->vehicle_id,
                    'service_date' => now()->toDateString(),
                    'description' => $jobCard->jobType->name
                                   ?? $jobCard->diagnosis
                                   ?? $jobCard->appointment->service_type
                                   ?? 'Service completed',
                    'cost' => $jobCard->total_cost,
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
    public function updateSymptoms(Request $request, JobCard $jobCard)
    {
        $this->assertCanEdit($request, $jobCard);

        $request->validate([
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string|max:100',
            'technician_notes' => 'nullable|string|max:2000',
        ]);

        $jobCard->update([
            'symptoms' => $request->symptoms ?? [],
            'technician_notes' => $request->technician_notes,
        ]);

        return back()->with('success', 'Diagnosis updated.');
    }

    public function addPart(Request $request, JobCard $jobCard)
    {
        $this->assertCanEdit($request, $jobCard);

        $request->validate([
            'spare_part_id' => 'required|exists:spare_parts,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $deductedPart = DB::transaction(function () use ($jobCard, $request) {
                $lockedPart = SparePart::where('id', $request->spare_part_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPart->stock < $request->quantity) {
                    throw new \InvalidArgumentException("Not enough stock. Available: {$lockedPart->stock}");
                }

                $lockedPart->decrement('stock', $request->quantity);

                JobCardPart::create([
                    'job_card_id' => $jobCard->id,
                    'spare_part_id' => $lockedPart->id,
                    'quantity' => $request->quantity,
                    'unit_price' => $lockedPart->unit_price,
                ]);

                $partsCost = $jobCard->parts()->sum(DB::raw('quantity * unit_price'));
                $labourCost = $jobCard->labourCharges()->sum('charge');
                $jobCard->update(['total_cost' => $partsCost + $labourCost]);

                ActivityLog::record(
                    'job_card.part_added',
                    "Added {$request->quantity}x {$lockedPart->name} to Job #{$jobCard->id}.",
                    $jobCard
                );

                return $lockedPart;
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Added {$request->quantity}x {$deductedPart->name}. Stock deducted: {$deductedPart->fresh()->stock} remaining.");
    }

    public function removePart(Request $request, JobCard $jobCard, JobCardPart $jobCardPart)
    {
        $this->assertCanEdit($request, $jobCard);

        if ($jobCardPart->job_card_id !== $jobCard->id) {
            abort(404);
        }

        $partName = $jobCardPart->sparePart->name ?? 'Part';
        $qty = $jobCardPart->quantity;

        DB::transaction(function () use ($jobCard, $jobCardPart, $partName, $qty) {
            if ($jobCardPart->spare_part_id) {
                $lockedPart = SparePart::where('id', $jobCardPart->spare_part_id)
                    ->lockForUpdate()
                    ->first();
                if ($lockedPart) {
                    $lockedPart->increment('stock', $qty);
                }
            }

            $jobCardPart->delete();

            $partsCost = $jobCard->parts()->sum(DB::raw('quantity * unit_price'));
            $labourCost = $jobCard->labourCharges()->sum('charge');
            $jobCard->update(['total_cost' => $partsCost + $labourCost]);

            ActivityLog::record(
                'job_card.part_removed',
                "Removed {$qty}x {$partName} from Job #{$jobCard->id} (stock restored to warehouse).",
                $jobCard
            );
        });

        return back()->with('success', "Removed {$partName} (×{$qty}). Stock has been restored to inventory.");
    }

    public function addLabour(Request $request, JobCard $jobCard)
    {
        $this->assertCanEdit($request, $jobCard);

        $request->validate([
            'description' => 'required|string|max:150',
            'charge' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:200',
        ]);

        LabourCharge::create([
            'job_card_id' => $jobCard->id,
            'description' => $request->description,
            'charge' => $request->charge,
            'remark' => $request->remark,
        ]);

        $partsCost = $jobCard->parts()->sum(\DB::raw('quantity * unit_price'));
        $labourCost = $jobCard->labourCharges()->sum('charge');
        $jobCard->update(['total_cost' => $partsCost + $labourCost]);

        return back()->with('success', 'Labour charge added.');
    }

    public function removeLabour(Request $request, LabourCharge $labourCharge)
    {
        $jobCard = $labourCharge->jobCard;
        $this->assertCanEdit($request, $jobCard);

        $labourCharge->delete();

        $partsCost = $jobCard->parts()->sum(\DB::raw('quantity * unit_price'));
        $labourCost = $jobCard->labourCharges()->sum('charge');
        $jobCard->update(['total_cost' => $partsCost + $labourCost]);

        return back()->with('success', 'Labour charge removed.');
    }

    // ----------------------------------------------------------------
    // Inspection photos — technician-uploaded photo documentation,
    // stored as a JSON array on the job card itself (same pattern the
    // `symptoms` column already uses). Guarded by the same assertCanEdit()
    // as every other mutation above.
    // ----------------------------------------------------------------

    public function uploadPhoto(Request $request, JobCard $jobCard)
    {
        $this->assertCanEdit($request, $jobCard);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'caption' => 'nullable|string|max:150',
        ]);

        $path = $request->file('photo')->store('inspection-photos/'.$jobCard->id, 'public');

        $photos = $jobCard->inspection_photos ?? [];
        $photos[] = [
            'id' => (string) Str::uuid(),
            'path' => $path,
            'caption' => $request->caption,
            'stage' => $jobCard->current_stage,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now()->toDateTimeString(),
        ];

        $jobCard->update(['inspection_photos' => $photos]);

        return back()->with('success', 'Inspection photo uploaded.');
    }

    public function removePhoto(Request $request, JobCard $jobCard, string $photoId)
    {
        $this->assertCanEdit($request, $jobCard);

        $photos = $jobCard->inspection_photos ?? [];
        $target = collect($photos)->firstWhere('id', $photoId);

        if (! $target) {
            return back()->with('error', 'Photo not found — it may have already been removed.');
        }

        Storage::disk('public')->delete($target['path']);

        $photos = collect($photos)->reject(fn ($p) => $p['id'] === $photoId)->values()->all();
        $jobCard->update(['inspection_photos' => $photos]);

        return back()->with('success', 'Inspection photo removed.');
    }
}
