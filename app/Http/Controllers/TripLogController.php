<?php
namespace App\Http\Controllers;

use App\Models\TripLog;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\MaintenancePredictionService;
use Illuminate\Http\Request;

class TripLogController extends Controller
{
    /**
     * Display a listing of trip logs within the authenticated user's scope.
     * Corporate users see all trip logs across their company's fleet.
     * Individuals see only trip logs for their own vehicles.
     * Admins see all trip logs system-wide.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $predictionService = app(MaintenancePredictionService::class);

        // Determine accessible vehicles based on RBAC
        if ($user->role === 'admin') {
            $vehicles = Vehicle::with('owner')->orderBy('plate_number')->get();
            $baseRoute = '/admin';
        } elseif ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $vehicles = Vehicle::whereIn('user_id', $companyUserIds)
                ->with('owner')
                ->orderBy('plate_number')
                ->get();
            $baseRoute = '/client';
        } else {
            $vehicles = Vehicle::where('user_id', $user->id)
                ->orderBy('plate_number')
                ->get();
            $baseRoute = '/customer';
        }

        $vehicleIds = $vehicles->pluck('id');

        // Build query
        $query = TripLog::whereIn('vehicle_id', $vehicleIds)->with('vehicle.owner');

        $selectedVehicleId = $request->input('vehicle_id');
        if ($selectedVehicleId && $vehicleIds->contains($selectedVehicleId)) {
            $query->where('vehicle_id', $selectedVehicleId);
        } else {
            $selectedVehicleId = null;
        }

        $selectedTerrain = $request->input('terrain_type');
        if ($selectedTerrain && in_array($selectedTerrain, ['urban', 'highway', 'rural', 'mountain', 'mixed'], true)) {
            $query->where('terrain_type', $selectedTerrain);
        } else {
            $selectedTerrain = null;
        }

        $tripLogs = $query->latest('trip_date')->latest('id')->paginate(15)->withQueryString();

        // Calculate aggregate statistics
        $totalTrips    = TripLog::whereIn('vehicle_id', $vehicleIds)->count();
        $totalDistance = (float) TripLog::whereIn('vehicle_id', $vehicleIds)->sum('distance_km');

        // Usage rates per vehicle (for transparency in the predictive engine)
        $vehicleStats = [];
        foreach ($vehicles as $v) {
            $vehicleStats[$v->id] = $predictionService->getUsageStats($v);
        }

        $selectedVehicle = $selectedVehicleId ? $vehicles->firstWhere('id', $selectedVehicleId) : null;
        $activeUsageStats = $selectedVehicle ? ($vehicleStats[$selectedVehicle->id] ?? null) : null;

        $isSecondaryPic = $user->role === 'corporate' && $user->isSecondaryPic();

        return view('trip-logs.index', compact(
            'tripLogs',
            'vehicles',
            'selectedVehicleId',
            'selectedVehicle',
            'selectedTerrain',
            'totalTrips',
            'totalDistance',
            'vehicleStats',
            'activeUsageStats',
            'baseRoute',
            'isSecondaryPic'
        ));
    }

    /**
     * Store a newly created trip log.
     * Enforces vehicle ownership guards and corporate PIC tier permissions.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Corporate Viewer-tier PICs cannot add records
        if ($user->role === 'corporate' && $user->isSecondaryPic()) {
            return back()->with('error', 'Secondary PIC (Viewer) cannot log trips. Contact your Primary PIC.');
        }

        $request->validate([
            'vehicle_id'   => 'required|exists:vehicles,id',
            'trip_date'    => 'required|date|before_or_equal:today',
            'distance_km'  => 'required|numeric|min:0.1|max:5000',
            'terrain_type' => 'required|string|in:urban,highway,rural,mountain,mixed',
            'notes'        => 'nullable|string|max:500',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        // Security check: verify ownership/access
        $this->authorizeVehicleAccess($vehicle);

        $tripLog = TripLog::create([
            'vehicle_id'   => $vehicle->id,
            'trip_date'    => $request->trip_date,
            'distance_km'  => $request->distance_km,
            'terrain_type' => $request->terrain_type,
            'notes'        => $request->notes,
        ]);

        // Automatically update odometer/mileage on the vehicle if requested
        if ($request->boolean('update_mileage', true)) {
            $vehicle->increment('mileage', (int) round($request->distance_km));
        }

        // Immediately refresh predictive maintenance alerts with the new usage rate
        app(MaintenancePredictionService::class)->runAll();

        ActivityLog::record(
            'trip_log.created',
            "Logged {$tripLog->distance_km} km trip ({$tripLog->terrain_type}) for {$vehicle->plate_number}",
            $tripLog
        );

        return back()->with('success', "Trip of {$tripLog->distance_km} km recorded for {$vehicle->plate_number}. Maintenance predictions updated.");
    }

    /**
     * Remove the specified trip log.
     * Enforces vehicle ownership guards and corporate PIC tier permissions.
     */
    public function destroy(TripLog $tripLog)
    {
        $user = auth()->user();

        // Corporate Viewer-tier PICs cannot delete records
        if ($user->role === 'corporate' && $user->isSecondaryPic()) {
            return back()->with('error', 'Secondary PIC (Viewer) cannot delete trip logs. Contact your Primary PIC.');
        }

        $vehicle = $tripLog->vehicle;
        $this->authorizeVehicleAccess($vehicle);

        $plate = $vehicle->plate_number ?? 'vehicle';
        $dist  = $tripLog->distance_km;

        $tripLog->delete();

        // Recalculate predictions with updated trip history
        app(MaintenancePredictionService::class)->runAll();

        ActivityLog::record(
            'trip_log.deleted',
            "Deleted {$dist} km trip log for {$plate}",
            $vehicle
        );

        return back()->with('success', "Trip log ({$dist} km) deleted. Maintenance predictions refreshed.");
    }

    /**
     * Authorize that the current user has rights to operate on the vehicle.
     */
    private function authorizeVehicleAccess(Vehicle $vehicle): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            abort_unless($companyUserIds->contains($vehicle->user_id), 403, 'You do not have access to this vehicle.');
            return;
        }

        abort_unless($vehicle->user_id === $user->id, 403, 'You do not have access to this vehicle.');
    }
}
