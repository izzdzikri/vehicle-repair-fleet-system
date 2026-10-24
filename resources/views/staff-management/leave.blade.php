@extends('layouts.app')
@section('page-title', 'Leave Management')

@section('content')

@php
    $isAdmin = auth()->user()->role === 'admin';
    $actionPrefix = auth()->user()->role === 'coordinator' ? '/coordinator' : '/staff';
@endphp

<div x-data="{
    showRejectModal: false,
    rejectRequestId: '',
    rejectStaffName: '',
    rejectDates: '',
    adminNotes: '',
    openReject(id, staffName, dates) {
        this.rejectRequestId = id;
        this.rejectStaffName = staffName;
        this.rejectDates = dates;
        this.adminNotes = '';
        this.showRejectModal = true;
    }
}">

    @if($isAdmin)
    {{-- Admin Subnavigation Tabs --}}
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="/admin/staff-management/roster" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Team Directory & Positions</a>
        <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Attendance Daily</a>
        <a href="/admin/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs flex items-center gap-1.5">
            <span>Leave Requests</span>
            @if(isset($stats) && $stats['pendingCount'] > 0)
            <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $stats['pendingCount'] }}</span>
            @endif
        </a>
        <a href="/admin/staff-management/performance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Performance & KPIs</a>
        <a href="/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Payroll & Salary</a>
    </div>

    {{-- Admin Overview Metrics --}}
    @if(isset($stats))
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-amber-200/80 shadow-xs p-5 bg-amber-50/20 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending Review
                </span>
                <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-amber-700">{{ $stats['pendingCount'] }}</p>
                <p class="text-xs text-amber-600 mt-1">requests awaiting approval</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-blue-200/80 shadow-xs p-5 bg-blue-50/20 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-700 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> On Leave Today
                </span>
                <i data-lucide="calendar" class="w-4 h-4 text-blue-600"></i>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-blue-700">{{ $stats['onLeaveToday'] }}</p>
                <p class="text-xs text-blue-600 mt-1">staff currently away from workshop</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-emerald-200/80 shadow-xs p-5 bg-emerald-50/20 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Approved ({{ now()->year }})
                </span>
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-emerald-700">{{ $stats['approvedYear'] }}</p>
                <p class="text-xs text-emerald-600 mt-1">leave requests processed this year</p>
            </div>
        </div>
    </div>
    @endif

    @else
    {{-- Staff & Coordinator Subnavigation Tabs --}}
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="{{ $actionPrefix }}/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">My Attendance</a>
        <a href="{{ $actionPrefix }}/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs">My Leave Requests</a>
        <a href="{{ $actionPrefix }}/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">My Salary & Payslips</a>
    </div>

    {{-- Employee Leave Balance Cards --}}
    @if(isset($balances))
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Annual Leave (AL)</span>
            <div class="my-2">
                <span class="text-2xl font-extrabold text-gray-900">{{ $balances['annualRemaining'] }}</span>
                <span class="text-xs text-gray-500 font-semibold">/ {{ $balances['annualTotal'] }} days</span>
            </div>
            <span class="text-[11px] text-gray-400">{{ $balances['annualTaken'] }} days used this year</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Medical Leave (MC)</span>
            <div class="my-2">
                <span class="text-2xl font-extrabold text-gray-900">{{ $balances['medicalRemaining'] }}</span>
                <span class="text-xs text-gray-500 font-semibold">/ {{ $balances['medicalTotal'] }} days</span>
            </div>
            <span class="text-[11px] text-gray-400">{{ $balances['medicalTaken'] }} days used this year</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Emergency Leave</span>
            <div class="my-2">
                <span class="text-2xl font-extrabold text-amber-700">{{ $balances['emergencyTaken'] }}</span>
                <span class="text-xs text-gray-500 font-semibold">days taken</span>
            </div>
            <span class="text-[11px] text-gray-400">deductible from quota</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Unpaid Leave</span>
            <div class="my-2">
                <span class="text-2xl font-extrabold text-gray-600">{{ $balances['unpaidTaken'] }}</span>
                <span class="text-xs text-gray-500 font-semibold">days taken</span>
            </div>
            <span class="text-[11px] text-gray-400">subject to payroll deduction</span>
        </div>
    </div>
    @endif

    {{-- Leave Application Form for Staff / Coordinator --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6">
        <div class="mb-4">
            <h2 class="text-base font-bold text-gray-900 tracking-tight">Submit Leave Application</h2>
            <p class="text-xs text-gray-500 mt-0.5">Apply for annual vacation, medical sick leave, or urgent personal time off</p>
        </div>
        <form method="POST" action="{{ $actionPrefix }}/staff-management/leave" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Leave Type *</label>
                <select name="leave_type"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
                    <option value="annual">Annual Leave (AL)</option>
                    <option value="medical">Medical / Sick Leave (MC)</option>
                    <option value="emergency">Emergency Leave (EL)</option>
                    <option value="unpaid">Unpaid Leave</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Start Date *</label>
                <input type="date" name="start_date" min="{{ now()->toDateString() }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">End Date *</label>
                <input type="date" name="end_date" min="{{ now()->toDateString() }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Reason / Justification *</label>
                <input type="text" name="reason" placeholder="e.g. Annual family vacation, fever MC"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
            </div>
            <div class="md:col-span-4 pt-1 flex justify-end">
                <button type="submit" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Submit Leave Request
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Main Requests Table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
            <div>
                <h2 class="text-base font-bold text-gray-900 tracking-tight">
                    {{ $isAdmin ? 'All Workshop Staff Leave Applications' : 'My Leave History' }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Approved, pending, and declined time-off records</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                {{ $requests->count() }} records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                        @if($isAdmin)
                        <th class="px-5 py-3.5">Staff Personnel</th>
                        @endif
                        <th class="px-5 py-3.5">Type</th>
                        <th class="px-5 py-3.5">Dates & Duration</th>
                        <th class="px-5 py-3.5">Reason</th>
                        <th class="px-5 py-3.5">Approval Status</th>
                        <th class="px-5 py-3.5">Admin Remarks</th>
                        @if($isAdmin)
                        <th class="px-5 py-3.5 text-right w-36">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $r)
                    @php
                        $days = $r->days_count;
                        $datesStr = $r->start_date->format('d M Y') . ' – ' . $r->end_date->format('d M Y');
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition">
                        @if($isAdmin)
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($r->staff->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-gray-900">{{ $r->staff->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $r->staff->staff_role ?? ucfirst($r->staff->role ?? '') }}</p>
                                </div>
                            </div>
                        </td>
                        @endif

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border
                                {{ $r->leave_type === 'medical' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                  ($r->leave_type === 'emergency' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                  ($r->leave_type === 'unpaid' ? 'bg-gray-100 text-gray-700 border-gray-300' :
                                  'bg-blue-50 text-blue-700 border-blue-200')) }}">
                                {{ $r->leave_type_label }}
                            </span>
                        </td>

                        <td class="px-5 py-4 text-xs font-semibold text-gray-800">
                            <p class="text-sm font-bold text-gray-900">{{ $datesStr }}</p>
                            <p class="text-gray-500 font-medium mt-0.5">{{ $days }} {{ \Illuminate\Support\Str::plural('day', $days) }}</p>
                        </td>

                        <td class="px-5 py-4 text-xs text-gray-700 max-w-56">
                            {{ $r->reason }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                                {{ $r->status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                  ($r->status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                  'bg-amber-50 text-amber-700 border-amber-200') }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $r->status === 'approved' ? 'bg-emerald-500' : ($r->status === 'rejected' ? 'bg-rose-500' : 'bg-amber-500') }}"></span>
                                {{ ucfirst($r->status) }}
                            </span>
                        </td>

                        <td class="px-5 py-4 text-xs text-gray-500 max-w-48 truncate" title="{{ $r->admin_notes ?? '' }}">
                            @if($r->admin_notes)
                                <span class="{{ $r->status === 'rejected' ? 'text-rose-600 font-medium' : 'text-gray-600' }}">{{ $r->admin_notes }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        @if($isAdmin)
                        <td class="px-5 py-4 text-right">
                            @if($r->status === 'pending')
                            <div class="flex items-center justify-end gap-1.5">
                                <form method="POST" action="/admin/staff-management/leave/{{ $r->id }}/approve">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg hover:bg-emerald-100 transition">
                                        Approve
                                    </button>
                                </form>
                                <button type="button"
                                    @click="openReject('{{ $r->id }}', '{{ addslashes($r->staff->name ?? '') }}', '{{ $datesStr }}')"
                                    class="text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 rounded-lg hover:bg-rose-100 transition">
                                    Decline
                                </button>
                            </div>
                            @else
                            <span class="text-xs text-gray-400 font-medium flex items-center justify-end gap-1">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span>Decided</span>
                            </span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 7 : 6 }}" class="py-12 text-center">
                            <x-empty-state icon="calendar-off" title="No leave applications found" subtitle="No staff members have submitted time-off requests." />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rejection Reason Modal --}}
    @if($isAdmin)
    <div x-show="showRejectModal" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-md w-full overflow-hidden"
            @click.outside="showRejectModal = false">
            <div class="bg-rose-900 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-300"></i>
                    <h3 class="font-bold text-sm text-white">Decline Leave Request</h3>
                </div>
                <button type="button" @click="showRejectModal = false" class="text-rose-200 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form :action="'/admin/staff-management/leave/' + rejectRequestId + '/reject'" method="POST" class="p-6 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <p class="text-xs text-gray-600 mb-1">Staff: <strong class="text-gray-900" x-text="rejectStaffName"></strong></p>
                    <p class="text-xs text-gray-600">Dates: <span class="font-semibold text-gray-900" x-text="rejectDates"></span></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Reason for Declining *</label>
                    <textarea name="admin_notes" x-model="adminNotes" rows="3" required
                        placeholder="e.g. Critical vehicle repair orders scheduled on bay, insufficient technician coverage"
                        class="w-full border border-gray-200 rounded-xl p-3 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 shadow-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showRejectModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-rose-600 text-white px-5 py-2.5 rounded-xl text-xs font-semibold hover:bg-rose-700 transition shadow-xs">
                        Confirm Decline
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
