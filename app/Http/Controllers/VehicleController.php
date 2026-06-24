<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index() {
    $user = auth()->user();

    if ($user->role === 'admin') {
        $vehicles = Vehicle::with('owner')->orderBy('plate_number')->get();
    } elseif ($user->role === 'corporate') {
        // Show all vehicles under the same company
        $companyUserIds = \App\Models\User::where('company_id', $user->company_id)->pluck('id');
        $vehicles = Vehicle::whereIn('user_id', $companyUserIds)
            ->with('owner')
            ->orderBy('plate_number')
            ->get();
    } else {
        $vehicles = Vehicle::where('user_id', $user->id)
            ->with('owner')
            ->orderBy('plate_number')
            ->get();
    }

    return view('vehicles.index', compact('vehicles'));
    }

    public function show(Vehicle $vehicle) {
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
        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle) {
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
        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle deleted.');
    }
}