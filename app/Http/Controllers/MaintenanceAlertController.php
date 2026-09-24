<?php
namespace App\Http\Controllers;

use App\Models\MaintenanceAlert;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Notification;
use App\Services\MaintenancePredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        $alert = MaintenanceAlert::create($request->only([
            'vehicle_id', 'alert_type', 'urgency', 'recommendation'
        ]) + ['source' => 'manual']);

        $vehicle = Vehicle::with('owner')->find($request->vehicle_id);
        if ($vehicle && $vehicle->owner) {
            $message = "{$vehicle->plate_number}: {$alert->alert_type} — {$alert->recommendation}";
            if ($vehicle->owner->role === 'corporate' && $vehicle->owner->company_id) {
                $companyUserIds = User::where('company_id', $vehicle->owner->company_id)->pluck('id');
                Notification::sendToMany($companyUserIds, 'Maintenance Alert', $message, '/client/maintenance');
            } elseif ($vehicle->owner->role === 'individual') {
                Notification::send($vehicle->owner->id, 'Maintenance Alert', $message, '/customer/maintenance');
            }
        }

        return back()->with('success', 'Maintenance alert created.');
    }

    public function markRead(MaintenanceAlert $alert) {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            $vehicle = Vehicle::find($alert->vehicle_id);
            $allowed = false;

            if ($vehicle) {
                if ($user->role === 'corporate') {
                    $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
                    $allowed = $companyUserIds->contains($vehicle->user_id);
                } else {
                    $allowed = $vehicle->user_id === $user->id;
                }
            }

            abort_unless($allowed, 403);
        }

        $alert->update(['is_read' => true]);
        return back()->with('success', 'Alert marked as read.');
    }

    /**
     * Admin-triggered manual re-run of the predictive scan — bypasses
     * the dashboard's throttle cache so a change (new service history,
     * updated job type intervals) shows up immediately without waiting
     * for the next throttled window or scheduled run.
     */
    public function runPredictions() {
        $touched = app(MaintenancePredictionService::class)->runAll();
        Cache::forget('maintenance_predictions_autorun');

        return back()->with('success', "Predictive scan complete — {$touched} alert(s) created or refreshed.");
    }
}