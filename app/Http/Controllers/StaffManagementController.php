<?php
namespace App\Http\Controllers;

use App\Mail\StaffPerformanceMail;
use App\Models\ActivityLog;
use App\Models\JobCard;
use App\Models\JobFeedback;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\SalaryPayment;
use App\Models\StaffAttendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StaffManagementController extends Controller
{
    // ------------------------------------------------------------
    // Staff Directory & Positions Roster
    // ------------------------------------------------------------

    /**
     * Staff Directory & Positions Roster:
     * Details every staff position, what they do, their mechanic specialties,
     * monthly payroll, active workloads, and contact particulars.
     */
    public function roster() {
        $staffList = User::whereIn('role', ['admin', 'coordinator', 'staff'])
            ->where('status', 'active')
            ->withCount([
                'jobCards as active_jobs' => fn($q) => $q->where('current_stage', '!=', 'completed'),
                'jobCards as completed_jobs' => fn($q) => $q->where('current_stage', 'completed'),
            ])
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'coordinator' THEN 2 WHEN 'staff' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();

        $jobTypes = \App\Models\JobType::all()->keyBy('id');

        // Job description dictionary describing what each position does
        $dutiesMap = [
            'Workshop Director' => 'Overall workshop executive leadership, fleet commercial contracts, inventory PO approval, and operational policy enforcement.',
            'Senior Master Mechanic' => 'Lead technician overseeing complex engine rebuilds, powertrain overhauls, diagnostic escalations, quality control (QC) sign-off, and apprentice mentoring.',
            'Brake & Climate Specialist' => 'Specializes in brake pad & rotor replacements, hydraulic braking systems, tyre rotation & balancing, and AC refrigeration & compressor servicing.',
            'Suspension Specialist' => 'Undercarriage specialist handling suspension struts, shock absorbers, bushings, steering linkages, high-voltage battery swaps, and transmission fluid service.',
            'Diagnostic Specialist' => 'Advanced computer diagnostics, OBD-II scanner telemetry, ECU fault isolation, electrical harness troubleshooting, and electronic sensor calibration.',
            'Junior Apprentice' => 'Fast-fit maintenance, routine lube services, engine oil & filter replacements, tyre rotation, battery testing, and supporting senior mechanics.',
            'Lead Service Coordinator' => 'Customer reception desk operations, appointment check-ins, walk-in driver intake, mechanic bay dispatching, and invoicing.',
            'Customer Coordinator' => 'Corporate fleet logistics coordination, commercial vehicle scheduling, customer service communications, and billing verification.',
        ];

        foreach ($staffList as $s) {
            $specIds = is_array($s->specialties) ? $s->specialties : [];
            $s->specialty_models = collect($specIds)->map(fn($id) => $jobTypes->get($id))->filter();
            $s->duties = $dutiesMap[$s->staff_role] ?? ($s->role === 'coordinator' ? 'Service desk reception, scheduling, and customer communication.' : 'Vehicle inspection, fault diagnosis, and mechanical repairs.');
        }

        $totalPayroll = $staffList->whereIn('role', ['coordinator', 'staff'])->sum(fn($s) => (float)($s->monthly_salary ?? 0));
        $mechanicsCount = $staffList->where('role', 'staff')->count();
        $coordinatorsCount = $staffList->where('role', 'coordinator')->count();

        return view('staff-management.roster', compact('staffList', 'jobTypes', 'totalPayroll', 'mechanicsCount', 'coordinatorsCount'));
    }

    // ------------------------------------------------------------
    // Attendance
    // ------------------------------------------------------------

    public function attendance(Request $request) {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $date = $request->input('date', now()->toDateString());
            $currentDate = Carbon::parse($date);

            $records = StaffAttendance::with('staff')
                ->whereDate('date', $date)
                ->get();

            $staffList = User::whereIn('role', ['staff', 'coordinator'])
                ->where('status', 'active')
                ->orderBy('role')
                ->orderBy('name')
                ->get();

            $rows = $staffList->map(function ($staff) use ($records) {
                $record = $records->firstWhere('staff_id', $staff->id);
                $hoursWorked = null;
                $isLate = false;

                if ($record && $record->clock_in && $record->clock_out) {
                    $in = Carbon::parse($record->clock_in);
                    $out = Carbon::parse($record->clock_out);
                    $diffMins = max(0, $in->diffInMinutes($out));
                    $h = floor($diffMins / 60);
                    $m = $diffMins % 60;
                    $hoursWorked = "{$h}h {$m}m";
                }

                if ($record && $record->clock_in) {
                    $in = Carbon::parse($record->clock_in);
                    // Standard workshop morning shift starts at 08:30 AM
                    if ($in->format('H:i') > '08:35') {
                        $isLate = true;
                    }
                }

                return (object) [
                    'staff'        => $staff,
                    'record'       => $record,
                    'hours_worked' => $hoursWorked,
                    'is_late'      => $isLate,
                ];
            });

            $totalStaff = $staffList->count();
            $presentCount = $records->where('status', 'present')->count();
            $halfDayCount = $records->where('status', 'half_day')->count();
            $leaveCount = $records->where('status', 'leave')->count();
            $absentCount = $records->where('status', 'absent')->count();
            $unrecordedCount = max(0, $totalStaff - ($presentCount + $halfDayCount + $leaveCount + $absentCount));

            $stats = compact('totalStaff', 'presentCount', 'halfDayCount', 'leaveCount', 'absentCount', 'unrecordedCount');

            return view('staff-management.attendance', compact('rows', 'date', 'stats', 'staffList'));
        }

        // Non-admin (staff / coordinator self-attendance view)
        $today = StaffAttendance::where('staff_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $todayHours = null;
        if ($today && $today->clock_in) {
            $in = Carbon::parse($today->clock_in);
            $out = $today->clock_out ? Carbon::parse($today->clock_out) : now();
            $diffMins = max(0, $in->diffInMinutes($out));
            $h = floor($diffMins / 60);
            $m = $diffMins % 60;
            $todayHours = "{$h}h {$m}m";
        }

        $history = StaffAttendance::where('staff_id', $user->id)
            ->orderByDesc('date')
            ->take(30)
            ->get()
            ->map(function($h) {
                $duration = '—';
                if ($h->clock_in && $h->clock_out) {
                    $in = Carbon::parse($h->clock_in);
                    $out = Carbon::parse($h->clock_out);
                    $diffMins = max(0, $in->diffInMinutes($out));
                    $hours = floor($diffMins / 60);
                    $mins = $diffMins % 60;
                    $duration = "{$hours}h {$mins}m";
                }
                $h->duration = $duration;
                return $h;
            });

        return view('staff-management.attendance-self', compact('today', 'history', 'todayHours'));
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
            $record->update(['clock_in' => now()->format('H:i:s'), 'status' => 'present']);
        }

        ActivityLog::record('attendance.clock_in', "{$user->name} clocked in at " . now()->format('h:i A'), $record);

        return back()->with('success', 'Clocked in successfully at ' . now()->format('h:i A'));
    }

    public function clockOut() {
        $user = auth()->user();

        $record = StaffAttendance::where('staff_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if ($record) {
            $record->update(['clock_out' => now()->format('H:i:s')]);
            ActivityLog::record('attendance.clock_out', "{$user->name} clocked out at " . now()->format('h:i A'), $record);
        }

        return back()->with('success', 'Clocked out successfully at ' . now()->format('h:i A'));
    }

    /**
     * Admin manual attendance entry / adjustment override.
     */
    public function recordAttendance(Request $request) {
        $request->validate([
            'staff_id'  => 'required|exists:users,id',
            'date'      => 'required|date',
            'status'    => 'required|in:present,half_day,absent,leave',
            'clock_in'  => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'notes'     => 'nullable|string|max:255',
        ]);

        $staff = User::findOrFail($request->staff_id);

        $record = StaffAttendance::updateOrCreate(
            [
                'staff_id' => $request->staff_id,
                'date'     => $request->date,
            ],
            [
                'status'    => $request->status,
                'clock_in'  => $request->clock_in ? Carbon::parse($request->clock_in)->format('H:i:s') : null,
                'clock_out' => $request->clock_out ? Carbon::parse($request->clock_out)->format('H:i:s') : null,
                'notes'     => $request->notes,
            ]
        );

        ActivityLog::record(
            'attendance.manual_adjustment',
            "Adjusted attendance for {$staff->name} on {$request->date} (Status: {$request->status})",
            $record
        );

        return back()->with('success', "Attendance record updated successfully for {$staff->name}.");
    }

    /**
     * Export daily or monthly attendance records as CSV.
     */
    public function exportAttendance(Request $request) {
        $date = $request->input('date', now()->toDateString());
        $scope = $request->input('scope', 'day'); // 'day' or 'month'

        if ($scope === 'month') {
            $month = Carbon::parse($date)->format('Y-m');
            $records = StaffAttendance::with('staff')
                ->where('date', 'LIKE', "{$month}%")
                ->orderBy('date')
                ->get();
            $filename = "staff_attendance_{$month}.csv";
        } else {
            $records = StaffAttendance::with('staff')
                ->whereDate('date', $date)
                ->get();
            $filename = "staff_attendance_{$date}.csv";
        }

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Staff Name', 'Role', 'Date', 'Clock In', 'Clock Out', 'Hours Worked', 'Status', 'Notes']);
            foreach ($records as $r) {
                $hours = '';
                if ($r->clock_in && $r->clock_out) {
                    $in = Carbon::parse($r->clock_in);
                    $out = Carbon::parse($r->clock_out);
                    $diffMins = max(0, $in->diffInMinutes($out));
                    $hours = round($diffMins / 60, 2) . ' hrs';
                }
                fputcsv($out, [
                    $r->staff->name ?? '—',
                    $r->staff->staff_role ?? ucfirst($r->staff->role ?? ''),
                    $r->date ? $r->date->format('Y-m-d') : '',
                    $r->clock_in ?? '—',
                    $r->clock_out ?? '—',
                    $hours,
                    ucfirst(str_replace('_', ' ', $r->status ?? '')),
                    $r->notes ?? '',
                ]);
            }
            fclose($out);
        }, $filename, $headers);
    }

    // ------------------------------------------------------------
    // Leave requests
    // ------------------------------------------------------------

    public function leave() {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $requests = LeaveRequest::with(['staff', 'approver'])->latest()->get();

            $pendingCount = $requests->where('status', 'pending')->count();
            $approvedYear = $requests->where('status', 'approved')
                ->where('start_date', '>=', now()->startOfYear())
                ->count();
            $onLeaveToday = $requests->where('status', 'approved')
                ->filter(fn($r) => now()->between($r->start_date, $r->end_date))
                ->count();

            $stats = compact('pendingCount', 'approvedYear', 'onLeaveToday');

            return view('staff-management.leave', compact('requests', 'stats'));
        }

        // Staff / Coordinator view
        $requests = LeaveRequest::where('staff_id', $user->id)->latest()->get();

        // Calculate leave balances for current calendar year
        $approvedYearRequests = $requests->where('status', 'approved')
            ->filter(fn($r) => $r->start_date && $r->start_date->year === now()->year);

        $annualTaken = $approvedYearRequests->where('leave_type', 'annual')->sum(fn($r) => $r->days_count);
        $medicalTaken = $approvedYearRequests->where('leave_type', 'medical')->sum(fn($r) => $r->days_count);
        $emergencyTaken = $approvedYearRequests->where('leave_type', 'emergency')->sum(fn($r) => $r->days_count);
        $unpaidTaken = $approvedYearRequests->where('leave_type', 'unpaid')->sum(fn($r) => $r->days_count);

        $annualTotal = 14;
        $medicalTotal = 14;
        $annualRemaining = max(0, $annualTotal - $annualTaken);
        $medicalRemaining = max(0, $medicalTotal - $medicalTaken);

        $balances = compact(
            'annualTotal', 'annualTaken', 'annualRemaining',
            'medicalTotal', 'medicalTaken', 'medicalRemaining',
            'emergencyTaken', 'unpaidTaken'
        );

        return view('staff-management.leave', compact('requests', 'balances'));
    }

    public function storeLeave(Request $request) {
        $request->validate([
            'leave_type' => 'required|in:annual,medical,emergency,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        $leave = LeaveRequest::create([
            'staff_id'   => auth()->id(),
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        // Notify admins about the new leave submission
        $adminIds = User::where('role', 'admin')->pluck('id');
        $label = LeaveRequest::LEAVE_TYPES[$request->leave_type] ?? 'Leave';
        foreach ($adminIds as $adminId) {
            Notification::send(
                $adminId,
                'New Leave Request',
                auth()->user()->name . " submitted a {$label} request ({$request->start_date} to {$request->end_date}).",
                '/admin/staff-management/leave'
            );
        }

        ActivityLog::record(
            'leave.submitted',
            auth()->user()->name . " submitted a {$label} application from {$request->start_date} to {$request->end_date}.",
            $leave
        );

        return back()->with('success', 'Leave request submitted successfully for management review.');
    }

    public function approveLeave(LeaveRequest $leaveRequest) {
        $leaveRequest->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
        ]);

        // Populate attendance table for each day of the approved leave
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
        $typeLabel = $leaveRequest->leave_type_label;
        foreach ($period as $day) {
            StaffAttendance::updateOrCreate(
                ['staff_id' => $leaveRequest->staff_id, 'date' => $day->toDateString()],
                [
                    'status' => 'leave',
                    'notes'  => "Approved {$typeLabel} (#REQ-{$leaveRequest->id})",
                ]
            );
        }

        Notification::send(
            $leaveRequest->staff_id,
            'Leave Request Approved',
            "Your {$typeLabel} request ({$leaveRequest->start_date->format('d M')} - {$leaveRequest->end_date->format('d M Y')}) was approved by " . auth()->user()->name . ".",
            auth()->user()->role === 'coordinator' ? '/coordinator/staff-management/leave' : '/staff/staff-management/leave'
        );

        ActivityLog::record(
            'leave.approved',
            "Approved {$typeLabel} request for {$leaveRequest->staff->name} ({$leaveRequest->start_date->format('d M')} - {$leaveRequest->end_date->format('d M Y')}).",
            $leaveRequest
        );

        return back()->with('success', 'Leave request approved and attendance calendar updated.');
    }

    public function rejectLeave(Request $request, LeaveRequest $leaveRequest) {
        $adminNotes = trim((string) $request->input('admin_notes', ''));

        $leaveRequest->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'admin_notes' => $adminNotes ?: 'Management scheduling constraints.',
        ]);

        Notification::send(
            $leaveRequest->staff_id,
            'Leave Request Declined',
            "Your leave request ({$leaveRequest->start_date->format('d M')} - {$leaveRequest->end_date->format('d M Y')}) was declined. Reason: " . ($adminNotes ?: 'Management scheduling constraints.'),
            auth()->user()->role === 'coordinator' ? '/coordinator/staff-management/leave' : '/staff/staff-management/leave'
        );

        ActivityLog::record(
            'leave.rejected',
            "Declined leave request for {$leaveRequest->staff->name}. Reason: " . ($adminNotes ?: 'Scheduling constraints.'),
            $leaveRequest
        );

        return back()->with('success', 'Leave request declined and employee notified.');
    }

    // ------------------------------------------------------------
    // Performance
    // ------------------------------------------------------------

    private function buildPerformanceReport(string $period = 'all') {
        $startDate = null;
        $endDate = null;

        if ($period === 'this_month') {
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
        } elseif ($period === 'last_month') {
            $startDate = now()->subMonth()->startOfMonth();
            $endDate   = now()->subMonth()->endOfMonth();
        } elseif ($period === 'this_year') {
            $startDate = now()->startOfYear();
            $endDate   = now()->endOfYear();
        }

        $staffList = User::whereIn('role', ['staff', 'coordinator'])
            ->where('status', 'active')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return $staffList->map(function ($s) use ($startDate, $endDate) {
            $completedJobs = JobCard::where('staff_id', $s->id)->where('current_stage', 'completed');
            if ($startDate && $endDate) {
                $completedJobs->whereBetween('updated_at', [$startDate, $endDate]);
            }

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

            $attQuery = StaffAttendance::where('staff_id', $s->id);
            if ($startDate && $endDate) {
                $attQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
            }
            $presentDays = (clone $attQuery)->where('status', 'present')->count();
            $totalDays   = (clone $attQuery)->count();
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : null;

            $feedbackQuery = JobFeedback::whereHas('jobCard', fn($q) => $q->where('staff_id', $s->id));
            if ($startDate && $endDate) {
                $feedbackQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $avgRating = $feedbackQuery->avg('rating');

            $activeJobsCount = JobCard::where('staff_id', $s->id)
                ->where('current_stage', '!=', 'completed')
                ->count();

            return (object) [
                'staff'             => $s,
                'total_jobs'        => $total,
                'on_time'           => $onTime,
                'overdue'           => $overdue,
                'on_time_pct'       => $total > 0 ? round(($onTime / $total) * 100) : null,
                'avg_hours'         => $avgHours ? round($avgHours, 1) : null,
                'attendance_rate'   => $attendanceRate,
                'avg_rating'        => $avgRating ? round($avgRating, 1) : null,
                'active_jobs_count' => $activeJobsCount,
            ];
        });
    }

    public function performance(Request $request) {
        $period = $request->input('period', 'all');
        $report = $this->buildPerformanceReport($period);

        // Overall aggregate metrics
        $totalCompleted = $report->sum('total_jobs');
        $totalOnTime = $report->sum('on_time');
        $overallOnTimePct = $totalCompleted > 0 ? round(($totalOnTime / $totalCompleted) * 100) : null;

        $ratedRows = $report->filter(fn($r) => $r->avg_rating !== null);
        $overallRating = $ratedRows->isNotEmpty() ? round($ratedRows->avg('avg_rating'), 1) : null;

        $hoursRows = $report->filter(fn($r) => $r->avg_hours !== null);
        $overallTurnaround = $hoursRows->isNotEmpty() ? round($hoursRows->avg('avg_hours'), 1) : null;

        $kpis = compact('totalCompleted', 'totalOnTime', 'overallOnTimePct', 'overallRating', 'overallTurnaround');

        return view('staff-management.performance', compact('report', 'period', 'kpis'));
    }

    public function emailPerformanceReport(Request $request) {
        $period = $request->input('period', 'all');
        $report = $this->buildPerformanceReport($period);
        $periodLabel = match($period) {
            'this_month' => now()->format('F Y'),
            'last_month' => now()->subMonth()->format('F Y'),
            'this_year'  => 'Year ' . now()->year,
            default      => 'All Time (Cumulative)',
        };

        $adminEmails = User::where('role', 'admin')->pluck('email');
        foreach ($adminEmails as $email) {
            try {
                Mail::to($email)->send(new StaffPerformanceMail($report, $periodLabel));
            } catch (\Throwable $e) {
                Log::warning('Performance report email to admin failed: ' . $e->getMessage());
            }
        }

        if ($request->boolean('notify_staff')) {
            foreach ($report as $r) {
                if (!$r->staff->email) continue;
                try {
                    Mail::to($r->staff->email)->send(new StaffPerformanceMail(collect([$r]), $periodLabel));
                } catch (\Throwable $e) {
                    Log::warning('Performance report email to staff failed: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Performance report emailed to admin/HR'
            . ($request->boolean('notify_staff') ? ', and to each staff member individually.' : '.'));
    }

    public function exportPerformance(Request $request) {
        $period = $request->input('period', 'all');
        $report   = $this->buildPerformanceReport($period);
        $filename = 'staff_performance_' . $period . '_' . now()->format('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Staff Name', 'Role', 'Completed Jobs', 'On-Time', 'Overdue', 'On-Time %', 'Avg Turnaround (hrs)', 'Attendance Rate (%)', 'Avg Rating']);
            foreach ($report as $r) {
                fputcsv($out, [
                    $r->staff->name,
                    $r->staff->staff_role ?? ucfirst($r->staff->role),
                    $r->total_jobs,
                    $r->on_time,
                    $r->overdue,
                    $r->on_time_pct !== null ? $r->on_time_pct . '%' : '',
                    $r->avg_hours ?? '',
                    $r->attendance_rate !== null ? $r->attendance_rate . '%' : '',
                    $r->avg_rating ?? '',
                ]);
            }
            fclose($out);
        }, $filename, $headers);
    }

    // ------------------------------------------------------------
    // Salary payments (Accountant / Admin / Staff Self-service)
    // ------------------------------------------------------------

    public function salary(Request $request) {
        $staff = User::whereIn('role', ['staff', 'coordinator'])
            ->where('status', 'active')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $period = $request->input('period', now()->format('Y-m'));

        $payments = SalaryPayment::with(['staff', 'recorder'])
            ->latest('paid_at')
            ->get();

        $periodPayments = $payments->where('period', $period);

        // Payroll Metrics
        $totalObligation = $staff->sum(fn($s) => (float) ($s->monthly_salary ?? 0));
        $disbursedThisMonth = $periodPayments->sum('amount');
        $paidStaffIds = $periodPayments->pluck('staff_id')->unique();
        $paidStaffCount = $paidStaffIds->count();
        $pendingStaffCount = max(0, $staff->count() - $paidStaffCount);
        $percentDisbursed = $totalObligation > 0 ? min(100, round(($disbursedThisMonth / $totalObligation) * 100)) : 0;

        $stats = compact('totalObligation', 'disbursedThisMonth', 'paidStaffCount', 'pendingStaffCount', 'percentDisbursed');

        return view('staff-management.salary', compact('staff', 'payments', 'period', 'stats'));
    }

    public function storeSalaryPayment(Request $request) {
        $request->validate([
            'staff_id'    => 'required|exists:users,id',
            'period'      => 'required|date_format:Y-m',
            'base_salary' => 'nullable|numeric|min:0',
            'allowances'  => 'nullable|numeric|min:0',
            'deductions'  => 'nullable|numeric|min:0',
            'amount'      => 'required|numeric|min:0.01',
            'method'      => 'required|in:cash,bank_transfer,cheque',
            'paid_at'     => 'required|date',
            'notes'       => 'nullable|string|max:255',
        ]);

        $exists = SalaryPayment::where('staff_id', $request->staff_id)
            ->where('period', $request->period)->exists();

        if ($exists) {
            return back()->with('error', 'A salary payment for this staff member and period has already been recorded.');
        }

        $staff = User::findOrFail($request->staff_id);
        $baseSalary = $request->filled('base_salary') ? (float) $request->base_salary : ((float) $staff->monthly_salary ?: (float) $request->amount);
        $allowances = (float) $request->input('allowances', 0);
        $deductions = (float) $request->input('deductions', 0);

        $payment = SalaryPayment::create([
            'staff_id'    => $request->staff_id,
            'period'      => $request->period,
            'base_salary' => $baseSalary,
            'allowances'  => $allowances,
            'deductions'  => $deductions,
            'amount'      => $request->amount,
            'method'      => $request->method,
            'paid_at'     => $request->paid_at,
            'notes'       => $request->notes,
            'recorded_by' => auth()->id(),
        ]);

        $periodFormatted = Carbon::createFromFormat('Y-m', $request->period)->format('F Y');
        Notification::send(
            $staff->id,
            'Salary Disbursement',
            "Your salary voucher ({$payment->slip_number}) for {$periodFormatted} of RM " . number_format($payment->amount, 2) . " has been issued.",
            "/staff-management/salary/{$payment->id}/payslip"
        );

        ActivityLog::record(
            'salary.disbursed',
            "Recorded salary voucher {$payment->slip_number} for {$staff->name} ({$periodFormatted}, RM " . number_format($payment->amount, 2) . ")",
            $payment
        );

        return back()->with('success', "Salary payment recorded successfully for {$staff->name}.");
    }

    /**
     * Printable formal employee payslip voucher.
     */
    public function payslip(SalaryPayment $salaryPayment) {
        $user = auth()->user();

        // Authorization guard: Admin, invoice.manage holders, or the recipient themselves
        $allowed = in_array($user->role, ['admin'])
            || $user->hasPermission('invoice.manage')
            || $salaryPayment->staff_id === $user->id;

        abort_unless($allowed, 403, 'Unauthorized to view this salary voucher.');

        $salaryPayment->load(['staff', 'recorder']);

        return view('staff-management.payslip', compact('salaryPayment'));
    }

    /**
     * Staff / Coordinator self-service salary history & payslips.
     */
    public function selfSalary() {
        $user = auth()->user();

        $payments = SalaryPayment::where('staff_id', $user->id)
            ->with('recorder')
            ->latest('paid_at')
            ->get();

        $currentYearTotal = $payments
            ->filter(fn($p) => $p->paid_at && $p->paid_at->year === now()->year)
            ->sum('amount');

        return view('staff-management.salary-self', compact('payments', 'currentYearTotal'));
    }
}
