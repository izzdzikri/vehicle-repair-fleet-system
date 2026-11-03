<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\MaintenancePredictionService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = trim((string) $request->input('search'));

        if ($user->role === 'admin') {
            $query = Vehicle::with(['owner.company'])->orderBy('plate_number');
        } elseif ($user->role === 'corporate') {
            abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $query = Vehicle::whereIn('user_id', $companyUserIds)->with(['owner.company'])->orderBy('plate_number');
        } else {
            $query = Vehicle::where('user_id', $user->id)->with(['owner.company'])->orderBy('plate_number');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%")
                            ->orWhereHas('company', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $vehicles = $query->paginate(20)->withQueryString();

        return view('vehicles.index', compact('vehicles', 'search'));
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorizeVehicleAccess($vehicle);

        $vehicle->load([
            'owner.company',
            'serviceHistory.jobCard',
            'appointments' => fn ($q) => $q->latest()->take(10),
            'maintenanceAlerts' => fn ($q) => $q->latest()->take(5),
            'tripLogs' => fn ($q) => $q->latest('trip_date')->latest('id')->take(10),
        ]);

        $totalVisits = $vehicle->serviceHistory->count();
        $totalSpent = $vehicle->serviceHistory->sum('cost');
        $firstService = $vehicle->serviceHistory->last();
        $lastService = $vehicle->serviceHistory->first();

        $predictionService = app(MaintenancePredictionService::class);
        $usageStats = $predictionService->getUsageStats($vehicle);
        $telemetry = $predictionService->getVehicleTelemetry($vehicle);
        $isSecondaryPic = auth()->user()->role === 'corporate' && auth()->user()->isSecondaryPic();

        return view('vehicles.show', compact(
            'vehicle', 'totalVisits', 'totalSpent', 'firstService', 'lastService', 'usageStats', 'telemetry', 'isSecondaryPic'
        ));
    }

    public function create()
    {
        $owners = null;
        if (auth()->user()->role === 'admin') {
            $owners = User::whereIn('role', ['individual', 'corporate'])
                ->with('company')
                ->orderBy('role')
                ->orderBy('name')
                ->get();
        }

        return view('vehicles.create', compact('owners'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'corporate' && auth()->user()->isSecondaryPic()) {
            return back()->with('error', 'Secondary PIC (Viewer) cannot add vehicles. Contact your Primary PIC.');
        }

        $request->validate([
            'plate_number' => 'required|unique:vehicles',
            'brand' => 'required',
            'model' => 'required',
            'year' => 'required|integer|min:1990|max:2030',
        ]);

        $userId = auth()->user()->role === 'admin' && $request->user_id
            ? $request->user_id
            : auth()->id();

        Vehicle::create([
            'user_id' => $userId,
            'plate_number' => strtoupper($request->plate_number),
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'mileage' => $request->mileage ?? 0,
        ]);

        return redirect()->back()->with('success', 'Vehicle added.');
    }

    public function edit(Vehicle $vehicle)
    {
        abort_unless(auth()->user()->can('update', $vehicle), 403, 'You do not have permission to edit this vehicle.');
        $vehicle->load('owner.company');

        $owners = null;
        if (auth()->user()->role === 'admin') {
            $owners = User::whereIn('role', ['individual', 'corporate'])
                ->with('company')
                ->orderBy('role')
                ->orderBy('name')
                ->get();
        }

        return view('vehicles.edit', compact('vehicle', 'owners'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeVehicleAccess($vehicle);
        abort_unless(auth()->user()->can('update', $vehicle), 403, 'You do not have permission to update this vehicle.');

        if (auth()->user()->role === 'corporate' && auth()->user()->isSecondaryPic()) {
            return back()->with('error', 'Secondary PIC (Viewer) cannot edit vehicles. Contact your Primary PIC.');
        }

        $request->validate([
            'brand' => 'required',
            'model' => 'required',
            'year' => 'required|integer|min:1990|max:2030',
            'mileage' => 'nullable|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $data = [
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'mileage' => $request->mileage ?? $vehicle->mileage,
        ];

        if (auth()->user()->role === 'admin' && $request->filled('user_id')) {
            $data['user_id'] = $request->user_id;
        }

        $vehicle->update($data);

        return back()->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeVehicleAccess($vehicle);
        abort_unless(auth()->user()->can('delete', $vehicle), 403, 'You do not have permission to delete this vehicle.');

        if (auth()->user()->role === 'corporate' && auth()->user()->isSecondaryPic()) {
            return back()->with('error', 'Secondary PIC (Viewer) cannot delete vehicles. Contact your Primary PIC.');
        }

        if ($vehicle->jobCards()->where('current_stage', '!=', 'completed')->exists()) {
            return back()->with('error', 'Cannot delete vehicle with active job cards in progress.');
        }

        if (Invoice::whereHas('jobCard', fn ($q) => $q->where('vehicle_id', $vehicle->id))->where('status', '!=', 'paid')->exists()) {
            return back()->with('error', 'Cannot delete vehicle with outstanding unpaid invoices.');
        }

        $vehicle->delete();

        return redirect()->back()->with('success', 'Vehicle deleted.');
    }

    /**
     * Ensure the current user is allowed to view/edit/delete this vehicle.
     * Centralized via VehiclePolicy.
     */
    private function authorizeVehicleAccess(Vehicle $vehicle): void
    {
        abort_unless(auth()->user()->can('view', $vehicle), 403, 'You do not have access to this vehicle.');
    }
}
