<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Appointment;
use App\Models\SparePart;
use App\Models\ServiceHistory;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Invoice;
use App\Models\JobType;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '6months');

        // Resolve time horizon
        $startDate = match ($period) {
            '30days'  => now()->subDays(30),
            '90days'  => now()->subDays(90),
            '6months' => now()->subMonths(6),
            'ytd'     => now()->startOfYear(),
            'all'     => null,
            default   => now()->subMonths(6),
        };

        // 1. Job cards by stage (Live workshop workload)
        $stages = ['received', 'diagnosing', 'waiting_parts', 'repairing', 'quality_check', 'completed'];
        $stageCounts = [];
        foreach ($stages as $stage) {
            $stageCounts[$stage] = JobCard::where('current_stage', $stage)->count();
        }

        // 2. Monthly revenue inflow (Dynamic 6 to 12 months)
        $numMonths = match ($period) {
            'ytd'   => max(6, now()->month),
            'all'   => 12,
            default => 6,
        };

        $months      = [];
        $revenue     = [];
        $monthlyJobs = [];
        for ($i = $numMonths - 1; $i >= 0; $i--) {
            $date          = now()->subMonths($i);
            $months[]      = $date->format('M Y');
            $revenue[]     = (float) ServiceHistory::whereYear('service_date', $date->year)
                ->whereMonth('service_date', $date->month)
                ->sum('cost');
            $monthlyJobs[] = ServiceHistory::whereYear('service_date', $date->year)
                ->whereMonth('service_date', $date->month)
                ->count();
        }

        // 3. Appointment Lifecycle & Intake Channels
        $apptQuery = Appointment::when($startDate, fn($q) => $q->where('date', '>=', $startDate->toDateString()));
        $apptPending   = (clone $apptQuery)->where('status', 'pending')->count();
        $apptConfirmed = (clone $apptQuery)->where('status', 'confirmed')->count();
        $apptCompleted = (clone $apptQuery)->where('status', 'completed')->count();
        $apptCancelled = (clone $apptQuery)->where('status', 'cancelled')->count();

        $walkinCount = (clone $apptQuery)->where('is_walkin', true)->count();
        $bookedCount = (clone $apptQuery)->where('is_walkin', false)->count();

        // 4. Financial & Operational KPIs
        $historyQuery = ServiceHistory::when($startDate, fn($q) => $q->where('service_date', '>=', $startDate->toDateString()));
        $totalRevenue = (float) (clone $historyQuery)->sum('cost');
        $allTimeRevenue = (float) ServiceHistory::sum('cost');

        $completedJobsQuery = JobCard::where('current_stage', 'completed')
            ->when($startDate, fn($q) => $q->where('updated_at', '>=', $startDate));
        $completedJobs = (clone $completedJobsQuery)->count();
        $allTimeCompleted = JobCard::where('current_stage', 'completed')->count();

        $avgJobValue = $completedJobs > 0 ? ($totalRevenue / $completedJobs) : 0;

        // Turnaround Time (in hours)
        $avgTurnaroundMinutes = JobCard::where('current_stage', 'completed')
            ->whereNotNull('completed_at')
            ->when($startDate, fn($q) => $q->where('completed_at', '>=', $startDate))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_min')
            ->value('avg_min') ?: 120;
        $avgTurnaroundHours = round($avgTurnaroundMinutes / 60, 1);

        // Invoicing & Receivables
        $invQuery = Invoice::when($startDate, fn($q) => $q->where('created_at', '>=', $startDate));
        $totalInvoiced    = (float) (clone $invQuery)->sum('total');
        $totalCollected   = (float) (clone $invQuery)->sum('amount_paid');
        $totalOutstanding = max(0, $totalInvoiced - $totalCollected);
        $collectionRate   = $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 1) : 100.0;
        $unpaidInvoicesCount = (clone $invQuery)->where('status', 'unpaid')->count();
        $partialInvoicesCount = (clone $invQuery)->where('status', 'partial')->count();

        // 5. Total counts for vehicle & accounts
        $totalVehicles  = Vehicle::count();
        $totalCustomers = User::whereIn('role', ['individual', 'corporate'])->count();
        $fleetVehiclesCount = Vehicle::whereHas('owner', fn($q) => $q->where('role', 'corporate'))->count();
        $individualVehiclesCount = $totalVehicles - $fleetVehiclesCount;

        // 6. Revenue by Service Category
        $categoryBreakdown = JobType::select('job_types.category')
            ->selectRaw('COUNT(job_cards.id) as jobs_count')
            ->selectRaw('COALESCE(SUM(job_cards.total_cost), 0) as total_revenue')
            ->join('job_cards', 'job_types.id', '=', 'job_cards.job_type_id')
            ->where('job_cards.current_stage', 'completed')
            ->when($startDate, fn($q) => $q->where('job_cards.updated_at', '>=', $startDate))
            ->groupBy('job_types.category')
            ->orderByDesc('total_revenue')
            ->get();

        $catTotal = $categoryBreakdown->sum('total_revenue');
        $categoryBreakdown->transform(function ($item) use ($catTotal) {
            $item->percentage = $catTotal > 0 ? round(($item->total_revenue / $catTotal) * 100, 1) : 0;
            return $item;
        });

        // 7. Technician Productivity Leaderboard
        $technicianLeaderboard = User::where('role', 'staff')
            ->withCount([
                'jobCards as completed_count' => function ($q) use ($startDate) {
                    $q->where('current_stage', 'completed')
                      ->when($startDate, fn($sq) => $sq->where('updated_at', '>=', $startDate));
                },
                'jobCards as active_count' => function ($q) {
                    $q->where('current_stage', '!=', 'completed');
                }
            ])
            ->withSum([
                'jobCards as revenue_generated' => function ($q) use ($startDate) {
                    $q->where('current_stage', 'completed')
                      ->when($startDate, fn($sq) => $sq->where('updated_at', '>=', $startDate));
                }
            ], 'total_cost')
            ->orderByDesc('revenue_generated')
            ->get();

        // 8. Top Corporate Fleet Accounts
        $topFleetClients = Company::withCount('vehicles')
            ->get()
            ->map(function ($company) use ($startDate) {
                $vehicleIds = Vehicle::whereHas('owner', fn($q) => $q->where('company_id', $company->id))->pluck('id');
                $jobsCount = JobCard::whereIn('vehicle_id', $vehicleIds)
                    ->where('current_stage', 'completed')
                    ->when($startDate, fn($q) => $q->where('updated_at', '>=', $startDate))
                    ->count();
                $totalSpend = (float) JobCard::whereIn('vehicle_id', $vehicleIds)
                    ->where('current_stage', 'completed')
                    ->when($startDate, fn($q) => $q->where('updated_at', '>=', $startDate))
                    ->sum('total_cost');
                $activeJobs = JobCard::whereIn('vehicle_id', $vehicleIds)
                    ->where('current_stage', '!=', 'completed')
                    ->count();

                return (object) [
                    'id'               => $company->id,
                    'name'             => $company->name,
                    'person_in_charge' => $company->person_in_charge,
                    'phone'            => $company->phone,
                    'vehicles_count'   => $company->vehicles_count,
                    'jobs_count'       => $jobsCount,
                    'total_spend'      => $totalSpend,
                    'active_jobs'      => $activeJobs,
                ];
            })
            ->sortByDesc('total_spend')
            ->values();

        // 9. Spare Parts Inventory Stock Health
        $lowStockParts = SparePart::whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->get();
        $totalPartsCount   = SparePart::count();
        $totalInventoryVal = (float) SparePart::sum(DB::raw('stock * unit_price'));

        // 10. Recent Completed Jobs
        $recentCompleted = JobCard::with(['vehicle.owner.company', 'staff', 'jobType', 'invoice'])
            ->where('current_stage', 'completed')
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('reports.index', compact(
            'period',
            'stageCounts', 'stages',
            'months', 'revenue', 'monthlyJobs',
            'apptPending', 'apptConfirmed', 'apptCompleted', 'apptCancelled',
            'walkinCount', 'bookedCount',
            'totalRevenue', 'allTimeRevenue', 'completedJobs', 'allTimeCompleted',
            'avgJobValue', 'avgTurnaroundHours',
            'totalInvoiced', 'totalCollected', 'totalOutstanding', 'collectionRate',
            'unpaidInvoicesCount', 'partialInvoicesCount',
            'totalVehicles', 'totalCustomers', 'fleetVehiclesCount', 'individualVehiclesCount',
            'categoryBreakdown',
            'technicianLeaderboard',
            'topFleetClients',
            'lowStockParts', 'totalPartsCount', 'totalInventoryVal',
            'recentCompleted'
        ));
    }

    /**
     * Streams CSV exports for Executive Summary, Invoices, Job Cards, Inventory, or Fleet History.
     * Includes UTF-8 BOM for Microsoft Excel / Sheets compatibility.
     */
    public function export(Request $request)
    {
        $type   = $request->get('type', 'summary');
        $period = $request->get('period', 'all');

        $startDate = match ($period) {
            '30days'  => now()->subDays(30),
            '90days'  => now()->subDays(90),
            '6months' => now()->subMonths(6),
            'ytd'     => now()->startOfYear(),
            default   => null,
        };

        $timestamp = now()->format('Ymd_His');

        return match ($type) {
            'invoices'  => $this->exportInvoices($startDate, $timestamp),
            'jobs'      => $this->exportJobs($startDate, $timestamp),
            'inventory' => $this->exportInventory($timestamp),
            'fleet'     => $this->exportFleetHistory($startDate, $timestamp),
            default     => $this->exportExecutiveSummary($startDate, $timestamp),
        };
    }

    /**
     * Helper to write CSV row with explicit PHP 8.4 compatible escape parameter.
     */
    protected function putCsvRow($out, array $row): void
    {
        fputcsv($out, $row, ',', '"', "\\");
    }

    /**
     * 1. Executive Workshop Analytics Summary CSV
     */
    protected function exportExecutiveSummary($startDate, string $timestamp)
    {
        $filename = "teraju_workshop_executive_summary_{$timestamp}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($startDate) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM

            $this->putCsvRow($out, ['TERAJU SETIA WORKSHOP — EXECUTIVE MANAGEMENT REPORT']);
            $this->putCsvRow($out, ['Generated At', now()->format('d M Y, h:i A')]);
            $this->putCsvRow($out, ['Scope', $startDate ? 'From ' . $startDate->format('d M Y') . ' to ' . now()->format('d M Y') : 'All Time Historical Data']);
            $this->putCsvRow($out, []);

            // Financial & Core Workshop Metrics
            $totalRev      = (float) ServiceHistory::when($startDate, fn($q) => $q->where('service_date', '>=', $startDate))->sum('cost');
            $completedJobs = JobCard::where('current_stage', 'completed')->when($startDate, fn($q) => $q->where('updated_at', '>=', $startDate))->count();
            $avgTicket     = $completedJobs > 0 ? ($totalRev / $completedJobs) : 0;
            $invoiced      = (float) Invoice::when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))->sum('total');
            $collected     = (float) Invoice::when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))->sum('amount_paid');
            $outstanding   = max(0, $invoiced - $collected);
            $collRate      = $invoiced > 0 ? round(($collected / $invoiced) * 100, 1) : 100.0;
            $totalVehicles = Vehicle::count();
            $fleetVehicles = Vehicle::whereHas('owner', fn($q) => $q->where('role', 'corporate'))->count();

            $this->putCsvRow($out, ['--- KEY PERFORMANCE INDICATORS ---']);
            $this->putCsvRow($out, ['Gross Workshop Revenue (RM)', number_format($totalRev, 2, '.', '')]);
            $this->putCsvRow($out, ['Total Invoiced Billing (RM)', number_format($invoiced, 2, '.', '')]);
            $this->putCsvRow($out, ['Total Revenue Collected (RM)', number_format($collected, 2, '.', '')]);
            $this->putCsvRow($out, ['Outstanding Receivables (RM)', number_format($outstanding, 2, '.', '')]);
            $this->putCsvRow($out, ['Invoice Collection Rate (%)', $collRate . '%']);
            $this->putCsvRow($out, ['Completed Work Orders', $completedJobs]);
            $this->putCsvRow($out, ['Average Repair Ticket (RM)', number_format($avgTicket, 2, '.', '')]);
            $this->putCsvRow($out, ['Total Serviced Vehicles on Record', $totalVehicles]);
            $this->putCsvRow($out, ['Corporate Fleet Vehicles', $fleetVehicles]);
            $this->putCsvRow($out, ['Retail Individual Vehicles', $totalVehicles - $fleetVehicles]);
            $this->putCsvRow($out, []);

            // Monthly Revenue Breakdown (Last 6 Months)
            $this->putCsvRow($out, ['--- MONTHLY REVENUE & WORK ORDER VOLUME ---']);
            $this->putCsvRow($out, ['Month', 'Completed Jobs', 'Revenue (RM)']);
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $mRev = ServiceHistory::whereYear('service_date', $date->year)->whereMonth('service_date', $date->month)->sum('cost');
                $mCnt = ServiceHistory::whereYear('service_date', $date->year)->whereMonth('service_date', $date->month)->count();
                $this->putCsvRow($out, [$date->format('M Y'), $mCnt, number_format($mRev, 2, '.', '')]);
            }
            $this->putCsvRow($out, []);

            // Service Category Breakdown
            $this->putCsvRow($out, ['--- REVENUE BY SERVICE CATEGORY ---']);
            $this->putCsvRow($out, ['Category', 'Work Orders', 'Total Revenue (RM)', 'Share (%)']);
            $categories = JobType::select('job_types.category')
                ->selectRaw('COUNT(job_cards.id) as jobs_count, COALESCE(SUM(job_cards.total_cost), 0) as total_revenue')
                ->join('job_cards', 'job_types.id', '=', 'job_cards.job_type_id')
                ->where('job_cards.current_stage', 'completed')
                ->when($startDate, fn($q) => $q->where('job_cards.updated_at', '>=', $startDate))
                ->groupBy('job_types.category')
                ->orderByDesc('total_revenue')
                ->get();

            foreach ($categories as $cat) {
                $pct = $totalRev > 0 ? round(($cat->total_revenue / $totalRev) * 100, 1) : 0;
                $this->putCsvRow($out, [$cat->category, $cat->jobs_count, number_format($cat->total_revenue, 2, '.', ''), $pct . '%']);
            }
            $this->putCsvRow($out, []);

            // Current Workshop Pipeline
            $this->putCsvRow($out, ['--- CURRENT WORKSHOP BAY WORKLOAD ---']);
            $this->putCsvRow($out, ['Workflow Stage', 'Active Job Cards']);
            $stages = ['received' => 'Received / Pending Inspection', 'diagnosing' => 'Diagnostic Assessment', 'waiting_parts' => 'Waiting for Spare Parts', 'repairing' => 'Under Active Repair', 'quality_check' => 'Final Quality Check (QC)', 'completed' => 'Completed / Delivered'];
            foreach ($stages as $key => $label) {
                $this->putCsvRow($out, [$label, JobCard::where('current_stage', $key)->count()]);
            }
            $this->putCsvRow($out, []);

            // Top Fleet Clients
            $this->putCsvRow($out, ['--- CORPORATE FLEET CLIENT EXPENDITURE ---']);
            $this->putCsvRow($out, ['Company Name', 'Contact Person', 'Phone', 'Fleet Size', 'Completed Jobs', 'Total Spend (RM)']);
            $companies = Company::withCount('vehicles')->get();
            foreach ($companies as $comp) {
                $vIds = Vehicle::whereHas('owner', fn($q) => $q->where('company_id', $comp->id))->pluck('id');
                $cJobs = JobCard::whereIn('vehicle_id', $vIds)->where('current_stage', 'completed')->when($startDate, fn($q) => $q->where('updated_at', '>=', $startDate))->count();
                $cSpend = (float) JobCard::whereIn('vehicle_id', $vIds)->where('current_stage', 'completed')->when($startDate, fn($q) => $q->where('updated_at', '>=', $startDate))->sum('total_cost');
                $this->putCsvRow($out, [
                    $comp->name,
                    $comp->person_in_charge ?? '-',
                    $comp->phone ?? '-',
                    $comp->vehicles_count,
                    $cJobs,
                    number_format($cSpend, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }

    /**
     * 2. Financial Invoices & Payments Ledger CSV
     */
    protected function exportInvoices($startDate, string $timestamp)
    {
        $filename = "teraju_invoices_ledger_{$timestamp}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($startDate) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $this->putCsvRow($out, [
                'Invoice #',
                'Invoice Date',
                'Due Date',
                'Customer / Company Name',
                'Subtotal (RM)',
                'SST Rate (%)',
                'SST Tax Amount (RM)',
                'Total Amount (RM)',
                'Amount Paid (RM)',
                'Balance Due (RM)',
                'Status',
                'Payment Method',
                'Payment Reference',
                'Notes',
            ]);

            $invoices = Invoice::with(['payments', 'jobCard.vehicle.owner.company'])
                ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
                ->latest('created_at')
                ->get();

            foreach ($invoices as $inv) {
                $payMethod = $inv->payments->pluck('method')->map(fn($m) => ucwords(str_replace('_', ' ', $m)))->join(', ') ?: '-';
                $payRef    = $inv->payments->pluck('reference_no')->filter()->join(', ') ?: '-';

                $this->putCsvRow($out, [
                    $inv->invoice_number,
                    $inv->created_at->format('Y-m-d'),
                    $inv->due_date ? Carbon::parse($inv->due_date)->format('Y-m-d') : '-',
                    $inv->customer_name,
                    number_format($inv->subtotal, 2, '.', ''),
                    number_format($inv->tax_rate, 2, '.', '') . '%',
                    number_format($inv->tax_amount, 2, '.', ''),
                    number_format($inv->total, 2, '.', ''),
                    number_format($inv->amount_paid, 2, '.', ''),
                    number_format($inv->balance, 2, '.', ''),
                    strtoupper($inv->status),
                    $payMethod,
                    $payRef,
                    $inv->notes ?? '-',
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }

    /**
     * 3. Completed Work Orders & Job Cards CSV
     */
    protected function exportJobs($startDate, string $timestamp)
    {
        $filename = "teraju_job_cards_report_{$timestamp}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($startDate) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $this->putCsvRow($out, [
                'Job Card ID',
                'Vehicle Plate',
                'Make & Model',
                'Client / Account',
                'Account Type',
                'Service Type',
                'Category',
                'Lead Mechanic',
                'Current Stage',
                'Created Date',
                'Completed Date',
                'Turnaround (Hours)',
                'Parts Total (RM)',
                'Labour Total (RM)',
                'Grand Total (RM)',
                'Diagnosis & Issues',
                'Technician Notes',
            ]);

            $jobs = JobCard::with(['vehicle.owner.company', 'staff', 'jobType', 'parts', 'labourCharges'])
                ->when($startDate, fn($q) => $q->where('updated_at', '>=', $startDate))
                ->latest('id')
                ->get();

            foreach ($jobs as $job) {
                $veh        = $job->vehicle;
                $clientName = $veh?->owner?->company?->name ?? $veh?->owner?->name ?? 'Walk-in';
                $acctType   = $veh?->isFleet() ? 'Corporate Fleet' : 'Individual Retail';
                $partsCost  = $job->parts->sum(fn($p) => $p->quantity * $p->unit_price);
                $labourCost = $job->labourCharges->sum('charge');

                $tatHours = '-';
                if ($job->completed_at && $job->created_at) {
                    $tatHours = round($job->created_at->diffInMinutes($job->completed_at) / 60, 1);
                }

                $this->putCsvRow($out, [
                    '#JC-' . str_pad($job->id, 5, '0', STR_PAD_LEFT),
                    $veh->plate_number ?? '-',
                    ($veh ? trim(($veh->brand ?? '') . ' ' . ($veh->model ?? '')) : '-'),
                    $clientName,
                    $acctType,
                    $job->jobType->name ?? 'General Service',
                    $job->jobType->category ?? '-',
                    $job->staff->name ?? '-',
                    strtoupper(str_replace('_', ' ', $job->current_stage)),
                    $job->created_at->format('Y-m-d H:i'),
                    $job->completed_at ? $job->completed_at->format('Y-m-d H:i') : '-',
                    $tatHours,
                    number_format($partsCost, 2, '.', ''),
                    number_format($labourCost, 2, '.', ''),
                    number_format($job->total_cost, 2, '.', ''),
                    $job->diagnosis ?? '-',
                    $job->technician_notes ?? '-',
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }

    /**
     * 4. Spare Parts Inventory & Stock Valuation CSV
     */
    protected function exportInventory(string $timestamp)
    {
        $filename = "teraju_spare_parts_inventory_{$timestamp}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $this->putCsvRow($out, [
                'Part Number',
                'Item Description',
                'Category',
                'Brand',
                'Current Stock',
                'Min Threshold',
                'Max Threshold',
                'Unit Price (RM)',
                'Stock Valuation (RM)',
                'Status',
                'Supplier Name',
                'Supplier Contact',
            ]);

            $parts = SparePart::orderBy('category')->orderBy('name')->get();

            foreach ($parts as $part) {
                $status = 'Adequate';
                if ($part->stock <= 0) {
                    $status = 'OUT OF STOCK';
                } elseif ($part->stock <= $part->min_stock) {
                    $status = 'LOW STOCK WARNING';
                }

                $valuation = $part->stock * $part->unit_price;

                $this->putCsvRow($out, [
                    $part->part_number ?? '-',
                    $part->name,
                    $part->category ?? '-',
                    $part->brand ?? '-',
                    $part->stock,
                    $part->min_stock,
                    $part->max_stock ?? '-',
                    number_format($part->unit_price, 2, '.', ''),
                    number_format($valuation, 2, '.', ''),
                    $status,
                    $part->supplier_name ?? '-',
                    $part->supplier_contact ?? '-',
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }

    /**
     * 5. Fleet Vehicle Maintenance History CSV
     */
    protected function exportFleetHistory($startDate, string $timestamp)
    {
        $filename = "teraju_fleet_maintenance_history_{$timestamp}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($startDate) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $this->putCsvRow($out, [
                'Service Date',
                'Plate Number',
                'Fleet Company',
                'Person in Charge',
                'Make & Model',
                'Mileage (km)',
                'Service Description',
                'Lead Mechanic',
                'Cost (RM)',
                'Job Card ID',
            ]);

            $histories = ServiceHistory::with(['vehicle.owner.company', 'jobCard.staff'])
                ->when($startDate, fn($q) => $q->where('service_date', '>=', $startDate->toDateString()))
                ->latest('service_date')
                ->get();

            foreach ($histories as $sh) {
                $v = $sh->vehicle;
                $comp = $v?->owner?->company;

                $this->putCsvRow($out, [
                    $sh->service_date,
                    $v->plate_number ?? '-',
                    $comp?->name ?? ($v?->owner?->name ?? 'Individual Customer'),
                    $comp?->person_in_charge ?? ($v?->owner?->name ?? '-'),
                    $v ? trim(($v->brand ?? '') . ' ' . ($v->model ?? '')) : '-',
                    $v->mileage ?? '-',
                    $sh->description ?? '-',
                    $sh->jobCard?->staff?->name ?? '-',
                    number_format($sh->cost, 2, '.', ''),
                    $sh->job_card_id ? '#JC-' . str_pad($sh->job_card_id, 5, '0', STR_PAD_LEFT) : '-',
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }
}
