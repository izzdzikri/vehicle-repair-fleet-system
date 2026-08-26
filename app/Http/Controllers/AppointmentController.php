<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request) {
    $user = auth()->user();

    if ($user->role === 'admin') {
        $query = Appointment::with(['vehicle', 'user', 'jobCard']);
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $appointments = $query->latest()->get();

    } elseif ($user->role === 'corporate') {
        // All PICs under same company
        $companyUserIds = \App\Models\User::where('company_id', $user->company_id)->pluck('id');
        $query = Appointment::whereIn('user_id', $companyUserIds)
            ->with(['vehicle', 'jobCard', 'user']);
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $appointments = $query->latest()->get();

    } else {
        $appointments = Appointment::where('user_id', $user->id)
            ->with(['vehicle', 'jobCard'])
            ->latest()
            ->get();
    }

    return view('appointments.index', compact('appointments'));
    }

    public function show(Appointment $appointment) {
    $appointment->load(['vehicle', 'user', 'jobCard']);

    // Set viewed flag so confirm button unlocks
    if (auth()->user()->role === 'admin') {
        session(['viewed_appointment_' . $appointment->id => true]);
    }

    return view('appointments.show', compact('appointment'));
}

    // ----------------------------------------------------------------
    // Today's Service Queue — Walk-in vs Booked priority
    // ----------------------------------------------------------------

    /**
     * Priority rule (agreed with supervisor):
     *   1. Booked (pre-scheduled) appointments take priority over walk-ins,
     *      ordered by their reserved time slot.
     *   2. Walk-ins are queued after, ordered by arrival time (created_at).
     */
    public function queue() {
        $appointments = Appointment::with(['vehicle', 'user', 'jobCard.staff'])
            ->whereDate('date', today())
            ->where('status', 'confirmed')
            ->get()
            ->sortBy(function ($apt) {
                $priorityWeight = $apt->is_walkin ? 1 : 0;
                $timeKey = $apt->is_walkin
                    ? $apt->created_at->format('H:i:s')
                    : substr($apt->time, 0, 8);
                return $priorityWeight . '_' . $timeKey;
            })
            ->values();

        return view('appointments.queue', compact('appointments'));
    }

    // ----------------------------------------------------------------
    // Customer booking (individual / corporate)
    // ----------------------------------------------------------------

    public function create() {
        if (auth()->user()->status === 'inactive') {
            return redirect()->back()->with('error',
                'Your account is inactive. Contact the administrator to reactivate.');
        }
        $vehicles = Vehicle::where('user_id', auth()->id())->get();
        return view('appointments.create', compact('vehicles'));
    }

    public function store(Request $request) {
        if (auth()->user()->isSecondaryPic()) {
            return redirect()->back()->with('error',
                'Secondary PIC (Viewer) cannot make bookings. Contact your Primary PIC.');
        }
        if (auth()->user()->status === 'inactive') {
            return redirect()->back()->with('error',
                'Your account is inactive. You cannot make bookings.');
        }

        $request->validate([
            'vehicle_id'   => 'required|exists:vehicles,id',
            'date'         => 'required|date|after:today',
            'time'         => 'required',
            'service_type' => 'required|string|max:100',
        ]);

        Appointment::create([
            'user_id'      => auth()->id(),
            'vehicle_id'   => $request->vehicle_id,
            'date'         => $request->date,
            'time'         => $request->time,
            'service_type' => $request->service_type,
            'notes'        => $request->notes,
            'status'       => 'pending',
            'is_walkin'    => false,
        ]);

        $redirect = auth()->user()->role === 'corporate'
            ? '/client/dashboard'
            : '/customer/dashboard';

        return redirect($redirect)->with('success', 'Appointment booked successfully.');
    }

    // ----------------------------------------------------------------
    // Walk-in appointment (admin / staff only)
    // ----------------------------------------------------------------

    public function walkinCreate() {
        $vehicles = Vehicle::with('owner')->orderBy('plate_number')->get();
        return view('appointments.walkin-create', compact('vehicles'));
    }

    public function walkinStore(Request $request) {
        $request->validate([
            'walkin_name'    => 'required|string|max:100',
            'walkin_contact' => 'nullable|string|max:30',
            'service_type'   => 'required|string|max:100',
            'date'           => 'required|date',
            'time'           => 'required',
            'notes'          => 'nullable|string',
            // vehicle: either existing plate or new entry
            'plate_number'   => 'required|string|max:20',
            'brand'          => 'nullable|string|max:60',
            'model'          => 'nullable|string|max:60',
            'year'           => 'nullable|integer|min:1970|max:' . (date('Y') + 1),
        ]);

        // Find existing vehicle by plate, or create a quick-entry one
        $vehicle = Vehicle::where('plate_number', strtoupper(trim($request->plate_number)))->first();

        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'user_id'      => null,   // walk-in vehicle; no registered owner
                'plate_number' => strtoupper(trim($request->plate_number)),
                'brand'        => $request->brand,
                'model'        => $request->model,
                'year'         => $request->year,
                'mileage'      => 0,
            ]);
        }

        $appointment = Appointment::create([
            'user_id'        => null,
            'vehicle_id'     => $vehicle->id,
            'date'           => $request->date,
            'time'           => $request->time,
            'service_type'   => $request->service_type,
            'notes'          => $request->notes,
            'status'         => 'confirmed',   // walk-ins are auto-confirmed
            'is_walkin'      => true,
            'walkin_name'    => $request->walkin_name,
            'walkin_contact' => $request->walkin_contact,
        ]);

        return redirect('/admin/appointments/' . $appointment->id)
            ->with('success', 'Walk-in appointment created and confirmed.');
    }

    // ----------------------------------------------------------------
    // Status actions (admin)
    // ----------------------------------------------------------------

    public function confirm(Appointment $appointment) {
        $appointment->update(['status' => 'confirmed']);
        return back()->with('success', 'Appointment confirmed.');
    }

    public function cancel(Appointment $appointment) {
        $appointment->update(['status' => 'cancelled']);
        return back()->with('success', 'Appointment cancelled.');
    }

    public function complete(Appointment $appointment) {
        $appointment->update(['status' => 'completed']);
        return back()->with('success', 'Appointment marked as completed.');
    }
}