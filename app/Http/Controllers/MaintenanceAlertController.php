<?php
namespace App\Http\Controllers;

use App\Models\MaintenanceAlert;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class MaintenanceAlertController extends Controller
{
    public function index() {
    $user = auth()->user();

    if ($user->role === 'admin') {
        $alerts   = MaintenanceAlert::with('vehicle.owner')->latest()->get();
        $vehicles = Vehicle::with('owner')->orderBy('plate_number')->get();

    } elseif ($user->role === 'corporate') {
        $companyUserIds = \App\Models\User::where('company_id', $user->company_id)->pluck('id');
        $vehicleIds     = Vehicle::whereIn('user_id', $companyUserIds)->pluck('id');
        $alerts         = MaintenanceAlert::with('vehicle')
                            ->whereIn('vehicle_id', $vehicleIds)
                            ->latest()->get();
        $vehicles       = Vehicle::whereIn('user_id', $companyUserIds)
                            ->orderBy('plate_number')->get();

    } else {
        // individual
        $vehicleIds = Vehicle::where('user_id', $user->id)->pluck('id');
        $alerts     = MaintenanceAlert::with('vehicle')
                        ->whereIn('vehicle_id', $vehicleIds)
                        ->latest()->get();
        $vehicles   = Vehicle::where('user_id', $user->id)->get();
    }

    return view('maintenance.alerts', compact('alerts', 'vehicles'));
    }

    public function store(Request $request) {
        $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'alert_type'     => 'required|string|max:100',
            'urgency'        => 'required|in:low,medium,high',
            'recommendation' => 'required|string|max:500',
        ]);

        MaintenanceAlert::create($request->only([
            'vehicle_id', 'alert_type', 'urgency', 'recommendation'
        ]));

        return back()->with('success', 'Maintenance alert created.');
    }

    public function markRead(MaintenanceAlert $alert) {
        // Gate: only owner's vehicle or admin
        $user = auth()->user();
        if ($user->role !== 'admin') {
            $ownsVehicle = Vehicle::where('id', $alert->vehicle_id)
                ->where('user_id', $user->id)->exists();
            abort_unless($ownsVehicle, 403);
        }

        $alert->update(['is_read' => true]);
        return back()->with('success', 'Alert marked as read.');
    }
}
