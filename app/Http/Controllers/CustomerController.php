<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Appointment;
use App\Models\ServiceHistory;
use App\Models\MaintenanceAlert;

class CustomerController extends Controller
{
    public function index() {
        $user     = auth()->user();
        $vehicles = Vehicle::where('user_id', $user->id)->get();

        $appointments = Appointment::where('user_id', $user->id)
            ->with(['vehicle', 'jobCard'])
            ->latest()->take(5)->get();

        $vehicleIds = $vehicles->pluck('id');

        $serviceHistory = ServiceHistory::with('vehicle')
            ->whereIn('vehicle_id', $vehicleIds)
            ->latest('service_date')
            ->take(10)
            ->get();

        $totalServices = ServiceHistory::whereIn('vehicle_id', $vehicleIds)->count();

        // Maintenance alerts for this customer's vehicles
        $maintenanceAlerts = MaintenanceAlert::with('vehicle')
            ->whereIn('vehicle_id', $vehicleIds)
            ->orderByRaw("FIELD(urgency,'high','medium','low')")
            ->orderBy('is_read')
            ->latest()
            ->get();

        return view('customer.dashboard', compact(
            'vehicles', 'appointments', 'serviceHistory',
            'totalServices', 'maintenanceAlerts'
        ));
    }
}
