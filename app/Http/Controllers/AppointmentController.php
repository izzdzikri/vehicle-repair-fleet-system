<?php
namespace App\Http\Controllers;

use App\Mail\AppointmentStatusMail;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Setting;
use App\Models\Notification;
use App\Models\JobCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    public function index(Request $request) {
        $user    = auth()->user();
        $search  = trim((string) $request->input('search'));
        $perPage = 15;

        if (in_array($user->role, ['admin', 'coordinator'])) {
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
        $counts = (clone $countScope)
            ->selectRaw("
                count(*) as all_count,
                sum(case when status = 'pending' then 1 else 0 end) as pending_cnt,
                sum(case when status = 'confirmed' then 1 else 0 end) as confirmed_cnt,
                sum(case when status = 'completed' then 1 else 0 end) as completed_cnt,
                sum(case when status = 'cancelled' then 1 else 0 end) as cancelled_cnt
            ")->first();

        $statusCounts = [
            'all'       => (int) ($counts->all_count ?? 0),
            'pending'   => (int) ($counts->pending_cnt ?? 0),
            'confirmed' => (int) ($counts->confirmed_cnt ?? 0),
            'completed' => (int) ($counts->completed_cnt ?? 0),
            'cancelled' => (int) ($counts->cancelled_cnt ?? 0),
        ];

        $query = (clone $base)->with(['vehicle', 'user', 'jobCard']);
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $applySearch($query);

        $appointments = $query->latest()->paginate($perPage)->withQueryString();

        $capacity = $user->role === 'admin' ? (int) Setting::get('daily_appointment_capacity', 20) : null;

        return view('appointments.index', compact('appointments', 'search', 'statusCounts', 'capacity'));
    }

    public function show(Appointment $appointment) {
    $user = auth()->user();

    if (!in_array($user->role, ['admin', 'coordinator'])) {
        $this->authorizeAppointmentAccess($appointment, $user);
    }

    $appointment->load(['vehicle', 'user', 'jobCard']);

    if (in_array($user->role, ['admin', 'coordinator'])) {
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

        $dailyCapacity   = (int) Setting::get('daily_appointment_capacity', 25);
        $totalBooked     = $appointments->count();
        $activeInBay     = JobCard::whereIn('current_stage', ['diagnosing', 'waiting_parts', 'repairing', 'quality_check'])->count();
        $completedToday  = JobCard::where('current_stage', 'completed')
            ->whereDate('completed_at', today())
            ->count();
        $capacityPercent = $dailyCapacity > 0 ? min(100, round(($totalBooked / $dailyCapacity) * 100)) : 0;
        $availableSlots  = max(0, $dailyCapacity - $totalBooked);

        $stats = [
            'capacity'         => $dailyCapacity,
            'total_booked'     => $totalBooked,
            'active_in_bay'    => $activeInBay,
            'completed_today'  => $completedToday,
            'available_slots'  => $availableSlots,
            'utilization_rate' => $capacityPercent,
        ];

        return view('appointments.queue', compact('appointments', 'stats'));
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

        // Daily booking capacity check — prevents overbooking a given date.
        $capacity = (int) Setting::get('daily_appointment_capacity', 20);
        $existingCount = Appointment::whereDate('date', $request->date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        if ($existingCount >= $capacity) {
            return back()->with('error',
                'Sorry, this date is fully booked. Please choose another date.')->withInput();
        }

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            abort_unless($companyUserIds->contains($vehicle->user_id), 403,
                'You can only book appointments for vehicles in your company fleet.');
        } else {
            abort_unless($vehicle->user_id === $user->id, 403,
                'You can only book appointments for your own vehicles.');
        }

        $appointment = Appointment::create([
            'user_id'      => $user->id,
            'vehicle_id'   => $request->vehicle_id,
            'date'         => $request->date,
            'time'         => $request->time,
            'service_type' => $request->service_type,
            'notes'        => $request->notes,
            'status'       => 'pending',
            'is_walkin'    => false,
        ]);

        $this->notifyStatus($appointment, 'Submitted');

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

        $appointment = DB::transaction(function () use ($request) {
            $plate = strtoupper(trim($request->plate_number));
            $vehicle = Vehicle::where('plate_number', $plate)->first();

            if (!$vehicle) {
                $vehicle = Vehicle::create([
                    'user_id'      => null,
                    'plate_number' => $plate,
                    'brand'        => $request->brand,
                    'model'        => $request->model,
                    'year'         => $request->year,
                    'mileage'      => 0,
                ]);
            }

            return Appointment::create([
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
        });

        $rolePrefix = match(auth()->user()->role) {
            'coordinator' => 'coordinator',
            'staff'       => 'staff',
            default       => 'admin',
        };
        $redirect = ($rolePrefix === 'staff')
            ? '/staff/appointments/queue'
            : "/{$rolePrefix}/appointments/{$appointment->id}";

        return redirect($redirect)
            ->with('success', 'Walk-in appointment created and confirmed.');
    }

    // ----------------------------------------------------------------
    // Booking capacity (admin only)
    // ----------------------------------------------------------------

    public function updateCapacity(Request $request) {
        $request->validate([
            'daily_appointment_capacity' => 'required|integer|min:1|max:200',
        ]);

        Setting::set('daily_appointment_capacity', $request->daily_appointment_capacity);

        return back()->with('success', 'Booking capacity updated.');
    }

    // ----------------------------------------------------------------
    // Status actions (admin) — each notifies the customer by email + in-app
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
     * Notify the registered customer (in-app + email) about a status
     * change. Walk-in customers (no user account) are skipped entirely.
     * Users without an email still get the in-app notification. Mail
     * failures never break the request.
     */
    private function notifyStatus(Appointment $appointment, string $label): void {
        if ($appointment->is_walkin) return;

        $appointment->load(['vehicle', 'user']);
        if (!$appointment->user) return;

        $prefix = $appointment->user->role === 'corporate' ? '/client' : '/customer';
        Notification::send(
            $appointment->user_id,
            "Appointment {$label}",
            "Your {$appointment->service_type} appointment on ".\Carbon\Carbon::parse($appointment->date)->format('d M Y')." has been {$label}.",
            "{$prefix}/appointments/{$appointment->id}"
        );

        if (!$appointment->user->email) return;

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