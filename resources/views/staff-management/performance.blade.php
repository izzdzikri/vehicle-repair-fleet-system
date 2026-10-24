@extends('layouts.app')
@section('page-title', 'Staff Performance & Productivity')

@section('content')

{{-- Subnavigation Tabs --}}
<div class="flex gap-2 mb-6 flex-wrap">
    <a href="/admin/staff-management/roster" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Team Directory & Positions</a>
    <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Attendance Daily</a>
    <a href="/admin/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 flex items-center gap-1.5">
        <span>Leave Requests</span>
        @php $pendingLeave = \App\Models\LeaveRequest::where('status', 'pending')->count(); @endphp
        @if($pendingLeave > 0)
        <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $pendingLeave }}</span>
        @endif
    </a>
    <a href="/admin/staff-management/performance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs">Performance & KPIs</a>
    <a href="/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Payroll & Salary</a>
</div>

{{-- Header & Period Filter Toolbar --}}
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 flex-wrap">
    <div>
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Staff KPI & Productivity Ledger</h2>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 capitalize">
                {{ str_replace('_', ' ', $period) }}
            </span>
        </div>
        <p class="text-sm text-gray-500 mt-1">Aggregated repair turnaround, on-time delivery rate, punctuality, and customer ratings</p>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        {{-- Period Filter Pills --}}
        <div class="flex items-center bg-white border border-gray-200 rounded-xl p-1 shadow-xs text-xs font-semibold">
            <a href="/admin/staff-management/performance?period=this_month"
                class="px-3 py-1.5 rounded-lg transition {{ $period === 'this_month' ? 'bg-slate-900 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                This Month
            </a>
            <a href="/admin/staff-management/performance?period=last_month"
                class="px-3 py-1.5 rounded-lg transition {{ $period === 'last_month' ? 'bg-slate-900 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                Last Month
            </a>
            <a href="/admin/staff-management/performance?period=this_year"
                class="px-3 py-1.5 rounded-lg transition {{ $period === 'this_year' ? 'bg-slate-900 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                Year {{ now()->year }}
            </a>
            <a href="/admin/staff-management/performance?period=all"
                class="px-3 py-1.5 rounded-lg transition {{ $period === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                All Time
            </a>
        </div>

        {{-- Export CSV --}}
        <a href="/admin/staff-management/performance/export?period={{ $period }}"
            class="bg-emerald-600 text-white px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-emerald-700 transition shadow-xs flex items-center gap-1.5">
            <i data-lucide="download" class="w-4 h-4"></i> Export CSV
        </a>
    </div>
</div>

{{-- Workshop KPI Overview Summary Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Repairs Completed</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $kpis['totalCompleted'] }}</p>
            <p class="text-xs text-gray-400 font-medium mt-1">job cards finished</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-emerald-200/80 shadow-xs p-5 bg-emerald-50/20 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">On-Time Delivery</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <i data-lucide="clock" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-emerald-700 tracking-tight">
                {{ $kpis['overallOnTimePct'] !== null ? $kpis['overallOnTimePct'] . '%' : '—' }}
            </p>
            <p class="text-xs text-emerald-600 font-medium mt-1">{{ $kpis['totalOnTime'] }} jobs met schedule</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-purple-200/80 shadow-xs p-5 bg-purple-50/20 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Avg Turnaround</span>
            <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                <i data-lucide="timer" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-purple-700 tracking-tight">
                {{ $kpis['overallTurnaround'] !== null ? $kpis['overallTurnaround'] . 'h' : '—' }}
            </p>
            <p class="text-xs text-purple-600 font-medium mt-1">average repair duration</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-amber-200/80 shadow-xs p-5 bg-amber-50/20 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Workshop Rating</span>
            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                <i data-lucide="star" class="w-4 h-4 fill-amber-500 text-amber-500"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-amber-700 tracking-tight">
                {{ $kpis['overallRating'] !== null ? $kpis['overallRating'] . ' / 5.0' : '—' }}
            </p>
            <p class="text-xs text-amber-600 font-medium mt-1">customer satisfaction</p>
        </div>
    </div>
</div>

{{-- Performance Email Dispatch Form --}}
<form method="POST" action="/admin/staff-management/performance/email?period={{ $period }}"
    class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 mb-6 flex items-center justify-between gap-4 flex-wrap">
    @csrf
    <label class="flex items-center gap-2 text-xs font-semibold text-gray-700 cursor-pointer">
        <input type="checkbox" name="notify_staff" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        <span>Also dispatch individual performance statements directly to technicians' emails</span>
    </label>
    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2 ml-auto">
        <i data-lucide="mail" class="w-4 h-4"></i> Email Report to Management
    </button>
</form>

{{-- Main Personnel KPI Table --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
        <div>
            <h2 class="text-base font-bold text-gray-900 tracking-tight">Personnel Productivity Breakdown</h2>
            <p class="text-xs text-gray-500 mt-0.5">Individual metrics across workshop mechanics and service coordinators</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
            {{ count($report) }} team members
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <th class="px-5 py-3.5">Personnel Name & Role</th>
                    <th class="px-5 py-3.5">Completed Jobs</th>
                    <th class="px-5 py-3.5">On-Time Delivery</th>
                    <th class="px-5 py-3.5">Active Bay Jobs</th>
                    <th class="px-5 py-3.5">Avg Turnaround</th>
                    <th class="px-5 py-3.5">Attendance Rate</th>
                    <th class="px-5 py-3.5 text-right">Customer Rating</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($report as $r)
                <tr class="hover:bg-gray-50/60 transition">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($r->staff->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-sm text-gray-900 flex items-center gap-1.5">
                                    <span>{{ $r->staff->name }}</span>
                                    <span class="text-[10px] font-bold uppercase px-1.5 py-0.2 rounded border
                                        {{ $r->staff->role === 'coordinator' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                                        {{ $r->staff->role }}
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $r->staff->staff_role ?? ($r->staff->role === 'coordinator' ? 'Service Coordinator' : 'Mechanic') }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 font-bold text-sm text-gray-900">
                        {{ $r->total_jobs }}
                    </td>

                    <td class="px-5 py-4">
                        @if($r->total_jobs > 0)
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-emerald-700">{{ $r->on_time }}</span>
                            <span class="text-xs text-gray-400">/ {{ $r->total_jobs }}</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded border
                                {{ $r->on_time_pct >= 85 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                  ($r->on_time_pct >= 70 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200') }}">
                                {{ $r->on_time_pct }}%
                            </span>
                        </div>
                        @if($r->overdue > 0)
                        <p class="text-[11px] text-rose-600 font-semibold mt-0.5">{{ $r->overdue }} delayed</p>
                        @endif
                        @else
                        <span class="text-gray-400 text-xs font-medium">—</span>
                        @endif
                    </td>

                    <td class="px-5 py-4">
                        @if($r->staff->role === 'staff')
                        <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-full border
                            {{ $r->active_jobs_count > 0 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200' }}">
                            {{ $r->active_jobs_count }} active
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">Reception</span>
                        @endif
                    </td>

                    <td class="px-5 py-4 text-xs font-semibold text-gray-700">
                        {{ $r->avg_hours !== null ? $r->avg_hours . ' hrs' : '—' }}
                    </td>

                    <td class="px-5 py-4">
                        @if($r->attendance_rate !== null)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border
                            {{ $r->attendance_rate >= 90 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                              ($r->attendance_rate >= 75 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200') }}">
                            {{ $r->attendance_rate }}%
                        </span>
                        @else
                        <span class="text-gray-400 text-xs font-medium">—</span>
                        @endif
                    </td>

                    <td class="px-5 py-4 text-right">
                        @if($r->avg_rating !== null)
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                            <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-500 text-amber-500"></i>
                            <span>{{ $r->avg_rating }} / 5.0</span>
                        </span>
                        @else
                        <span class="text-gray-400 text-xs font-medium">No reviews</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center">
                        <x-empty-state icon="award" title="No performance data" subtitle="Completed repair job cards and attendance records will generate productivity metrics." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
