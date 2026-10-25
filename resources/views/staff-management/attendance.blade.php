@extends('layouts.app')
@section('page-title', 'Staff Attendance')

@section('content')

@php
    $currentCarbon = \Carbon\Carbon::parse($date);
    $prevDate = $currentCarbon->copy()->subDay()->toDateString();
    $nextDate = $currentCarbon->copy()->addDay()->toDateString();
    $isToday  = $currentCarbon->isToday();
@endphp

<div x-data="{
    showAdjustModal: false,
    modalStaffId: '',
    modalStaffName: '',
    modalDate: '{{ $date }}',
    modalStatus: 'present',
    modalClockIn: '08:30',
    modalClockOut: '17:30',
    modalNotes: '',
    openAdjust(staffId, staffName, status, clockIn, clockOut, notes) {
        this.modalStaffId = staffId;
        this.modalStaffName = staffName;
        this.modalStatus = status || 'present';
        this.modalClockIn = clockIn || '08:30';
        this.modalClockOut = clockOut || '17:30';
        this.modalNotes = notes || '';
        this.showAdjustModal = true;
    }
}">

    {{-- Subnavigation Tabs --}}
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="/admin/staff-management/roster" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Team Directory & Positions</a>
        <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs">Attendance Daily</a>
        <a href="/admin/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 flex items-center gap-1.5">
            <span>Leave Requests</span>
            @php $pendingLeave = \App\Models\LeaveRequest::where('status', 'pending')->count(); @endphp
            @if($pendingLeave > 0)
            <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $pendingLeave }}</span>
            @endif
        </a>
        <a href="/admin/staff-management/performance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Performance & KPIs</a>
        <a href="/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Payroll & Salary</a>
    </div>

    {{-- Header & Date Navigator --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Staff Daily Attendance</h2>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                    {{ $currentCarbon->format('l, d F Y') }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Daily clock-in, duty duration, and punctuality logs for workshop mechanics & coordinators</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Date Prev/Next Navigator --}}
            <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl p-1 shadow-xs">
                <a href="/admin/staff-management/attendance?date={{ $prevDate }}"
                    class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-700 transition" title="Previous Day">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>

                <form method="GET" action="/admin/staff-management/attendance" class="flex items-center">
                    <input type="date" name="date" value="{{ $date }}"
                        class="border-0 px-2 py-1 text-xs font-bold text-gray-900 bg-transparent focus:outline-none cursor-pointer"
                        onchange="this.form.submit()">
                </form>

                <a href="/admin/staff-management/attendance?date={{ $nextDate }}"
                    class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-700 transition" title="Next Day">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if(!$isToday)
            <a href="/admin/staff-management/attendance?date={{ now()->toDateString() }}"
                class="px-3 py-2 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 shadow-xs transition">
                Today
            </a>
            @endif

            {{-- Manual Log Button --}}
            <button type="button" @click="openAdjust('', '', 'present', '08:30', '17:30', '')"
                class="bg-slate-900 text-white px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Log / Adjust Attendance</span>
            </button>

            {{-- Export CSV Dropdown --}}
            <div class="relative" x-data="{ exportMenu: false }">
                <button type="button" @click="exportMenu = !exportMenu"
                    class="bg-white border border-gray-200 text-gray-700 px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-gray-50 transition shadow-xs flex items-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4 text-emerald-600"></i>
                    <span>Export CSV</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>
                <div x-show="exportMenu" @click.outside="exportMenu = false" x-transition
                    class="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-xl shadow-lg py-1 z-20 text-xs">
                    <a href="/admin/staff-management/attendance/export?date={{ $date }}&scope=day"
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-50 font-medium">
                        Export This Day ({{ $currentCarbon->format('d M') }})
                    </a>
                    <a href="/admin/staff-management/attendance/export?date={{ $date }}&scope=month"
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-50 font-medium border-t border-gray-100">
                        Export Full Month ({{ $currentCarbon->format('F Y') }})
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Metrics Summary Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Workshop</span>
            <p class="text-2xl font-extrabold text-gray-900 mt-2">{{ $stats['totalStaff'] }}</p>
            <span class="text-[11px] text-gray-400 mt-0.5">active staff</span>
        </div>
        <div class="bg-white rounded-xl border border-emerald-200/80 shadow-xs p-4 flex flex-col justify-between bg-emerald-50/20">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Present
            </span>
            <p class="text-2xl font-extrabold text-emerald-700 mt-2">{{ $stats['presentCount'] }}</p>
            <span class="text-[11px] text-emerald-600 mt-0.5">on floor duty</span>
        </div>
        <div class="bg-white rounded-xl border border-blue-200/80 shadow-xs p-4 flex flex-col justify-between bg-blue-50/20">
            <span class="text-xs font-bold uppercase tracking-wider text-blue-700 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span> On Leave
            </span>
            <p class="text-2xl font-extrabold text-blue-700 mt-2">{{ $stats['leaveCount'] }}</p>
            <span class="text-[11px] text-blue-600 mt-0.5">approved time off</span>
        </div>
        <div class="bg-white rounded-xl border border-amber-200/80 shadow-xs p-4 flex flex-col justify-between bg-amber-50/20">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Half Day
            </span>
            <p class="text-2xl font-extrabold text-amber-700 mt-2">{{ $stats['halfDayCount'] }}</p>
            <span class="text-[11px] text-amber-600 mt-0.5">partial shift</span>
        </div>
        <div class="bg-white rounded-xl border border-rose-200/80 shadow-xs p-4 flex flex-col justify-between bg-rose-50/20">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-700 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Absent
            </span>
            <p class="text-2xl font-extrabold text-rose-700 mt-2">{{ $stats['absentCount'] }}</p>
            <span class="text-[11px] text-rose-600 mt-0.5">recorded absence</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Not Clocked</span>
            <p class="text-2xl font-extrabold text-gray-400 mt-2">{{ $stats['unrecordedCount'] }}</p>
            <span class="text-[11px] text-gray-400 mt-0.5">pending punch</span>
        </div>
    </div>

    {{-- Main Attendance Table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
            <div>
                <h2 class="text-base font-bold text-gray-900 tracking-tight">
                    Roster Attendance &bull; {{ $currentCarbon->format('l, d F Y') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Workshop floor technician and customer coordinator shifts</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                {{ count($rows) }} personnel listed
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                        <th class="px-5 py-3.5">Personnel Name & Role</th>
                        <th class="px-5 py-3.5">Clock In</th>
                        <th class="px-5 py-3.5">Clock Out</th>
                        <th class="px-5 py-3.5">Duration</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Notes</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                    @php
                        $rec = $row->record;
                        $status = $rec->status ?? 'unrecorded';
                        $clockInTime = $rec && $rec->clock_in ? \Carbon\Carbon::parse($rec->clock_in)->format('h:i A') : '—';
                        $clockOutTime = $rec && $rec->clock_out ? \Carbon\Carbon::parse($rec->clock_out)->format('h:i A') : '—';
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($row->staff->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-gray-900 flex items-center gap-1.5">
                                        <span>{{ $row->staff->name }}</span>
                                        <span class="text-[10px] font-bold uppercase px-1.5 py-0.2 rounded border
                                            {{ $row->staff->role === 'coordinator' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                                            {{ $row->staff->role }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $row->staff->staff_role ?? ($row->staff->role === 'coordinator' ? 'Service Coordinator' : 'Mechanic') }}
                                        @if($row->staff->contact_no)
                                        &bull; <span class="font-mono text-gray-400">{{ $row->staff->contact_no }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-xs font-semibold text-gray-800">
                            <span class="font-mono">{{ $clockInTime }}</span>
                            @if($row->is_late)
                            <span class="ml-1 text-[10px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-1 py-0.2 rounded">Late</span>
                            @elseif($rec && $rec->clock_in)
                            <span class="ml-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-1 py-0.2 rounded">Punctual</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-xs font-semibold text-gray-800 font-mono">
                            {{ $clockOutTime }}
                        </td>
                        <td class="px-5 py-4 text-xs font-bold text-gray-700">
                            {{ $row->hours_worked ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                                {{ $status === 'present'  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                  ($status === 'leave'    ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                  ($status === 'half_day' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                  ($status === 'absent'   ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-gray-50 text-gray-500 border-gray-200'))) }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $status === 'present' ? 'bg-emerald-500' : ($status === 'leave' ? 'bg-blue-500' : ($status === 'half_day' ? 'bg-amber-500' : ($status === 'absent' ? 'bg-rose-500' : 'bg-gray-400'))) }}"></span>
                                {{ ucfirst(str_replace('_',' ',$status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-500 max-w-44 truncate" title="{{ $rec->notes ?? '' }}">
                            {{ $rec->notes ?? '—' }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button type="button"
                                @click="openAdjust(
                                    '{{ $row->staff->id }}',
                                    '{{ addslashes($row->staff->name) }}',
                                    '{{ $rec->status ?? 'present' }}',
                                    '{{ $rec && $rec->clock_in ? substr($rec->clock_in, 0, 5) : '08:30' }}',
                                    '{{ $rec && $rec->clock_out ? substr($rec->clock_out, 0, 5) : '17:30' }}',
                                    '{{ addslashes($rec->notes ?? '') }}'
                                )"
                                class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-2.5 py-1 rounded-lg shadow-xs transition inline-flex items-center gap-1">
                                <i data-lucide="edit-2" class="w-3 h-3 text-gray-400"></i>
                                <span>Adjust</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <x-empty-state icon="users" title="No staff records" subtitle="No workshop staff or coordinators found for this date." />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Manual Attendance Entry / Adjustment Modal --}}
    <div x-show="showAdjustModal" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        style="display:none;">
        
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-lg w-full overflow-hidden"
            @click.outside="showAdjustModal = false">
            
            <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-blue-400">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-sm text-white">Log / Adjust Attendance</h2>
                        <p class="text-[11px] text-slate-400">Manual clock punch override for workshop personnel</p>
                    </div>
                </div>
                <button type="button" @click="showAdjustModal = false" class="text-slate-400 hover:text-white p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="/admin/staff-management/attendance/record" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Staff Member *</label>
                    <select name="staff_id" x-model="modalStaffId"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
                        <option value="">Select staff member</option>
                        @foreach($staffList as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->staff_role ?? ucfirst($s->role) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Attendance Date *</label>
                        <input type="date" name="date" x-model="modalDate"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Status *</label>
                        <select name="status" x-model="modalStatus"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
                            <option value="present">Present (Hadir)</option>
                            <option value="half_day">Half Day (Separuh Hari)</option>
                            <option value="leave">On Leave (Bercuti)</option>
                            <option value="absent">Absent (Tidak Hadir)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3" x-show="modalStatus === 'present' || modalStatus === 'half_day'">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Clock In Time</label>
                        <input type="time" name="clock_in" x-model="modalClockIn"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Clock Out Time</label>
                        <input type="time" name="clock_out" x-model="modalClockOut"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Admin Note / Reason</label>
                    <input type="text" name="notes" x-model="modalNotes" placeholder="e.g. Off-site fleet inspection, medical emergency, overtime"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
                </div>

                <div class="flex justify-end items-center gap-2 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAdjustModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
                        Save Attendance Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
