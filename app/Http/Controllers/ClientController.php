<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Appointment;
use App\Models\ServiceHistory;
use App\Models\MaintenanceAlert;
use App\Models\User;

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
            ->orderByRaw("FIELD(urgency,'high','medium','low')")
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
}