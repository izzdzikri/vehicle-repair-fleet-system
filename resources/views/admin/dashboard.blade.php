@extends('layouts.app')
@section('page-title', 'Admin Dashboard')

@section('content')

@include('partials.greeting')

{{-- Stat Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 text-center hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-2">
            <i data-lucide="car" class="w-5 h-5"></i>
        </div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Vehicles</p>
        <p class="text-3xl font-extrabold text-blue-600 tracking-tight">{{ $totalVehicles }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 text-center hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-2">
            <i data-lucide="users" class="w-5 h-5"></i>
        </div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Users</p>
        <p class="text-3xl font-extrabold text-purple-600 tracking-tight">{{ $totalUsers }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 text-center hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center mx-auto mb-2">
            <i data-lucide="clipboard-list" class="w-5 h-5"></i>
        </div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pending Jobs</p>
        <p class="text-3xl font-extrabold text-yellow-600 tracking-tight">{{ $pendingJobs }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 text-center hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-2">
            <i data-lucide="package" class="w-5 h-5"></i>
        </div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Low Stock</p>
        <p class="text-3xl font-extrabold text-red-600 tracking-tight">{{ $lowStock }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 text-center hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
            <i data-lucide="calendar" class="w-5 h-5"></i>
        </div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Today's Apts</p>
        <p class="text-3xl font-extrabold text-emerald-600 tracking-tight">{{ $todayAppointments }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 text-center hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-2">
            <i data-lucide="clock" class="w-5 h-5"></i>
        </div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pending Apts</p>
        <p class="text-3xl font-extrabold text-amber-600 tracking-tight">{{ $pendingAppts }}</p>
        @if($pendingAppts > 0)
        <a href="/admin/appointments?status=pending" class="text-xs font-semibold text-blue-600 hover:underline mt-1 block">Review →</a>
        @endif
    </div>
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 text-center hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg {{ $staleJobs > 0 ? 'bg-rose-50 text-rose-600' : 'bg-gray-50 text-gray-400' }} flex items-center justify-center mx-auto mb-2">
            <i data-lucide="alarm-clock" class="w-5 h-5"></i>
        </div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Needs Update</p>
        <p class="text-3xl font-extrabold {{ $staleJobs > 0 ? 'text-rose-600' : 'text-gray-400' }} tracking-tight">{{ $staleJobs }}</p>
        @if($staleJobs > 0)
        <a href="/admin/job-cards/schedule" class="text-xs font-semibold text-blue-600 hover:underline mt-1 block">Check in →</a>
        @endif
    </div>
</div>

{{-- ------------------------------------------------------------- --}}
{{-- Predictive Fleet Health & Maintenance Radar                   --}}
{{-- ------------------------------------------------------------- --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0 text-blue-600">
                <i data-lucide="activity" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-900 tracking-tight">Fleet Predictive Maintenance Radar</h2>
                    <span class="text-[10px] bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider">
                        Heuristic Monitoring
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    Continuous monitoring based on logged odometer usage, trip averages, and service intervals.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-between sm:justify-end">
            <form method="POST" action="/admin/maintenance/predict" class="inline">
                @csrf
                <button type="submit"
                    class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-xs flex items-center gap-1.5 transition">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Run Scanner
                </button>
            </form>
            <a href="/admin/maintenance" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                View all alerts <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
        </div>
    </div>

    {{-- Gauge & Risk Matrix Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- Fleet Health Scorecard --}}
        <div class="bg-slate-50 border border-gray-200 rounded-xl p-4 flex items-center gap-4">
            <div class="relative w-16 h-16 flex items-center justify-center shrink-0">
                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                    <path class="text-gray-200" stroke-width="3.5" stroke="currentColor" fill="none"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="{{ $fleetHealthScore >= 80 ? 'text-emerald-500' : ($fleetHealthScore >= 60 ? 'text-amber-500' : 'text-rose-500') }}"
                        stroke-dasharray="{{ $fleetHealthScore }}, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div class="absolute text-center">
                    <span class="text-sm font-bold text-gray-900">{{ $fleetHealthScore }}%</span>
                </div>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Fleet Health</p>
                <p class="text-xs font-semibold text-gray-800 mt-0.5">
                    {{ $fleetHealthScore >= 80 ? 'Optimal' : ($fleetHealthScore >= 60 ? 'Moderate Attention' : 'High Risk') }}
                </p>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ $totalVehicles - $vehiclesAtRisk }} of {{ $totalVehicles }} vehicles on track
                </p>
            </div>
        </div>

        {{-- Urgent / Overdue Card --}}
        <div class="bg-rose-50/60 border border-rose-200/80 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-rose-700 font-bold flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Overdue Services
                </span>
                <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-500"></i>
            </div>
            <p class="text-2xl font-black text-rose-700 mt-2">{{ $urgentAlerts }}</p>
            <p class="text-[11px] text-rose-600/80 mt-1">Immediate workshop intake needed</p>
        </div>

        {{-- Due Soon Card --}}
        <div class="bg-amber-50/60 border border-amber-200/80 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-amber-700 font-bold flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500"></i> Due in &le;14 Days
                </span>
                <i data-lucide="calendar" class="w-4 h-4 text-amber-500"></i>
            </div>
            <p class="text-2xl font-black text-amber-700 mt-2">{{ $mediumAlerts }}</p>
            <p class="text-[11px] text-amber-600/80 mt-1">Schedule within next two weeks</p>
        </div>

        {{-- Healthy Fleet Card --}}
        <div class="bg-emerald-50/60 border border-emerald-200/80 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-emerald-700 font-bold flex items-center gap-1.5">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500"></i> Healthy Fleet
                </span>
                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
            </div>
            <p class="text-2xl font-black text-emerald-700 mt-2">{{ max(0, $totalVehicles - $vehiclesAtRisk) }}</p>
            <p class="text-[11px] text-emerald-600/80 mt-1">Running within interval bounds</p>
        </div>
    </div>

    {{-- Predictive Demand & Off-Season Savings Banner (Admin Only) --}}
    @if(isset($offSeasonSavings) && $offSeasonSavings['total_savings'] > 0)
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl mb-6 border border-indigo-800/40 relative overflow-hidden">
        {{-- Subtle background decoration --}}
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -top-10 w-36 h-36 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-1.5 max-w-xl">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Admin Predictive Procurement Engine
                    </span>
                    <span class="text-xs text-indigo-200/80 font-medium">
                        {{ $offSeasonSavings['seasonal_cycle']['name'] }}
                    </span>
                </div>
                <h3 class="text-xl font-black text-white tracking-tight">
                    Upcoming Parts Demand & Off-Season Cost Savings
                </h3>
                <p class="text-xs text-indigo-100/80 leading-relaxed">
                    {{ $offSeasonSavings['seasonal_cycle']['description'] }} Projecting parts demand 30–90 days ahead locks in pre-peak supplier discounts (12%–20%) before seasonal spot price spikes.
                </p>
            </div>

            <div class="flex items-center gap-3.5 flex-wrap sm:flex-nowrap">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3.5 border border-white/10 text-center min-w-32">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-200 block">Potential Savings</span>
                    <span class="text-2xl font-black text-emerald-400 font-mono block mt-0.5">
                        RM {{ number_format($offSeasonSavings['total_savings'], 2) }}
                    </span>
                    <span class="text-[10px] text-emerald-300/90 font-semibold">Save {{ $offSeasonSavings['overall_savings_pct'] }}%</span>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3.5 border border-white/10 text-center min-w-32">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-200 block">Off-Season Window</span>
                    <span class="text-sm font-bold text-white block mt-1">
                        {{ $offSeasonSavings['seasonal_cycle']['off_season_end'] }}
                    </span>
                    <span class="text-[10px] text-amber-300/90 font-medium">Order before peak</span>
                </div>

                <a href="/admin/predictions/proofread?filter=all"
                    class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-3 rounded-xl text-xs font-bold shadow-lg transition flex items-center gap-2 whitespace-nowrap shrink-0">
                    <i data-lucide="trending-down" class="w-4 h-4"></i>
                    <span>Review Savings &amp; Draft PO</span>
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Predictive Projections Table --}}
    @if($upcomingPredictions->count())
    <div class="mt-4" x-data="{ viewMode: 'table' }">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="list" class="w-4 h-4 text-gray-500"></i>
                Actionable Fleet Projections (Glance View)
            </p>
            <div class="flex items-center gap-1 bg-gray-100 p-0.5 rounded-lg text-xs">
                <button type="button" @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-white text-gray-900 font-semibold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                    class="px-2.5 py-1 rounded transition">
                    Table View
                </button>
                <button type="button" @click="viewMode = 'cards'"
                    :class="viewMode === 'cards' ? 'bg-white text-gray-900 font-semibold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                    class="px-2.5 py-1 rounded transition">
                    Cards View
                </button>
            </div>
        </div>

        {{-- TABLE VIEW (Fast reading for busy admins) --}}
        <div x-show="viewMode === 'table'" class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="w-full text-left text-xs min-w-[720px]">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase text-[10px] tracking-wider font-semibold">
                    <tr>
                        <th class="py-3 px-4">Vehicle</th>
                        <th class="py-3 px-4">Predicted Service</th>
                        <th class="py-3 px-4">Target Due Date</th>
                        <th class="py-3 px-4">Status & Countdown</th>
                        <th class="py-3 px-4">Confidence / Urgency</th>
                        <th class="py-3 px-4 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($upcomingPredictions as $alert)
                    @php
                        $isOverdue = $alert->predicted_due_date && \Carbon\Carbon::parse($alert->predicted_due_date)->isPast();
                        $dueCarbon = $alert->predicted_due_date ? \Carbon\Carbon::parse($alert->predicted_due_date) : null;
                        $daysDiff = $dueCarbon ? abs(now()->startOfDay()->diffInDays($dueCarbon->startOfDay(), false)) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50/80 transition {{ $isOverdue ? 'bg-rose-50/20' : '' }}">
                        <td class="py-3 px-4">
                            <p class="font-bold text-gray-900 text-sm tracking-tight font-mono">{{ $alert->vehicle->plate_number ?? '—' }}</p>
                            <p class="text-[11px] text-gray-500">
                                {{ $alert->vehicle->brand ?? '' }} {{ $alert->vehicle->model ?? '' }} &bull;
                                <span>{{ $alert->vehicle->owner->name ?? 'Customer' }}</span>
                            </p>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-semibold text-gray-900 text-xs">{{ $alert->jobType->name ?? $alert->alert_type }}</span>
                            @if($alert->jobType && $alert->jobType->interval_km)
                            <p class="text-[10px] text-gray-400 mt-0.5">Every {{ number_format($alert->jobType->interval_km) }} km</p>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($dueCarbon)
                            <p class="font-medium text-gray-800">{{ $dueCarbon->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $dueCarbon->format('l') }}</p>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($dueCarbon)
                                @if($isOverdue)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Overdue by {{ $daysDiff }}d
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    <i data-lucide="clock" class="w-3 h-3 text-amber-500"></i> In ~{{ $daysDiff }}d
                                </span>
                                @endif
                            @else
                            <span class="text-gray-400">Ongoing</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                {{ $alert->urgency === 'high' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                                  ($alert->urgency === 'medium' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                                  'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                {{ ucfirst($alert->urgency) }} Priority
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="/admin/appointments?search={{ $alert->vehicle->plate_number ?? '' }}"
                                class="inline-flex items-center gap-1 text-xs bg-slate-900 hover:bg-slate-800 text-white font-medium px-3 py-1.5 rounded-lg shadow-xs transition">
                                Action <i data-lucide="chevron-right" class="w-3 h-3"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- CARDS VIEW (Alternative view) --}}
        <div x-show="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5" style="display:none">
            @foreach($upcomingPredictions as $alert)
            @php
                $isOverdue = $alert->predicted_due_date && \Carbon\Carbon::parse($alert->predicted_due_date)->isPast();
                $dueCarbon = $alert->predicted_due_date ? \Carbon\Carbon::parse($alert->predicted_due_date) : null;
                $daysDiff = $dueCarbon ? abs(now()->startOfDay()->diffInDays($dueCarbon->startOfDay(), false)) : 0;
            @endphp
            <div class="bg-gray-50/60 hover:bg-white hover:shadow-sm transition border border-gray-200 rounded-xl p-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div>
                            <p class="font-bold text-gray-900 text-sm font-mono tracking-tight">{{ $alert->vehicle->plate_number ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $alert->vehicle->brand ?? '' }} {{ $alert->vehicle->model ?? '' }}</p>
                        </div>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border
                            {{ $alert->urgency === 'high' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                               ($alert->urgency === 'medium' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                               'bg-slate-100 text-slate-700 border-slate-200') }}">
                            {{ ucfirst($alert->urgency) }}
                        </span>
                    </div>

                    <div class="bg-white border border-gray-200/80 rounded-lg p-2.5 mb-2">
                        <p class="font-semibold text-xs text-gray-900">{{ $alert->jobType->name ?? $alert->alert_type }}</p>
                        <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2 leading-relaxed">
                            {{ $alert->recommendation }}
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-200/80 flex items-center justify-between mt-2">
                    <div class="text-[11px]">
                        @if($dueCarbon)
                        <span class="{{ $isOverdue ? 'text-rose-600 font-semibold' : 'text-gray-600' }}">
                            {{ $isOverdue ? "Overdue by {$daysDiff}d" : "Due in ~{$daysDiff}d" }}
                        </span>
                        &bull; {{ $dueCarbon->format('d M') }}
                        @else
                        <span class="text-gray-400">Target: Ongoing</span>
                        @endif
                    </div>
                    <a href="/admin/appointments?search={{ $alert->vehicle->plate_number ?? '' }}"
                        class="text-xs bg-slate-900 text-white hover:bg-slate-800 font-medium px-2.5 py-1 rounded-md transition shadow-xs">
                        Action &rarr;
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center text-gray-600">
        <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500 mx-auto mb-2"></i>
        <p class="text-sm font-semibold text-gray-900">All Clear! No Pending Predictive Alerts</p>
        <p class="text-xs text-gray-500 mt-1">The system continuously evaluates trip logs and service gaps to project upcoming needs.</p>
    </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Recent Job Cards --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
            <div>
                <h2 class="text-base font-bold text-gray-900 tracking-tight">Recent Job Cards</h2>
                <p class="text-xs text-gray-500 mt-0.5">Active shop floor repair orders in progress</p>
            </div>
            <a href="/admin/job-cards" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-100 transition">
                View all job cards &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100 bg-gray-50/60">
                        <th class="py-2.5 px-3 rounded-l">ID</th>
                        <th class="py-2.5 px-3">Vehicle Plate</th>
                        <th class="py-2.5 px-3">Assigned Staff</th>
                        <th class="py-2.5 px-3">Stage</th>
                        <th class="py-2.5 px-3 text-right rounded-r">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentJobs as $job)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="py-3 px-3 font-mono text-xs font-semibold text-gray-500">#{{ $job->id }}</td>
                        <td class="py-3 px-3">
                            <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded border border-gray-200">
                                {{ $job->vehicle->plate_number ?? '—' }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-sm font-medium text-gray-800">{{ $job->staff->name ?? 'Unassigned' }}</td>
                        <td class="py-3 px-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                                {{ $job->current_stage === 'completed'  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                  ($job->current_stage === 'repairing'  ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                  'bg-amber-50 text-amber-700 border-amber-200') }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $job->current_stage === 'completed' ? 'bg-emerald-500' : ($job->current_stage === 'repairing' ? 'bg-blue-500' : 'bg-amber-500') }}"></span>
                                {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                            </span>
                            @if($job->is_stale)
                            <span class="inline-flex items-center text-xs font-bold text-rose-600 ml-1" title="No update in {{ $job->hours_since_update }}h">
                                ⏰ Stale
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-right">
                            <a href="/admin/job-cards/{{ $job->id }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                Details &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-4">
                        <x-empty-state icon="clipboard-list" title="No job cards yet" subtitle="Confirm an appointment, then create a job card from it." />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Staff Workload --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
        <div>
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900 tracking-tight">Staff Workload</h2>
                <a href="/admin/users" class="text-xs font-semibold text-gray-500 hover:text-gray-900">Manage</a>
            </div>
            <div class="space-y-4">
                @forelse($staffWorkload as $s)
                <div class="p-3 rounded-lg border border-gray-100 bg-gray-50/50">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-gray-900">{{ $s->name }}</span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $s->active_jobs >= 5 ? 'bg-rose-100 text-rose-700' : ($s->active_jobs >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                            {{ $s->active_jobs }} active {{ Str::plural('job', $s->active_jobs) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-300 {{ $s->active_jobs >= 5 ? 'bg-rose-500' : ($s->active_jobs >= 3 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                            style="width: {{ min(100, $s->active_jobs * 20) }}%"></div>
                    </div>
                </div>
                @empty
                <x-empty-state icon="user-cog" title="No staff accounts yet" subtitle="Add a staff account to start assigning jobs." action-href="/admin/users" action-label="+ Add User" />
                @endforelse
            </div>
        </div>
        <div class="pt-4 border-t border-gray-100 mt-4 text-xs text-gray-500 flex items-center justify-between">
            <span>Capacity guide:</span>
            <span class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span> Optimal
                <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span> Heavy
                <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span> Overload
            </span>
        </div>
    </div>

</div>

{{-- Quick Actions --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
    <div class="mb-4">
        <h2 class="text-base font-bold text-gray-900 tracking-tight">Operational Quick Actions</h2>
        <p class="text-xs text-gray-500 mt-0.5">Fast shortcuts to everyday fleet operations</p>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="/admin/appointments/queue" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 bg-white hover:border-blue-400 hover:shadow-xs hover:bg-blue-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-blue-700">Today's Queue</span>
        </a>
        <a href="/admin/job-cards/schedule" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 bg-white hover:border-purple-400 hover:shadow-xs hover:bg-purple-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition">
                <i data-lucide="list-ordered" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-purple-700">Job Schedule</span>
        </a>
        <a href="/admin/invoices" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 bg-white hover:border-emerald-400 hover:shadow-xs hover:bg-emerald-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                <i data-lucide="receipt" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-emerald-700">Invoices</span>
        </a>
        <a href="/admin/purchase-orders" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 bg-white hover:border-amber-400 hover:shadow-xs hover:bg-amber-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition">
                <i data-lucide="shopping-cart" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-amber-700">Purchase Orders</span>
        </a>
        <a href="/admin/suppliers" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 bg-white hover:border-cyan-400 hover:shadow-xs hover:bg-cyan-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-cyan-50 text-cyan-600 group-hover:bg-cyan-600 group-hover:text-white transition">
                <i data-lucide="truck" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-cyan-700">Suppliers</span>
        </a>
        <a href="/admin/staff-management/attendance" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 bg-white hover:border-rose-400 hover:shadow-xs hover:bg-rose-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-rose-50 text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition">
                <i data-lucide="user-cog" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-rose-700">Staff Mgmt</span>
        </a>
    </div>
</div>

@endsection