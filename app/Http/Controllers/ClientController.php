<?php

namespace App\Http\Controllers;

use App\Models\AccountRequest;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\MaintenanceAlert;
use App\Models\ServiceHistory;
use App\Models\TripLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Prediction\DecisionTreePredictor;
use App\Services\Prediction\EnsemblePredictor;
use App\Services\Prediction\InventoryDemandAdvisoryService;
use App\Services\Prediction\LogisticRegressionPredictor;
use App\Services\Prediction\RandomForestPredictor;
use App\Services\Prediction\RuleBasedPredictor;
use App\Services\Prediction\SvmPredictor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');
        $companyId = $user->company_id;

        // All PICs under the same company
        $companyUserIds = User::where('company_id', $companyId)
            ->pluck('id');

        // All vehicles belonging to ANY user in this company
        $vehicles = Vehicle::whereIn('user_id', $companyUserIds)
            ->with('owner')
            ->orderBy('plate_number')
            ->get();

        $vehicleIds = $vehicles->pluck('id');

        // All appointments across the whole fleet
        $appointments = Appointment::whereIn('user_id', $companyUserIds)
            ->with(['vehicle', 'jobCard'])
            ->latest()
            ->take(10)
            ->get();

        // Service history across the whole fleet
        $serviceHistory = ServiceHistory::whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle')
            ->latest('service_date')
            ->take(10)
            ->get();

        $historyStats = $vehicleIds->isEmpty()
            ? (object) ['total_services' => 0, 'total_spent' => 0]
            : ServiceHistory::whereIn('vehicle_id', $vehicleIds)
                ->selectRaw('count(*) as total_services, coalesce(sum(cost), 0) as total_spent')
                ->first();

        $totalServices = (int) ($historyStats->total_services ?? 0);
        $totalSpent = (float) ($historyStats->total_spent ?? 0);

        // Maintenance alerts across the whole fleet
        $maintenanceAlerts = MaintenanceAlert::whereIn('vehicle_id', $vehicleIds)
            ->with(['vehicle.owner', 'jobType'])
            ->orderByRaw("CASE urgency WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('is_read')
            ->latest()
            ->get();

        // Other PICs in the same company
        $companyPics = User::where('company_id', $companyId)
            ->where('id', '!=', $user->id)
            ->get();

        return view('client.dashboard', compact(
            'vehicles', 'appointments', 'serviceHistory',
            'totalServices', 'totalSpent', 'maintenanceAlerts', 'companyPics'
        ));
    }

    public function company()
    {
        $user = auth()->user();
        abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');
        $companyId = $user->company_id;

        $company = Company::find($companyId);

        $pics = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $requests = AccountRequest::where('company_id', $companyId)
            ->with(['requester', 'targetUser'])
            ->latest()
            ->get();

        return view('client.company', compact('company', 'pics', 'requests'));
    }

    public function statement(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');
        $companyId = $user->company_id;
        $company = $companyId ? Company::find($companyId) : null;

        $companyUserIds = $companyId ? User::where('company_id', $companyId)->pluck('id') : collect([$user->id]);
        $vehicles = Vehicle::whereIn('user_id', $companyUserIds)->with('owner')->orderBy('plate_number')->get();
        $vehicleIds = $vehicles->pluck('id');

        $month = $request->input('month', now()->format('Y-m'));

        $historyQuery = ServiceHistory::whereIn('vehicle_id', $vehicleIds)->with(['vehicle', 'jobCard']);
        $invoicesQuery = Invoice::whereHas('jobCard.appointment', fn ($q) => $q->whereIn('user_id', $companyUserIds))
            ->with(['jobCard.vehicle', 'jobCard.appointment.user', 'payments']);

        if ($month !== 'all') {
            try {
                $period = Carbon::createFromFormat('Y-m', $month);
                $start = $period->copy()->startOfMonth()->toDateString();
                $end = $period->copy()->endOfMonth()->toDateString();

                $historyQuery->whereBetween('service_date', [$start, $end]);
                $invoicesQuery->whereBetween('created_at', [
                    $period->copy()->startOfMonth()->startOfDay(),
                    $period->copy()->endOfMonth()->endOfDay(),
                ]);
            } catch (\Exception $e) {
                $month = 'all';
            }
        }

        $serviceHistory = $historyQuery->latest('service_date')->get();
        $invoices = $invoicesQuery->latest()->get();

        $totalSpent = (float) $serviceHistory->sum('cost');
        if ($totalSpent == 0 && $invoices->isNotEmpty()) {
            $totalSpent = (float) $invoices->sum('total');
        }

        $totalInvoiced = (float) $invoices->sum('total');
        $totalPaid = (float) $invoices->sum('amount_paid');
        $outstandingBalance = (float) $invoices->sum(fn ($i) => $i->balance);
        $totalServices = $serviceHistory->count();

        // Group by vehicle for summary
        $vehicleSummary = $vehicles->map(function ($vehicle) use ($serviceHistory, $invoices) {
            $vHistory = $serviceHistory->where('vehicle_id', $vehicle->id);
            $vInvoices = $invoices->filter(fn ($inv) => $inv->jobCard?->vehicle_id === $vehicle->id);
            $spend = (float) $vHistory->sum('cost');
            if ($spend == 0 && $vInvoices->isNotEmpty()) {
                $spend = (float) $vInvoices->sum('total');
            }

            return [
                'vehicle' => $vehicle,
                'service_count' => $vHistory->count(),
                'total_spend' => $spend,
                'last_service' => $vHistory->sortByDesc('service_date')->first()?->service_date,
            ];
        })->sortByDesc('total_spend')->values();

        $data = compact(
            'company', 'vehicles', 'serviceHistory', 'invoices',
            'totalSpent', 'totalInvoiced', 'totalPaid', 'outstandingBalance',
            'totalServices', 'vehicleSummary', 'month'
        );

        if ($request->query('format') === 'pdf' || $request->has('pdf')) {
            $pdf = Pdf::loadView('client.statement-pdf', $data)
                ->setPaper('a4', 'portrait');
            $safeCompanyName = Str::slug($company?->name ?? 'Corporate-Fleet');
            $filename = "Fleet-Statement-{$safeCompanyName}-{$month}.pdf";

            return $pdf->download($filename);
        }

        return view('client.statement', $data);
    }

    // ─────────────────────────── Fleet Health Report ──────────────────────────

    public function fleetHealthReport(Request $request, InventoryDemandAdvisoryService $advisoryService)
    {
        $user = auth()->user();
        abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');
        $companyId = $user->company_id;
        $company = Company::findOrFail($companyId);

        $companyUserIds = User::where('company_id', $companyId)->pluck('id');
        $vehicles = Vehicle::whereIn('user_id', $companyUserIds)
            ->with(['owner', 'serviceHistory', 'maintenanceAlerts'])
            ->orderBy('plate_number')
            ->get();

        $vehicleIds = $vehicles->pluck('id');
        $alerts = MaintenanceAlert::whereIn('vehicle_id', $vehicleIds)
            ->where('is_read', false)
            ->with(['vehicle.owner', 'jobType', 'verifier'])
            ->orderByRaw("CASE urgency WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('predicted_due_date')
            ->get();

        $highUrgencyAlerts = $alerts->where('urgency', 'high');
        $mediumUrgencyAlerts = $alerts->where('urgency', 'medium');
        $lowUrgencyAlerts = $alerts->where('urgency', 'low');

        $totalVehicles = $vehicles->count();
        $atRiskVehiclesCount = $alerts->whereIn('urgency', ['high', 'medium'])->pluck('vehicle_id')->unique()->count();
        $healthyVehiclesCount = max(0, $totalVehicles - $atRiskVehiclesCount);
        $fleetHealthScore = $totalVehicles > 0 ? (int) round(($healthyVehiclesCount / $totalVehicles) * 100) : 100;

        // Top risk vehicles
        $topRiskAlerts = $alerts->whereIn('urgency', ['high', 'medium'])->take(8);

        // Inventory restock advisory hints (recommendation only, never auto-purchases)
        $inventoryAdvisory = $advisoryService->generateAdvisory($alerts);

        $workshop = [
            'name'        => 'Teraju Setia Enterprise',
            'tagline'     => 'Commercial Fleet Maintenance & Vehicle Repair Specialists',
            'reg_no'      => 'TSE-2024-KPT/FYP',
            'supervisor'  => 'Dr. Nureize (Technical Supervisor)',
            'address'     => 'Kawasan Perindustrian Ringan Batu 3, Jalan Kapar, 41400 Klang, Selangor',
            'phone'       => '+60 3-3344 5566 / +60 12-345 6789',
            'email'       => 'fleet@terajusetia.com.my',
            'report_date' => now()->format('d M Y, h:i A'),
        ];

        return view('client.fleet-health-report', compact(
            'company', 'vehicles', 'alerts', 'totalVehicles',
            'highUrgencyAlerts', 'mediumUrgencyAlerts', 'lowUrgencyAlerts',
            'atRiskVehiclesCount', 'healthyVehiclesCount', 'fleetHealthScore',
            'topRiskAlerts', 'inventoryAdvisory', 'workshop'
        ));
    }

    public function exportFleetHealthCsv(Request $request, InventoryDemandAdvisoryService $advisoryService)
    {
        $user = auth()->user();
        abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');
        $companyId = $user->company_id;
        $company = Company::findOrFail($companyId);

        $companyUserIds = User::where('company_id', $companyId)->pluck('id');
        $vehicles = Vehicle::whereIn('user_id', $companyUserIds)->with('owner')->get();
        $vehicleIds = $vehicles->pluck('id');

        $alerts = MaintenanceAlert::whereIn('vehicle_id', $vehicleIds)
            ->where('is_read', false)
            ->with(['vehicle', 'jobType'])
            ->orderByRaw("CASE urgency WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('predicted_due_date')
            ->get();

        $inventoryAdvisory = $advisoryService->generateAdvisory($alerts);

        $filename = 'Fleet_Health_Report_' . Str::slug($company->name) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($company, $vehicles, $alerts, $inventoryAdvisory) {
            $fp = fopen('php://output', 'w');

            // Metadata Header
            fputcsv($fp, ['# ==============================================================================']);
            fputcsv($fp, ['# TERAJU SETIA ENTERPRISE — CORPORATE FLEET HEALTH REPORT']);
            fputcsv($fp, ['# Client Company:', $company->name]);
            fputcsv($fp, ['# Report Timestamp:', now()->toDateTimeString()]);
            fputcsv($fp, ['# Fleet Size:', $vehicles->count(), 'Active Unresolved Alerts:', $alerts->count()]);
            fputcsv($fp, ['# Workshop Address:', 'Kawasan Perindustrian Ringan Batu 3, Jalan Kapar, Klang, Selangor']);
            fputcsv($fp, ['# ==============================================================================']);
            fputcsv($fp, []);

            // Section 1: Summary Counts
            fputcsv($fp, ['[SECTION 1: FLEET HEALTH SUMMARY]']);
            fputcsv($fp, ['Urgency Level', 'Active Alert Count', 'Status Description']);
            fputcsv($fp, ['HIGH', $alerts->where('urgency', 'high')->count(), 'Action required immediately']);
            fputcsv($fp, ['MEDIUM', $alerts->where('urgency', 'medium')->count(), 'Schedule maintenance within 14 days']);
            fputcsv($fp, ['LOW', $alerts->where('urgency', 'low')->count(), 'Informational / regular bounds']);
            fputcsv($fp, []);

            // Section 2: Per-Vehicle Risk Assessment
            fputcsv($fp, ['[SECTION 2: FLEET VEHICLES RISK ASSESSMENT & PREDICTIONS]']);
            fputcsv($fp, [
                'Plate Number', 'Brand & Model', 'Year', 'Mileage (km)',
                'Service / Issue', 'Urgency', 'Confidence', 'Agreement',
                'Predicted Due Date', 'Overdue Status', 'Verified Status', 'Recommended Action'
            ]);

            foreach ($alerts as $a) {
                $due = $a->effective_due_date;
                $isOverdue = $due && $due->isPast();
                $diff = $due ? abs((int) now()->startOfDay()->diffInDays($due->startOfDay(), false)) : 0;
                $overdueStr = $due ? ($isOverdue ? "Overdue by {$diff} days" : "Due in {$diff} days") : 'N/A';

                fputcsv($fp, [
                    $a->vehicle->plate_number ?? 'N/A',
                    trim(($a->vehicle->brand ?? '') . ' ' . ($a->vehicle->model ?? '')),
                    $a->vehicle->year ?? 'N/A',
                    $a->vehicle->mileage ?? 0,
                    $a->alert_type,
                    strtoupper($a->urgency),
                    $a->plain_confidence,
                    $a->plain_agreement_rate ?: 'Ensemble Consensus',
                    $due ? $due->format('Y-m-d') : 'Immediate',
                    $overdueStr,
                    $a->plain_verification_status,
                    $a->recommendation,
                ]);
            }
            fputcsv($fp, []);

            // Section 3: Recommended Spare Parts Restock Demand
            fputcsv($fp, ['[SECTION 3: INVENTORY RESTOCK ADVISORY (RECOMMENDATION ONLY)]']);
            fputcsv($fp, [
                'Part Name', 'Part Number', 'Category', 'Recommended Qty',
                'Current Stock', 'Min Stock', 'Shortfall Units', 'Unit Price (RM)',
                'Estimated Cost (RM)', 'Supplier', 'Advisory Note'
            ]);

            foreach ($inventoryAdvisory['advisory_items'] as $item) {
                fputcsv($fp, [
                    $item['part_name'],
                    $item['part_number'],
                    $item['category'],
                    $item['recommended_qty'],
                    $item['current_stock'],
                    $item['min_stock'],
                    $item['shortfall_units'],
                    number_format($item['unit_price'], 2),
                    number_format($item['estimated_cost'], 2),
                    $item['supplier_name'],
                    $item['needs_restock'] ? 'Shortfall detected - recommend PO draft' : 'Sufficient stock',
                ]);
            }

            fputcsv($fp, []);
            fputcsv($fp, ['# DISCLAIMER:', $inventoryAdvisory['disclaimer']]);

            fclose($fp);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    // ─────────────────────────── Corporate Bulk Fleet Check ───────────────────

    public function fleetCheck(Request $request, InventoryDemandAdvisoryService $advisoryService)
    {
        $user = auth()->user();
        abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');
        $companyId = $user->company_id;
        $company = Company::findOrFail($companyId);

        $companyUserIds = User::where('company_id', $companyId)->pluck('id');
        $vehicles = Vehicle::whereIn('user_id', $companyUserIds)
            ->with(['owner', 'serviceHistory', 'maintenanceAlerts'])
            ->orderBy('plate_number')
            ->get();

        $vehicleIds = $vehicles->pluck('id');
        $alerts = MaintenanceAlert::whereIn('vehicle_id', $vehicleIds)
            ->where('is_read', false)
            ->with(['vehicle.owner', 'jobType', 'verifier'])
            ->orderByRaw("CASE urgency WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('predicted_due_date')
            ->get();

        $inventoryAdvisory = $advisoryService->generateAdvisory($alerts);
        $isPrimaryPic = $user->isPrimaryPic();

        return view('client.fleet-check', compact(
            'company', 'vehicles', 'alerts', 'inventoryAdvisory', 'isPrimaryPic'
        ));
    }

    public function runBulkFleetCheck(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');

        // Enforce PIC authorization policy: Primary PIC only
        if (! $user->isPrimaryPic()) {
            abort(403, 'Unauthorized: Only a Primary PIC can execute batch fleet predictions. Secondary and Viewer PICs have read-only access.');
        }

        $companyId = $user->company_id;
        $companyUserIds = User::where('company_id', $companyId)->pluck('id');

        $query = Vehicle::whereIn('user_id', $companyUserIds)->with(['serviceHistory', 'tripLogs', 'owner']);

        // Filter selected vehicle IDs if passed
        if ($request->filled('vehicle_ids') && is_array($request->vehicle_ids)) {
            $query->whereIn('id', $request->vehicle_ids);
        }

        // Support CSV upload
        if ($request->hasFile('fleet_csv')) {
            $request->validate([
                'fleet_csv' => 'required|file|mimes:csv,txt|max:10240',
            ]);

            $file = $request->file('fleet_csv');
            $handle = fopen($file->getRealPath(), 'r');
            $headers = fgetcsv($handle, 0, ',', '"', "\\");
            $csvPlates = [];
            if ($headers) {
                while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
                    if (! empty($row[0])) {
                        $csvPlates[] = trim($row[0]);
                    }
                }
            }
            fclose($handle);

            if (! empty($csvPlates)) {
                $query->whereIn('plate_number', $csvPlates);
            }
        }

        $vehicles = $query->get();

        if ($vehicles->isEmpty()) {
            return back()->with('error', 'No fleet vehicles found to analyze.');
        }

        $ensemble = new EnsemblePredictor([
            'rule_based'          => new RuleBasedPredictor(),
            'decision_tree'       => new DecisionTreePredictor(),
            'random_forest'       => new RandomForestPredictor(nEstimators: 30),
            'svm'                 => new SvmPredictor(),
            'logistic_regression' => new LogisticRegressionPredictor(),
        ]);

        $processedCount = 0;
        foreach ($vehicles as $vehicle) {
            $sample = $this->extractVehicleFeatures($vehicle);
            $pred = $ensemble->predictDetailed($sample);

            $urgency = strtolower($pred['final_urgency']);
            $conf = (float) $pred['confidence'];
            $agreement = $pred['agreement_fraction'];

            $daysRem = $urgency === 'high' ? 2 : ($urgency === 'medium' ? 14 : 45);
            $dueDate = now()->addDays($daysRem)->toDateString();

            // Update or create alert
            MaintenanceAlert::updateOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'source'     => 'predicted',
                ],
                [
                    'alert_type'          => 'Periodic Fleet Inspection',
                    'urgency'             => $urgency,
                    'recommendation'      => sprintf('Multi-model consensus projected %s urgency (Confidence: %.1f%%, %s).', ucfirst($urgency), $conf, $agreement ? "{$agreement} agreed" : 'Consensus'),
                    'predicted_due_date'  => $dueDate,
                    'predicted_by_algo'   => 'ensemble',
                    'confidence_score'    => $conf,
                    'agreement_rate'      => $agreement,
                    'ensemble_votes_json' => $pred['model_predictions'],
                    'is_read'             => false,
                ]
            );

            $processedCount++;
        }

        return back()->with('success', "Batch fleet prediction successfully completed for {$processedCount} vehicle(s).");
    }

    private function extractVehicleFeatures(Vehicle $vehicle): array
    {
        $today = Carbon::today();
        $vehicleAge = max(1, $today->year - (int) ($vehicle->year ?: $today->year - 3));
        $mileage = (float) ($vehicle->mileage ?: 50000.0);

        // Days and Km since last service
        $lastHistory = $vehicle->serviceHistory()->latest('service_date')->first();
        if ($lastHistory && $lastHistory->service_date) {
            $lastDate = Carbon::parse($lastHistory->service_date);
            $daysSince = max(0, (int) $lastDate->diffInDays($today));
            $kmSince = max(0.0, $mileage - (float) ($lastHistory->mileage_at_service ?: ($mileage - 8000)));
        } else {
            $daysSince = 120;
            $kmSince = 8500.0;
        }

        // Daily usage from trip logs
        $trips = $vehicle->tripLogs()->latest('trip_date')->take(10)->get();
        $dailyKm = 45.0;
        if ($trips->count() >= 2) {
            $totalDist = (float) $trips->sum('distance_km');
            $minDate = Carbon::parse($trips->min('trip_date'));
            $maxDate = Carbon::parse($trips->max('trip_date'));
            $days = max(1, (int) abs($minDate->diffInDays($maxDate)));
            $dailyKm = max(10.0, round($totalDist / $days, 1));
        }

        return [
            'plate_number'        => $vehicle->plate_number,
            'vehicle_id'          => $vehicle->plate_number,
            'service_type'        => 'Periodic Fleet Inspection',
            'days_since_service'  => $daysSince,
            'km_since_service'    => $kmSince,
            'interval_km'         => 10000.0,
            'interval_months'     => 6.0,
            'daily_km'            => $dailyKm,
            'terrain_severity'    => 1.1, // corporate fleet slight severe multiplier
            'vehicle_age'         => $vehicleAge,
            'total_mileage'       => $mileage,
            'fuel_type'           => strtolower($vehicle->fuel_type ?? 'diesel'),
            'owner_type'          => 'corporate',
        ];
    }
}
