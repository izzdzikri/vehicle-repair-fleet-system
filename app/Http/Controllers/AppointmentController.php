<?php
namespace App\Http\Controllers;

use App\Mail\AppointmentStatusMail;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    public function index(Request $request) {
        $user    = auth()->user();
        $search  = trim((string) $request->input('search'));
        $perPage = 15;

        if ($user->role === 'admin') {
            $base = Appointment::query();
        } elseif ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $base = Appointment::whereIn('user_id', $companyUserIds);
        } else {
            $base = Appointment::where('user_id', $user->id);
        }

        $applySearch = function ($query) use ($search) {
            if ($search === '') return;
            $query->where(function ($q) use ($search) {
                $q->where('service_type', 'like', "%{$search}%")
                  ->orWhere('walkin_name', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', fn($v) => $v->where('plate_number', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        };

        $countScope = clone $base;
        $applySearch($countScope);
        $statusCounts = [
            'all'       => (clone $countScope)->count(),
            'pending'   => (clone $countScope)->where('status', 'pending')->count(),
            'confirmed' => (clone $countScope)->where('status', 'confirmed')->count(),
            'completed' => (clone $countScope)->where('status', 'completed')->count(),
            'cancelled' => (clone $countScope)->where('status', 'cancelled')->count(),
        ];

        $query = (clone $base)->with(['vehicle', 'user', 'jobCard']);
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $applySearch($query);

        $appointments = $query->latest()->paginate($perPage)->withQueryString();

        return view('appointments.index', compact('appointments', 'search', 'statusCounts'));
    }

    public function show(Appointment $appointment) {
    $user = auth()->user();

    if ($user->role !== 'admin') {
        $this->authorizeAppointmentAccess($appointment, $user);
    }

    $appointment->load(['vehicle', 'user', 'jobCard']);

    if ($user->role === 'admin') {
        session(['viewed_appointment_' . $appointment->id => true]);
    }

    return view('appointments.show', compact('appointment'));
}

    // ----------------------------------------------------------------
    // Today's Service Queue — Walk-in vs Booked priority
    // ----------------------------------------------------------------

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
        $user = auth()->user();

        if ($user->status === 'inactive') {
            return redirect()->back()->with('error',
                'Your account is inactive. Contact the administrator to reactivate.');
        }

        if ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $vehicles = Vehicle::whereIn('user_id', $companyUserIds)->orderBy('plate_number')->get();
        } else {
            $vehicles = Vehicle::where('user_id', $user->id)->orderBy('plate_number')->get();
        }

        return view('appointments.create', compact('vehicles'));
    }

    public function store(Request $request) {
        $user = auth()->user();

        if ($user->isSecondaryPic()) {
            return redirect()->back()->with('error',
                'Secondary PIC (Viewer) cannot make bookings. Contact your Primary PIC.');
        }
        if ($user->status === 'inactive') {
            return redirect()->back()->with('error',
                'Your account is inactive. You cannot make bookings.');
        }

        $request->validate([
            'vehicle_id'   => 'required|exists:vehicles,id',
            'date'         => 'required|date|after:today',
            'time'         => 'required',
            'service_type' => 'required|string|max:100',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            abort_unless($companyUserIds->contains($vehicle->user_id), 403,
                'You can only book appointments for vehicles in your company fleet.');
        } else {
            abort_unless($vehicle->user_id === $user->id, 403,
                'You can only book appointments for your own vehicles.');
        }

        Appointment::create([
            'user_id'      => $user->id,
            'vehicle_id'   => $request->vehicle_id,
            'date'         => $request->date,
            'time'         => $request->time,
            'service_type' => $request->service_type,
            'notes'        => $request->notes,
            'status'       => 'pending',
            'is_walkin'    => false,
        ]);

        $redirect = $user->role === 'corporate'
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
            'plate_number'   => 'required|string|max:20',
            'brand'          => 'nullable|string|max:60',
            'model'          => 'nullable|string|max:60',
            'year'           => 'nullable|integer|min:1970|max:' . (date('Y') + 1),
        ]);

        $vehicle = Vehicle::where('plate_number', strtoupper(trim($request->plate_number)))->first();

        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'user_id'      => null,
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
            'status'         => 'confirmed',
            'is_walkin'      => true,
            'walkin_name'    => $request->walkin_name,
            'walkin_contact' => $request->walkin_contact,
        ]);

        return redirect('/admin/appointments/' . $appointment->id)
            ->with('success', 'Walk-in appointment created and confirmed.');
    }

    // ----------------------------------------------------------------
    // Status actions (admin) — each notifies the customer by email
    // ----------------------------------------------------------------

    public function confirm(Appointment $appointment) {
        $appointment->update(['status' => 'confirmed']);
        $this->notifyStatus($appointment, 'Confirmed');
        return back()->with('success', 'Appointment confirmed.');
    }

    public function cancel(Appointment $appointment) {
        $appointment->update(['status' => 'cancelled']);
        $this->notifyStatus($appointment, 'Cancelled');
        return back()->with('success', 'Appointment cancelled.');
    }

    public function complete(Appointment $appointment) {
        $appointment->update(['status' => 'completed']);
        $this->notifyStatus($appointment, 'Completed');
        return back()->with('success', 'Appointment marked as completed.');
    }

    /**
     * Email the registered customer about a status change. Walk-in
     * customers (no user account) or users without an email are
     * silently skipped. Mail failures never break the request.
     */
    private function notifyStatus(Appointment $appointment, string $label): void {
        if ($appointment->is_walkin) return;

        $appointment->load(['vehicle', 'user']);
        if (!$appointment->user || !$appointment->user->email) return;

        try {
            Mail::to($appointment->user->email)->send(new AppointmentStatusMail($appointment, $label));
        } catch (\Throwable $e) {
            Log::warning('Appointment status email failed: ' . $e->getMessage());
        }
    }

    private function authorizeAppointmentAccess(Appointment $appointment, $user): void {
        if ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            abort_unless($companyUserIds->contains($appointment->user_id), 403,
                'You do not have access to this appointment.');
            return;
        }

        abort_unless($appointment->user_id === $user->id, 403,
            'You do not have access to this appointment.');
    }
}