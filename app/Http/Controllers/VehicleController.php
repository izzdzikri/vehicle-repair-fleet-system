<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request) {
        $user   = auth()->user();
        $search = trim((string) $request->input('search'));

        if ($user->role === 'admin') {
            $query = Vehicle::with('owner')->orderBy('plate_number');
        } elseif ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $query = Vehicle::whereIn('user_id', $companyUserIds)->with('owner')->orderBy('plate_number');
        } else {
            $query = Vehicle::where('user_id', $user->id)->with('owner')->orderBy('plate_number');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->paginate(20)->withQueryString();

        return view('vehicles.index', compact('vehicles', 'search'));
    }

    public function show(Vehicle $vehicle) {
        $this->authorizeVehicleAccess($vehicle);

        $vehicle->load([
            'owner',
            'serviceHistory.jobCard',
            'appointments' => fn($q) => $q->latest()->take(10),
            'maintenanceAlerts' => fn($q) => $q->latest()->take(5),
        ]);

        $totalVisits  = $vehicle->serviceHistory->count();
        $totalSpent   = $vehicle->serviceHistory->sum('cost');
        $firstService = $vehicle->serviceHistory->last();
        $lastService  = $vehicle->serviceHistory->first();

        return view('vehicles.show', compact(
            'vehicle','totalVisits','totalSpent','firstService','lastService'
        ));
    }

    public function create() {
        $owners = null;
        if (auth()->user()->role === 'admin') {
            $owners = User::whereIn('role',['individual','corporate'])->get();
        }
        return view('vehicles.create', compact('owners'));
    }

    public function store(Request $request) {
        if (auth()->user()->role === 'corporate' && auth()->user()->isSecondaryPic()) {
            return back()->with('error', 'Secondary PIC (Viewer) cannot add vehicles. Contact your Primary PIC.');
        }

        $request->validate([
            'plate_number' => 'required|unique:vehicles',
            'brand'        => 'required',
            'model'        => 'required',
            'year'         => 'required|integer|min:1990|max:2030',
        ]);

        $userId = auth()->user()->role === 'admin' && $request->user_id
            ? $request->user_id
            : auth()->id();

        Vehicle::create([
            'user_id'      => $userId,
            'plate_number' => strtoupper($request->plate_number),
            'brand'        => $request->brand,
            'model'        => $request->model,
            'year'         => $request->year,
            'mileage'      => $request->mileage ?? 0,
        ]);

        return redirect()->back()->with('success', 'Vehicle added.');
    }

    public function edit(Vehicle $vehicle) {
        $this->authorizeVehicleAccess($vehicle);
        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle) {
        $this->authorizeVehicleAccess($vehicle);

        $request->validate([
            'brand'   => 'required',
            'model'   => 'required',
            'year'    => 'required|integer|min:1990|max:2030',
            'mileage' => 'nullable|numeric|min:0',
        ]);

        $vehicle->update([
            'brand'   => $request->brand,
            'model'   => $request->model,
            'year'    => $request->year,
            'mileage' => $request->mileage ?? $vehicle->mileage,
        ]);

        return back()->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle) {
        $this->authorizeVehicleAccess($vehicle);

        if (auth()->user()->role === 'corporate' && auth()->user()->isSecondaryPic()) {
            return back()->with('error', 'Secondary PIC (Viewer) cannot delete vehicles. Contact your Primary PIC.');
        }

        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle deleted.');
    }

    /**
     * Ensure the current user is allowed to view/edit/delete this vehicle.
     * Admins can access everything; corporate users can access any vehicle
     * owned by any PIC in their own company; individuals can only access
     * their own vehicles.
     */
    private function authorizeVehicleAccess(Vehicle $vehicle): void {
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