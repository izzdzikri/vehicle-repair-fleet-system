@extends('layouts.app')
@section('page-title', 'Staff Directory & Workshop Positions')

@section('content')

@php
    $pendingLeave = \App\Models\LeaveRequest::where('status', 'pending')->count();
@endphp

<div x-data="{ roleFilter: 'all' }">

    {{-- Subnavigation Tabs --}}
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="/admin/staff-management/roster" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs">Team Directory & Positions</a>
        <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Attendance Daily</a>
        <a href="/admin/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 flex items-center gap-1.5">
            <span>Leave Requests</span>
            @if($pendingLeave > 0)
            <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $pendingLeave }}</span>
            @endif
        </a>
        <a href="/admin/staff-management/performance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Performance & KPIs</a>
        <a href="/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Payroll & Salary</a>
    </div>

    {{-- Header & Role Filter Pills --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Workshop Team Positions & Payroll Roster</h2>
            <p class="text-sm text-gray-500 mt-1">Personnel designations, technical specialties, job responsibilities, and monthly compensation</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center bg-white border border-gray-200 rounded-xl p-1 shadow-xs text-xs font-semibold">
                <button type="button" @click="roleFilter = 'all'"
                    :class="roleFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                    class="px-3 py-1.5 rounded-lg transition">
                    All Personnel ({{ count($staffList) }})
                </button>
                <button type="button" @click="roleFilter = 'staff'"
                    :class="roleFilter === 'staff' ? 'bg-slate-900 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                    class="px-3 py-1.5 rounded-lg transition">
                    Mechanics ({{ $mechanicsCount }})
                </button>
                <button type="button" @click="roleFilter = 'coordinator'"
                    :class="roleFilter === 'coordinator' ? 'bg-slate-900 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
                    class="px-3 py-1.5 rounded-lg transition">
                    Coordinators ({{ $coordinatorsCount }})
                </button>
            </div>

            <a href="/admin/users" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3.5 py-2 rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                <i data-lucide="user-plus" class="w-4 h-4 text-gray-500"></i>
                <span>Manage Users & Roles</span>
            </a>
        </div>
    </div>

    {{-- Overview Metrics Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Workshop Mechanics</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                </div>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $mechanicsCount }}</p>
                <p class="text-xs text-gray-400 font-medium mt-1">engine, suspension & diagnostic technicians</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-purple-200/80 shadow-xs p-5 bg-purple-50/20 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Service Coordinators</span>
                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-purple-700 tracking-tight">{{ $coordinatorsCount }}</p>
                <p class="text-xs text-purple-600 font-medium mt-1">front-desk reception & fleet dispatch</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-emerald-200/80 shadow-xs p-5 bg-emerald-50/20 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Monthly Team Payroll</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-extrabold text-emerald-700 tracking-tight">RM {{ number_format($totalPayroll, 2) }}</p>
                <p class="text-xs text-emerald-600 font-medium mt-1">monthly compensation obligation</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Annual Projection</span>
                <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($totalPayroll * 12, 2) }}</p>
                <p class="text-xs text-gray-400 font-medium mt-1">12-month payroll budget</p>
            </div>
        </div>
    </div>

    {{-- Staff Positions Roster Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($staffList as $s)
        <div x-show="roleFilter === 'all' || roleFilter === '{{ $s->role }}'" x-transition
            class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 hover:shadow-md transition flex flex-col justify-between">
            
            <div>
                {{-- Top Bar: Avatar, Name, Role Badge, Salary --}}
                <div class="flex items-start justify-between gap-4 mb-4 pb-4 border-b border-gray-100">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-slate-900 text-white font-extrabold text-base flex items-center justify-center shrink-0 shadow-xs">
                            {{ strtoupper(substr($s->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-extrabold text-gray-900">{{ $s->name }}</h3>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full border
                                    {{ $s->role === 'admin' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                      ($s->role === 'coordinator' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                      'bg-blue-50 text-blue-700 border-blue-200') }}">
                                    {{ $s->role }}
                                </span>
                            </div>

                            <p class="text-xs font-bold text-blue-700 mt-0.5 flex items-center gap-1.5">
                                <i data-lucide="badge-check" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span>{{ $s->staff_role ?? 'Workshop Technician' }}</span>
                            </p>

                            <p class="text-xs text-gray-400 font-mono mt-1 flex items-center gap-3">
                                <span>{{ $s->email }}</span>
                                @if($s->contact_no)
                                <span>&bull; {{ $s->contact_no }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Salary Box --}}
                    <div class="text-right shrink-0 bg-emerald-50/50 border border-emerald-200/60 rounded-xl px-3 py-2">
                        <span class="text-[10px] uppercase font-bold text-emerald-800 tracking-wider block">Payroll / Mo</span>
                        <p class="text-base font-extrabold text-emerald-700 font-mono mt-0.5">
                            RM {{ number_format($s->monthly_salary ?? 0, 2) }}
                        </p>
                    </div>
                </div>

                {{-- Responsibilities & Scope (What They Do) --}}
                <div class="mb-4">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Scope of Responsibilities</span>
                    <p class="text-xs text-gray-700 leading-relaxed bg-gray-50 p-3 rounded-xl border border-gray-100">
                        {{ $s->duties }}
                    </p>
                </div>

                {{-- Technical Specialties (Mapped to Workshop Job Types) --}}
                @if($s->role === 'staff' || $s->specialty_models->isNotEmpty())
                <div class="mb-4">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1.5">
                        Technical Specializations & Bay Operations
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse($s->specialty_models as $jt)
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold bg-blue-50 text-blue-800 border border-blue-200/80 px-2.5 py-1 rounded-lg">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            <span>{{ $jt->name }}</span>
                        </span>
                        @empty
                        <span class="text-xs text-gray-400 italic">No specific job types tagged. General workshop mechanic tasks.</span>
                        @endforelse
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer: Workload Metrics, Hire Date & Action Link --}}
            <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs flex-wrap gap-2 mt-2">
                <div class="flex items-center gap-4">
                    @if($s->role === 'staff')
                    <div>
                        <span class="text-gray-400 text-[10px] uppercase block font-semibold">Active Workload</span>
                        <span class="font-bold {{ $s->active_jobs > 0 ? 'text-blue-700' : 'text-gray-500' }}">
                            {{ $s->active_jobs }} bay {{ \Illuminate\Support\Str::plural('job', $s->active_jobs) }}
                        </span>
                    </div>
                    <div class="h-6 w-px bg-gray-200"></div>
                    <div>
                        <span class="text-gray-400 text-[10px] uppercase block font-semibold">Completed</span>
                        <span class="font-bold text-emerald-700">{{ $s->completed_jobs }} vehicles</span>
                    </div>
                    @else
                    <div>
                        <span class="text-gray-400 text-[10px] uppercase block font-semibold">Department</span>
                        <span class="font-bold text-purple-700">Customer Reception</span>
                    </div>
                    @endif
                    <div class="h-6 w-px bg-gray-200"></div>
                    <div>
                        <span class="text-gray-400 text-[10px] uppercase block font-semibold">Hire Date</span>
                        <span class="font-semibold text-gray-700 font-mono">
                            {{ $s->hire_date ? $s->hire_date->format('d M Y') : '—' }}
                        </span>
                    </div>
                </div>

                <div>
                    @if($s->role === 'staff')
                    <a href="/admin/job-cards?staff_id={{ $s->id }}"
                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        <span>View Job Cards</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                    @else
                    <a href="/admin/users/{{ $s->id }}"
                        class="text-xs font-semibold text-gray-600 hover:text-gray-900 flex items-center gap-1">
                        <span>User Profile</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
