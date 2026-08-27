<?php
namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\StaffAttendance;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Models\JobCard;
use Illuminate\Http\Request;

class StaffManagementController extends Controller
{
    // ------------------------------------------------------------
    // Attendance
    // ------------------------------------------------------------

    public function attendance(Request $request) {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $date = $request->input('date', now()->toDateString());

            $records = StaffAttendance::with('staff')
                ->whereDate('date', $date)
                ->get();

            $staffList = User::where('role', 'staff')->where('status', 'active')->get();

            $rows = $staffList->map(function ($staff) use ($records) {
                return (object) [
                    'staff'  => $staff,
                    'record' => $records->firstWhere('staff_id', $staff->id),
                ];
            });

            return view('staff-management.attendance', compact('rows', 'date'));
        }

        // Staff: own attendance + clock in/out
        $today = StaffAttendance::where('staff_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $history = StaffAttendance::where('staff_id', $user->id)
            ->orderByDesc('date')
            ->take(14)
            ->get();

        return view('staff-management.attendance-self', compact('today', 'history'));
    }

    public function clockIn() {
        $user = auth()->user();

        $record = StaffAttendance::firstOrCreate(
            ['staff_id' => $user->id, 'date' => now()->toDateString()],
            ['status' => 'present']
        );

        if ($record->wasRecentlyCreated) {
            $record->update(['clock_in' => now()->format('H:i:s')]);
        } elseif (!$record->clock_in && !in_array($record->status, ['leave', 'absent'])) {
            // Only auto-flip to "present" if the day isn't already
            // marked leave/absent (e.g. an approved leave request).
            $record->update(['clock_in' => now()->format('H:i:s'), 'status' => 'present']);
        }

        return back()->with('success', 'Clocked in at ' . now()->format('H:i'));
    }

    public function clockOut() {
        $user = auth()->user();

        $record = StaffAttendance::where('staff_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if ($record) {
            $record->update(['clock_out' => now()->format('H:i:s')]);
        }

        return back()->with('success', 'Clocked out at ' . now()->format('H:i'));
    }

    // ------------------------------------------------------------
    // Leave requests
    // ------------------------------------------------------------

    public function leave() {
        $user = auth()->user();

        $requests = $user->role === 'admin'
            ? LeaveRequest::with(['staff', 'approver'])->latest()->get()
            : LeaveRequest::where('staff_id', $user->id)->latest()->get();

        return view('staff-management.leave', compact('requests'));
    }

    public function storeLeave(Request $request) {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        LeaveRequest::create([
            'staff_id'   => auth()->id(),
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'reason'     => $request->reason,
        ]);

        return back()->with('success', 'Leave request submitted.');
    }

    public function approveLeave(LeaveRequest $leaveRequest) {
        $leaveRequest->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
        ]);

        $period = \Carbon\CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
        foreach ($period as $day) {
            StaffAttendance::updateOrCreate(
                ['staff_id' => $leaveRequest->staff_id, 'date' => $day->toDateString()],
                ['status' => 'leave']
            );
        }

        return back()->with('success', 'Leave approved.');
    }

    public function rejectLeave(Request $request, LeaveRequest $leaveRequest) {
        $leaveRequest->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return back()->with('success', 'Leave rejected.');
    }

    // ------------------------------------------------------------
    // Performance
    // ------------------------------------------------------------

    public function performance() {
        $staff = User::where('role', 'staff')->get();

        $report = $staff->map(function ($s) {
            $completedJobs = JobCard::where('staff_id', $s->id)->where('current_stage', 'completed');

            $total   = (clone $completedJobs)->count();
            $onTime  = (clone $completedJobs)
                ->whereNotNull('completed_at')
                ->whereColumn('completed_at', '<=', 'estimated_completion')
                ->count();
            $overdue = $total - $onTime;

            $avgHours = (clone $completedJobs)
                ->whereNotNull('completed_at')
                ->get()
                ->avg(fn($j) => $j->created_at->diffInHours($j->completed_at));

            $presentDays = StaffAttendance::where('staff_id', $s->id)->where('status', 'present')->count();
            $totalDays   = StaffAttendance::where('staff_id', $s->id)->count();
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : null;

            return (object) [
                'staff'           => $s,
                'total_jobs'      => $total,
                'on_time'         => $onTime,
                'overdue'         => $overdue,
                'avg_hours'       => $avgHours ? round($avgHours, 1) : null,
                'attendance_rate' => $attendanceRate,
            ];
        });

        return view('staff-management.performance', compact('report'));
    }

    // ------------------------------------------------------------
    // Salary payments (admin only)
    // ------------------------------------------------------------

    public function salary() {
        $staff    = User::where('role', 'staff')->get();
        $payments = SalaryPayment::with(['staff', 'recorder'])->latest('paid_at')->get();

        return view('staff-management.salary', compact('staff', 'payments'));
    }

    public function storeSalaryPayment(Request $request) {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'period'   => 'required|date_format:Y-m',
            'amount'   => 'required|numeric|min:0.01',
            'method'   => 'required|in:cash,bank_transfer,cheque',
            'paid_at'  => 'required|date',
            'notes'    => 'nullable|string|max:255',
        ]);

        $exists = SalaryPayment::where('staff_id', $request->staff_id)
            ->where('period', $request->period)->exists();

        if ($exists) {
            return back()->with('error', 'A salary payment for this staff and period is already recorded.');
        }

        SalaryPayment::create([
            'staff_id'    => $request->staff_id,
            'period'      => $request->period,
            'amount'      => $request->amount,
            'method'      => $request->method,
            'paid_at'     => $request->paid_at,
            'notes'       => $request->notes,
            'recorded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Salary payment recorded.');
    }
}