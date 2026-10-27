<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Appointment;
use App\Models\ServiceHistory;
use App\Models\MaintenanceAlert;
use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index() {
        $user      = auth()->user();
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

        $totalServices = ServiceHistory::whereIn('vehicle_id', $vehicleIds)->count();

        $totalSpent = ServiceHistory::whereIn('vehicle_id', $vehicleIds)->sum('cost');

        // Maintenance alerts across the whole fleet
        $maintenanceAlerts = MaintenanceAlert::whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle')
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

    public function isSecondaryPic(): bool {
    return $this->role === 'corporate' && $this->pic_role === 'secondary';
    }

    public function isPrimaryPic(): bool {
        return $this->role === 'corporate' && $this->pic_role === 'primary';
    }
    
    public function company() {
    $user      = auth()->user();
    $companyId = $user->company_id;

    $company = \App\Models\Company::find($companyId);

    $pics = \App\Models\User::where('company_id', $companyId)
        ->orderBy('name')
        ->get();

    $requests = \App\Models\AccountRequest::where('company_id', $companyId)
        ->with(['requester', 'targetUser'])
        ->latest()
        ->get();

    return view('client.company', compact('company', 'pics', 'requests'));
}

    public function statement(Request $request) {
        $user = auth()->user();
        $companyId = $user->company_id;
        $company = $companyId ? Company::find($companyId) : null;

        $companyUserIds = $companyId ? User::where('company_id', $companyId)->pluck('id') : collect([$user->id]);
        $vehicles = Vehicle::whereIn('user_id', $companyUserIds)->with('owner')->orderBy('plate_number')->get();
        $vehicleIds = $vehicles->pluck('id');

        $month = $request->input('month', now()->format('Y-m'));

        $historyQuery = ServiceHistory::whereIn('vehicle_id', $vehicleIds)->with(['vehicle', 'jobCard']);
        $invoicesQuery = Invoice::whereHas('jobCard.appointment', fn($q) => $q->whereIn('user_id', $companyUserIds))
            ->with(['jobCard.vehicle', 'jobCard.appointment.user', 'payments']);

        if ($month !== 'all') {
            try {
                $period = Carbon::createFromFormat('Y-m', $month);
                $start = $period->copy()->startOfMonth()->toDateString();
                $end = $period->copy()->endOfMonth()->toDateString();

                $historyQuery->whereBetween('service_date', [$start, $end]);
                $invoicesQuery->whereBetween('created_at', [
                    $period->copy()->startOfMonth()->startOfDay(),
                    $period->copy()->endOfMonth()->endOfDay()
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

        $totalInvoiced      = (float) $invoices->sum('total');
        $totalPaid          = (float) $invoices->sum('amount_paid');
        $outstandingBalance = (float) $invoices->sum(fn($i) => $i->balance);
        $totalServices      = $serviceHistory->count();

        // Group by vehicle for summary
        $vehicleSummary = $vehicles->map(function ($vehicle) use ($serviceHistory, $invoices) {
            $vHistory = $serviceHistory->where('vehicle_id', $vehicle->id);
            $vInvoices = $invoices->filter(fn($inv) => $inv->jobCard?->vehicle_id === $vehicle->id);
            $spend = (float) $vHistory->sum('cost');
            if ($spend == 0 && $vInvoices->isNotEmpty()) {
                $spend = (float) $vInvoices->sum('total');
            }
            return [
                'vehicle'       => $vehicle,
                'service_count' => $vHistory->count(),
                'total_spend'   => $spend,
                'last_service'  => $vHistory->sortByDesc('service_date')->first()?->service_date,
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
}