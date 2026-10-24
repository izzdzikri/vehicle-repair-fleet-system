<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\SparePart;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Company;
use App\Models\JobType;
use App\Models\ActivityLog;
use App\Services\MaintenancePredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index() {
        // Throttled auto-refresh of predictive maintenance alerts — cheap
        // enough to run inline rather than requiring the scheduler cron
        // to be set up on Herd, but capped to once per 6 hours so it
        // doesn't re-scan every vehicle on every dashboard load.
        Cache::remember('maintenance_predictions_autorun', 21600, function () {
            app(MaintenancePredictionService::class)->runAll();
            return true;
        });

        $recentJobs = JobCard::with(['vehicle','staff','appointment'])->latest()->take(5)->get();

        $staffWorkload = User::where('role','staff')
            ->withCount(['jobCards as active_jobs' => function($q) {
                $q->where('current_stage','!=','completed');
            }])->get();

        $totalVehicles = Vehicle::count();
        $urgentAlerts  = \App\Models\MaintenanceAlert::where('urgency', 'high')->where('is_read', false)->count();
        $mediumAlerts  = \App\Models\MaintenanceAlert::where('urgency', 'medium')->where('is_read', false)->count();
        $lowAlerts     = \App\Models\MaintenanceAlert::where('urgency', 'low')->where('is_read', false)->count();

        $upcomingPredictions = \App\Models\MaintenanceAlert::with(['vehicle.owner', 'jobType'])
            ->where('is_read', false)
            ->orderByRaw("FIELD(urgency, 'high', 'medium', 'low')")
            ->orderBy('predicted_due_date')
            ->take(6)
            ->get();

        // Calculate fleet health index percentage
        $vehiclesAtRisk = \App\Models\MaintenanceAlert::whereIn('urgency', ['high', 'medium'])
            ->where('is_read', false)
            ->distinct('vehicle_id')
            ->count('vehicle_id');
        $fleetHealthScore = $totalVehicles > 0
            ? max(0, min(100, round((($totalVehicles - $vehiclesAtRisk) / $totalVehicles) * 100)))
            : 100;

        // Predictive Part Demand & Off-Season Savings (Admin Only)
        $predictionService = app(MaintenancePredictionService::class);
        $demandForecast = $predictionService->getDemandForecast(60);
        $offSeasonSavings = $predictionService->getOffSeasonSavingsAnalysis($demandForecast);

        return view('admin.dashboard', [
            'totalVehicles'       => $totalVehicles,
            'totalUsers'          => User::count(),
            'pendingJobs'         => JobCard::where('current_stage','!=','completed')->count(),
            'lowStock'            => SparePart::whereColumn('stock','<=','min_stock')->count(),
            'todayAppointments'   => Appointment::whereDate('date', today())->count(),
            'pendingAppts'        => Appointment::where('status','pending')->count(),
            'staleJobs'           => JobCard::where('current_stage', '!=', 'completed')
                                       ->where('updated_at', '<', now()->subHours(3))
                                       ->count(),
            'recentJobs'          => $recentJobs,
            'staffWorkload'       => $staffWorkload,
            'urgentAlerts'        => $urgentAlerts,
            'mediumAlerts'        => $mediumAlerts,
            'lowAlerts'           => $lowAlerts,
            'upcomingPredictions' => $upcomingPredictions,
            'fleetHealthScore'    => $fleetHealthScore,
            'vehiclesAtRisk'      => $vehiclesAtRisk,
            'demandForecast'      => $demandForecast,
            'offSeasonSavings'    => $offSeasonSavings,
        ]);
    }

    // Users
    public function users(Request $request) {
    $search = trim((string) $request->input('search'));

    $base = User::query();
    if ($request->role)   $base->where('role',   $request->role);
    if ($request->status) $base->where('status', $request->status);
    if ($search !== '') {
        $base->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    $totalUsers = User::count();

    $users = $base->with('company')->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

    $jobTypes = \App\Models\JobType::orderBy('category')->get();

    return view('admin.users', compact('users', 'jobTypes', 'search', 'totalUsers'));
    }

    public function showUser(User $user) {
        $user->load('company');
        $vehicles     = null;
        $assignedJobs = null;

        if (in_array($user->role, ['individual','corporate'])) {
            $vehicles = Vehicle::where('user_id', $user->id)->get();
        }
        if ($user->role === 'staff') {
            $assignedJobs = JobCard::with(['vehicle','appointment'])
                ->where('staff_id', $user->id)->latest()->get();
        }
        return view('admin.user-show', compact('user','vehicles','assignedJobs'));
    }

   public function updateUser(Request $request, User $user) {
    $request->validate([
        'name'       => 'required|string|max:100',
        'username'   => 'nullable|string|max:50|unique:users,username,'.$user->id,
        'email'      => 'required|email|unique:users,email,'.$user->id,
        'contact_no' => 'nullable|string|max:20',
        'status'     => 'required|in:active,inactive',
        'company_id' => 'nullable|exists:companies,id',
        'avatar'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    // Same PIC-safety guard as the dedicated toggle action: a plain
    // profile-edit save can also flip status to inactive, so it needs
    // the same protection as toggleStatus() below.
    if ($request->status === 'inactive' && $user->status === 'active' && $user->role === 'corporate') {
        if ($user->isLastActivePicOfCompany()) {
            return back()->with('error', 'Cannot deactivate — this is the last active PIC for '.($user->company->name ?? 'this company').'.')->withInput();
        }
        if ($user->isLastActivePrimaryPicOfCompany()) {
            return back()->with('error', 'Cannot deactivate — this is the last active Primary PIC for '.($user->company->name ?? 'this company').'. Add a new Primary PIC first.')->withInput();
        }
    }

    $data = [
        'name'       => $request->name,
        'username'   => $request->username,
        'email'      => $request->email,
        'contact_no' => $request->contact_no,
        'status'     => $request->status,
        'company_id' => $request->company_id,
    ];

    if ($request->hasFile('avatar')) {
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $data['avatar'] = $request->file('avatar')->store('avatars','public');
    }

    if ($request->filled('password')) {
        $request->validate([
            'password'              => 'min:6|confirmed',
            'password_confirmation' => 'required',
        ]);
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);
    return back()->with('success','User updated successfully.');
}

    public function storeUser(Request $request) {
        $request->validate([
            'name'          => 'required|string|max:100',
            'username'      => 'nullable|string|max:50|unique:users',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'role'          => 'required|in:admin,staff,coordinator,corporate,individual',
            'contact_no'    => 'nullable|string|max:20',
            'avatar'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'staff_role'    => 'nullable|string|in:mechanic,accountant,inventory_manager,front_desk',
        ]);

        $data = [
            'name'       => $request->name,
            'username'   => $request->username,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'contact_no' => $request->contact_no,
            'status'     => 'active',
            'staff_role' => $request->role === 'staff' ? $request->staff_role : null,
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars','public');
        }

        $user = User::create($data);
        ActivityLog::record('user.created', "Created {$user->role} account: {$user->name}", $user);

        return back()->with('success','User created successfully. Assign specific permissions from their profile page.');
    }

    public function toggleStatus(User $user) {
        if ($user->id === auth()->id()) {
            return back()->with('error','You cannot deactivate your own account.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        // Only the active -> inactive direction can strand a company;
        // reactivating a PIC is always safe. Same guard the
        // account-request approval flow enforces, so a corporate user
        // can't be silently deactivated through this direct admin
        // action and bypass that safety check.
        if ($newStatus === 'inactive' && $user->role === 'corporate') {
            if ($user->isLastActivePicOfCompany()) {
                return back()->with('error', 'Cannot deactivate — this is the last active PIC for '.($user->company->name ?? 'this company').'.');
            }
            if ($user->isLastActivePrimaryPicOfCompany()) {
                return back()->with('error', 'Cannot deactivate — this is the last active Primary PIC for '.($user->company->name ?? 'this company').'. The company would be left with Viewer-only access. Add a new Primary PIC first.');
            }
        }

        $user->update(['status' => $newStatus]);
        ActivityLog::record('user.status_changed', "Set {$user->name}'s status to {$newStatus}", $user);

        return back()->with('success','User status updated.');
    }

    public function deleteUser(User $user) {
        if ($user->id === auth()->id()) {
            return back()->with('error','You cannot delete your own account.');
        }

        // Same PIC-safety guard as toggleStatus() — deletion is even
        // more permanent, so it needs it at least as much.
        if ($user->role === 'corporate') {
            if ($user->isLastActivePicOfCompany()) {
                return back()->with('error', 'Cannot delete — this is the last active PIC for '.($user->company->name ?? 'this company').'.');
            }
            if ($user->isLastActivePrimaryPicOfCompany()) {
                return back()->with('error', 'Cannot delete — this is the last active Primary PIC for '.($user->company->name ?? 'this company').'. Add a new Primary PIC first.');
            }
        }

        ActivityLog::record('user.deleted', "Deleted user: {$user->name} ({$user->email})", $user);
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->delete();
        return back()->with('success','User deleted.');
    }

    // Companies
    public function companies() {
        $companies = Company::withCount('representatives')->get();
        return view('admin.companies', compact('companies'));
    }

    public function storeCompany(Request $request) {
        $request->validate([
            'name'             => 'required|string|max:100',
            'registration_no'  => 'nullable|string|max:50',
            'address'          => 'nullable|string',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email',
            'person_in_charge' => 'nullable|string|max:100',
        ]);
        Company::create($request->all());
        return back()->with('success','Company added.');
    }

    public function showCompany(Company $company) {
        $company->load('representatives');
        return view('admin.company-show', compact('company'));
    }

    // Job Types
    public function jobTypes() {
        $jobTypes = JobType::orderBy('category')->get();
        return view('admin.job-types', compact('jobTypes'));
    }

    public function storeJobType(Request $request) {
        $request->validate([
            'name'               => 'required|string|max:100',
            'category'           => 'required|string',
            'estimated_minutes'  => 'required|integer|min:5',
            'base_price'         => 'required|numeric|min:0',
            'interval_km'        => 'nullable|integer|min:1',
            'interval_months'    => 'nullable|integer|min:1',
        ]);
        JobType::create($request->all());
        return back()->with('success','Job type added.');
    }

    public function updateJobType(Request $request, \App\Models\JobType $jobType) {
    $request->validate([
        'name'               => 'required|string|max:100',
        'category'           => 'required|string|max:50',
        'estimated_minutes'  => 'required|integer|min:1',
        'base_price'         => 'required|numeric|min:0',
        'interval_km'        => 'nullable|integer|min:1',
        'interval_months'    => 'nullable|integer|min:1',
    ]);

    $oldPrice = $jobType->base_price;
    $jobType->update($request->only([
        'name','category','estimated_minutes','base_price','description',
        'interval_km','interval_months',
    ]));

    if ((float) $oldPrice !== (float) $request->base_price) {
        ActivityLog::record(
            'pricing.changed',
            "Changed price of '{$jobType->name}' from RM".number_format($oldPrice,2)." to RM".number_format($request->base_price,2),
            $jobType
        );
    }

    return back()->with('success', 'Job type updated.');
    }

    public function deleteJobType(JobType $jobType) {
        $jobType->delete();
        return back()->with('success','Job type deleted.');
    }

    public function updateSpecialties(Request $request, \App\Models\User $user) {
    $request->validate([
        'specialties'   => 'nullable|array',
        'specialties.*' => 'exists:job_types,id',
    ]);

    $user->update(['specialties' => $request->specialties ?? []]);

    return back()->with('success', 'Staff specialties updated.');
    }

    /**
     * Assign a staff sub-role and specific permissions (e.g. Accountant +
     * Inventory Manager) independently from the full profile edit form.
     */
    public function updateRole(Request $request, User $user) {
        $request->validate([
            'staff_role'    => 'nullable|string|in:mechanic,accountant,inventory_manager,front_desk',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|in:inventory.manage,pricing.manage,invoice.manage,job_cards.manage_all',
        ]);

        $user->update([
            'staff_role'  => $request->staff_role,
            'permissions' => $request->permissions ?? [],
        ]);

        return back()->with('success', 'Role & permissions updated.');
    }

    // Activity Log
    public function activityLog() {
        $logs = ActivityLog::with('user')->latest()->paginate(30);
        return view('admin.activity-log', compact('logs'));
    }

    /**
     * Prediction Engine Proofreading & Audit Workspace.
     * Allows workshop administrators and academic reviewers to transparently inspect
     * predictive telemetry, backtest accuracy metrics, review empirical derivations,
     * verify predictions, or override due dates with audit justifications.
     */
    public function proofreadPredictions(Request $request) {
        $predictionService = app(MaintenancePredictionService::class);

        // Run backtest evaluation
        $backtest = $predictionService->evaluate();

        // Get demand and procurement forecast
        $forecast = $predictionService->getDemandForecast(60);
        $offSeasonSavings = $predictionService->getOffSeasonSavingsAnalysis($forecast);

        // Check if migration has been executed
        $hasVerificationColumns = false;
        try {
            $hasVerificationColumns = \Illuminate\Support\Facades\Schema::hasColumn('maintenance_alerts', 'is_verified');
        } catch (\Throwable $e) {
            $hasVerificationColumns = false;
        }

        // Query all active predicted alerts with vehicle, owner, company, and optional verifier relations
        $withRelations = ['vehicle.owner.company', 'jobType'];
        if ($hasVerificationColumns) {
            $withRelations[] = 'verifier';
        }

        $query = \App\Models\MaintenanceAlert::with($withRelations)
            ->where('source', 'predicted');

        $statusFilter = $request->input('filter', 'all');
        if ($statusFilter === 'verified') {
            if ($hasVerificationColumns) {
                $query->where('is_verified', true);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($statusFilter === 'pending') {
            if ($hasVerificationColumns) {
                $query->where('is_verified', false);
            }
        } elseif ($statusFilter === 'overridden') {
            if ($hasVerificationColumns) {
                $query->where(function($q) {
                    $q->whereNotNull('overridden_due_date')->orWhereNotNull('overridden_urgency');
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->whereHas('vehicle', function($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $alerts = $query->orderByRaw("FIELD(urgency, 'high', 'medium', 'low')")
            ->orderBy('predicted_due_date')
            ->paginate(25)
            ->withQueryString();

        $totalPredictions = \App\Models\MaintenanceAlert::where('source', 'predicted')->count();
        $verifiedCount = 0;
        $pendingCount = $totalPredictions;
        $overriddenCount = 0;

        if ($hasVerificationColumns) {
            try {
                $verifiedCount = \App\Models\MaintenanceAlert::where('source', 'predicted')->where('is_verified', true)->count();
                $pendingCount = \App\Models\MaintenanceAlert::where('source', 'predicted')->where('is_verified', false)->count();
                $overriddenCount = \App\Models\MaintenanceAlert::where('source', 'predicted')->where(function($q) {
                    $q->whereNotNull('overridden_due_date')->orWhereNotNull('overridden_urgency');
                })->count();
            } catch (\Throwable $e) {
                $verifiedCount = 0;
                $pendingCount = $totalPredictions;
                $overriddenCount = 0;
            }
        }

        $stats = [
            'total_predictions' => $totalPredictions,
            'verified_count'    => $verifiedCount,
            'pending_count'     => $pendingCount,
            'overridden_count'  => $overriddenCount,
        ];

        return view('admin.predictions.proofread', compact(
            'alerts', 'backtest', 'forecast', 'offSeasonSavings', 'stats', 'statusFilter', 'search', 'hasVerificationColumns'
        ));
    }

    /**
     * Mark a prediction as human-verified and accurate.
     */
    public function verifyPrediction(Request $request, \App\Models\MaintenanceAlert $alert) {
        $hasVerificationColumns = false;
        try {
            $hasVerificationColumns = \Illuminate\Support\Facades\Schema::hasColumn('maintenance_alerts', 'is_verified');
        } catch (\Throwable $e) {}

        if (!$hasVerificationColumns) {
            return back()->with('error', 'Database migration required: please run "php artisan migrate" in your terminal first to activate prediction verification.');
        }

        $alert->update([
            'is_verified' => true,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'admin_notes' => $request->input('admin_notes') ?: 'Confirmed accurate by workshop administrator after telemetry audit.',
        ]);

        ActivityLog::record(
            'prediction.verified',
            "Administrator verified prediction for vehicle {$alert->vehicle?->plate_number} ({$alert->alert_type})"
        );

        return back()->with('success', "Prediction for {$alert->vehicle?->plate_number} successfully verified.");
    }

    /**
     * Override / adjust a predicted due date or urgency with justification note.
     */
    public function adjustPrediction(Request $request, \App\Models\MaintenanceAlert $alert) {
        $hasVerificationColumns = false;
        try {
            $hasVerificationColumns = \Illuminate\Support\Facades\Schema::hasColumn('maintenance_alerts', 'is_verified');
        } catch (\Throwable $e) {}

        if (!$hasVerificationColumns) {
            return back()->with('error', 'Database migration required: please run "php artisan migrate" in your terminal first to activate prediction parameter overrides.');
        }

        $request->validate([
            'overridden_due_date' => 'nullable|date',
            'overridden_urgency'  => 'nullable|in:low,medium,high',
            'admin_notes'         => 'required|string|max:1000',
        ]);

        $alert->update([
            'overridden_due_date' => $request->overridden_due_date ?: null,
            'overridden_urgency'  => $request->overridden_urgency ?: null,
            'admin_notes'         => $request->admin_notes,
            'is_verified'         => true,
            'verified_by'         => auth()->id(),
            'verified_at'         => now(),
        ]);

        ActivityLog::record(
            'prediction.adjusted',
            "Administrator adjusted prediction for vehicle {$alert->vehicle?->plate_number}: {$request->admin_notes}"
        );

        return back()->with('success', "Prediction parameters adjusted and verified for {$alert->vehicle?->plate_number}.");
    }

    /**
     * Auto-draft a Purchase Order from the predictive shortfall forecast with off-season discount support.
     */
    public function draftForecastPurchaseOrder(Request $request) {
        $request->validate([
            'spare_part_id' => 'required|exists:spare_parts,id',
            'quantity'      => 'required|integer|min:1',
            'unit_cost'     => 'nullable|numeric|min:0.01',
            'is_off_season' => 'nullable|boolean',
        ]);

        $part = \App\Models\SparePart::findOrFail($request->spare_part_id);
        $supplier = \App\Models\Supplier::where('name', $part->supplier_name)->first()
            ?? \App\Models\Supplier::first();

        $unitCost = $request->unit_cost ?: (float) $part->unit_price;
        $isOffSeason = (bool) $request->input('is_off_season', false);

        $notes = $isOffSeason
            ? "Auto-drafted: Locked in Off-Season Supplier Discount (RM " . number_format($unitCost, 2) . "/unit vs regular RM " . number_format($part->unit_price, 2) . ")."
            : "Auto-drafted from Predictive Maintenance Demand Forecast shortfall analysis.";

        $po = \App\Models\PurchaseOrder::create([
            'supplier_id'    => $supplier?->id,
            'spare_part_id'  => $part->id,
            'quantity'       => $request->quantity,
            'unit_cost'      => $unitCost,
            'status'         => 'draft',
            'auto_generated' => true,
            'notes'          => $notes,
            'created_by'     => auth()->id(),
        ]);

        ActivityLog::record(
            'purchase_order.auto_drafted',
            "Auto-drafted PO #{$po->id} for {$part->name} ({$request->quantity} units) " . ($isOffSeason ? 'with Off-Season Discount' : '')
        );

        $savedMsg = $isOffSeason ? " with locked-in off-season discount rate (RM " . number_format($unitCost, 2) . "/unit)" : "";

        return redirect('/admin/purchase-orders')
            ->with('success', "Purchase Order #{$po->id} auto-drafted for {$part->name} ({$request->quantity} units){$savedMsg}.");
    }
}