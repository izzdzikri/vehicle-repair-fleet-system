@extends('layouts.app')
@section('page-title', 'Maintenance Alerts')

@section('content')

@php
    $role     = auth()->user()->role;
    $prefix   = match($role) { 'admin' => 'admin', 'corporate' => 'client', default => 'customer' };
    $unread   = $alerts->where('is_read', false)->count();
    $highUrge = $alerts->where('urgency', 'high')->where('is_read', false)->count();
    $predicted = $alerts->where('source', 'predicted')->count();
@endphp

{{-- Summary bar --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex items-center justify-between">
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Alerts</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $alerts->count() }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Fleet alert backlog</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-600">
            <i data-lucide="bell" class="w-5 h-5"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex items-center justify-between">
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Unread</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $unread }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Awaiting staff review</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600">
            <i data-lucide="inbox" class="w-5 h-5"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex items-center justify-between">
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">High Urgency</p>
            <p class="text-2xl font-bold text-rose-600 mt-1">{{ $highUrge }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Requires prompt action</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600">
            <i data-lucide="alert-octagon" class="w-5 h-5"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex items-center justify-between">
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Predicted</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $predicted }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Algorithm projections</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600">
            <i data-lucide="sparkles" class="w-5 h-5"></i>
        </div>
    </div>
</div>

{{-- Admin can create manual alerts + trigger predictions --}}
@if($role === 'admin')
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6">
    <div class="flex justify-between items-start flex-wrap gap-4 mb-4">
        <div>
            <h2 class="text-base font-bold text-gray-900">Create Manual Alert</h2>
            <p class="text-xs text-gray-500 mt-1">
                Automated alerts are derived from odometer velocity and recommended service thresholds —
                configure intervals in the <a href="/pricing/job-types" class="text-blue-600 hover:underline">Job Types</a> directory.
            </p>
        </div>
        <form method="POST" action="/admin/maintenance/predict" class="shrink-0">
            @csrf
            <button type="submit"
                class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 shadow-xs transition">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Run Scanner
            </button>
        </form>
    </div>
    <form method="POST" action="/admin/maintenance" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Vehicle</label>
            <select name="vehicle_id"
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required>
                <option value="">Select vehicle</option>
                @foreach($vehicles as $v)
                <option value="{{ $v->id }}">
                    {{ $v->plate_number }}
                    — {{ $v->brand }} {{ $v->model }}
                    @if($v->owner) ({{ $v->owner->name }}) @endif
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Alert Type</label>
            <select name="alert_type"
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required>
                <option value="">Select type</option>
                <option value="Oil Change Due">Oil Change Due</option>
                <option value="Tyre Rotation Due">Tyre Rotation Due</option>
                <option value="Brake Inspection">Brake Inspection</option>
                <option value="Battery Check">Battery Check</option>
                <option value="Air Filter Replacement">Air Filter Replacement</option>
                <option value="Engine Service Due">Engine Service Due</option>
                <option value="Coolant Flush">Coolant Flush</option>
                <option value="Transmission Service">Transmission Service</option>
                <option value="Road Tax Expiry">Road Tax Expiry</option>
                <option value="Insurance Renewal">Insurance Renewal</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Urgency</label>
            <select name="urgency"
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required>
                <option value="low">Low — informational</option>
                <option value="medium" selected>Medium — schedule soon</option>
                <option value="high">High — action required</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Recommendation</label>
            <input type="text" name="recommendation"
                placeholder="e.g. Schedule oil change within 500 km or 1 month"
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required>
        </div>
        <div class="md:col-span-2 pt-1">
            <button type="submit"
                class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Create Alert
            </button>
        </div>
    </form>
</div>
@endif

{{-- Print-only header for workshop evaluation --}}
<div class="hidden print:block mb-6 pb-4 border-b">
    <h1 class="text-xl font-bold text-gray-900">Teraju Setia Enterprise — Predictive Maintenance Validation Sheet</h1>
    <p class="text-xs text-gray-500 mt-0.5">Date: {{ now()->format('d M Y, H:i') }} &bull; Workshop Verification & Assessment Form</p>
    <p class="text-xs text-gray-600 mt-1">
        Please verify if the predicted due dates and recommended service items match real workshop mechanical inspection.
    </p>
</div>

{{-- Alerts list --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6" x-data="{ filter: 'all', viewMode: 'table' }">
    <div class="flex justify-between items-center mb-5 flex-wrap gap-3 pb-3 border-b border-gray-100">
        <div>
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <span>Fleet Maintenance Log</span>
                @if($unread > 0)
                <span class="text-[11px] bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded-full font-semibold">
                    {{ $unread }} unread
                </span>
                @endif
            </h2>
            <p class="text-xs text-gray-400 mt-0.5">Real-time status of triggered and heuristic maintenance alerts</p>
        </div>

        <div class="flex items-center gap-2 no-print">
            <div class="flex items-center bg-gray-100 p-0.5 rounded-lg text-xs">
                <button type="button" @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-white text-gray-900 font-semibold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                    class="px-2.5 py-1 rounded-md transition">
                    Table View
                </button>
                <button type="button" @click="viewMode = 'cards'"
                    :class="viewMode === 'cards' ? 'bg-white text-gray-900 font-semibold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                    class="px-2.5 py-1 rounded-md transition">
                    Card View
                </button>
            </div>

            <button type="button" onclick="window.print()"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-gray-300 text-gray-700 rounded-lg text-xs font-semibold hover:bg-gray-100 transition shadow-xs"
                title="Print this sheet for mechanic/workshop field validation">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-gray-600"></i>
                <span>Print Validation Sheet</span>
            </button>
        </div>
    </div>

    {{-- Filter tabs --}}
    <div class="flex items-center gap-1.5 mb-4 flex-wrap no-print">
        <button type="button" @click="filter='all'"
            :class="filter==='all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <span>All</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='all' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $alerts->count() }}
            </span>
        </button>
        <button type="button" @click="filter='high'"
            :class="filter==='high' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span>High Priority</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='high' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $alerts->where('urgency','high')->count() }}
            </span>
        </button>
        <button type="button" @click="filter='medium'"
            :class="filter==='medium' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span>Medium</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='medium' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $alerts->where('urgency','medium')->count() }}
            </span>
        </button>
        <button type="button" @click="filter='low'"
            :class="filter==='low' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
            <span>Low</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='low' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $alerts->where('urgency','low')->count() }}
            </span>
        </button>
        <button type="button" @click="filter='unread'"
            :class="filter==='unread' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <span>Unread</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='unread' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $unread }}
            </span>
        </button>
        <button type="button" @click="filter='predicted'"
            :class="filter==='predicted' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-blue-500"></i>
            <span>Projected</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='predicted' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $predicted }}
            </span>
        </button>
    </div>

    {{-- High Density Scannable Table View (Fast reading) --}}
    <div x-show="viewMode === 'table'" class="overflow-x-auto rounded-xl border border-gray-200 mb-4 bg-white">
        <table class="w-full text-left text-xs min-w-[760px]">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase text-[10px] tracking-wider font-semibold">
                <tr>
                    <th class="py-3 px-4">Vehicle</th>
                    <th class="py-3 px-4">Service Projected</th>
                    <th class="py-3 px-4">Predicted Due</th>
                    <th class="py-3 px-4">Urgency / Confidence</th>
                    <th class="py-3 px-4">Recommendation</th>
                    <th class="py-3 px-4 print:table-cell hidden">Expert Verification</th>
                    <th class="py-3 px-4 text-right no-print">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($alerts as $alert)
                <tr class="hover:bg-gray-50/80 transition {{ $alert->is_read ? 'opacity-60 bg-gray-50/40' : '' }}"
                    x-show="filter==='all' || filter===$alert->urgency || (filter==='unread' && {{ $alert->is_read ? 'false' : 'true' }}) || (filter==='predicted' && {{ $alert->source === 'predicted' ? 'true' : 'false' }})">
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <p class="font-bold text-gray-900 text-sm font-mono tracking-tight">{{ $alert->vehicle->plate_number ?? '—' }}</p>
                            @if($alert->vehicle)
                                @if($alert->vehicle->isFleet())
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                        <i data-lucide="building-2" class="w-3 h-3 text-amber-600"></i> Fleet: {{ $alert->vehicle->company->name ?? ($alert->vehicle->owner->company->name ?? 'Corporate') }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                        <i data-lucide="user" class="w-3 h-3 text-emerald-600"></i> Individual
                                    </span>
                                @endif
                            @endif
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            {{ $alert->vehicle->brand ?? '' }} {{ $alert->vehicle->model ?? '' }}
                            @if($alert->vehicle && $alert->vehicle->owner) &bull; {{ $alert->vehicle->owner->name }} @endif
                        </p>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="font-semibold text-gray-900 text-xs">{{ $alert->alert_type }}</span>
                        @if($alert->source === 'predicted')
                        <span class="inline-flex items-center gap-1 text-[10px] text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.2 rounded font-medium mt-0.5">
                            <i data-lucide="sparkles" class="w-2.5 h-2.5 text-blue-500"></i> Auto-Projected
                        </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        @if($alert->predicted_due_date)
                        @php
                            $isOverdue = $alert->predicted_due_date->isPast();
                            $days = abs(now()->startOfDay()->diffInDays($alert->predicted_due_date->startOfDay(), false));
                        @endphp
                        <p class="font-medium {{ $isOverdue ? 'text-rose-600 font-semibold' : 'text-gray-800' }}">
                            {{ $alert->predicted_due_date->format('d M Y') }}
                        </p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold mt-0.5
                            {{ $isOverdue ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                            {{ $isOverdue ? "Overdue by {$days}d" : "In ~{$days}d" }}
                        </span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                            {{ $alert->urgency === 'high' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                              ($alert->urgency === 'medium' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                              'bg-slate-100 text-slate-700 border border-slate-200') }}">
                            {{ ucfirst($alert->urgency) }}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-gray-600 max-w-xs">
                        <p class="line-clamp-2 text-xs leading-relaxed text-gray-600">{{ $alert->recommendation }}</p>
                    </td>

                    {{-- Expert Verification Column for Printable Sheet --}}
                    <td class="py-3.5 px-4 print:table-cell hidden text-center border-l border-gray-200">
                        <div class="text-[11px] text-gray-700 space-y-1">
                            <span class="border px-1.5 py-0.5 mr-2">[ ] Agree</span>
                            <span class="border px-1.5 py-0.5">[ ] Disagree</span>
                            <p class="text-[9px] text-gray-400 mt-2">Sign: ____________</p>
                        </div>
                    </td>

                    <td class="py-3.5 px-4 text-right whitespace-nowrap no-print">
                        <div class="flex items-center justify-end gap-1.5">
                            @php
                                $targetDate = $alert->effective_due_date ? \Carbon\Carbon::parse($alert->effective_due_date)->format('Y-m-d') : now()->addDays(3)->format('Y-m-d');
                                $bookUrl = match($role) {
                                    'corporate'   => '/client/appointments/create?vehicle_id=' . ($alert->vehicle_id ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate,
                                    'individual'  => '/customer/appointments/create?vehicle_id=' . ($alert->vehicle_id ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate,
                                    'coordinator' => '/coordinator/appointments/walkin?plate_number=' . urlencode($alert->vehicle->plate_number ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate . '&walkin_name=' . urlencode($alert->vehicle->owner->name ?? ''),
                                    default       => '/admin/appointments/walkin?plate_number=' . urlencode($alert->vehicle->plate_number ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate . '&walkin_name=' . urlencode($alert->vehicle->owner->name ?? ''),
                                };
                            @endphp
                            <a href="{{ $bookUrl }}"
                                class="inline-flex items-center gap-1.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg shadow-xs transition"
                                title="Book appointment for this vehicle and service">
                                <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                                <span>Book Now</span>
                            </a>
                            @if(!$alert->is_read)
                            <form method="POST" action="/{{ $prefix }}/maintenance/{{ $alert->id }}/read">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-gray-600 hover:text-gray-900 border border-gray-200 hover:bg-gray-50 px-2.5 py-1.5 rounded-lg transition">
                                    Mark Read
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Cards View --}}
    <div x-show="viewMode === 'cards'" style="display:none">

    @forelse($alerts as $alert)
    @php
        $borderClass = match($alert->urgency) {
            'high'   => 'border-l-4 border-l-rose-500',
            'medium' => 'border-l-4 border-l-amber-500',
            default  => 'border-l-4 border-l-slate-400',
        };
        $badgeClass = match($alert->urgency) {
            'high'   => 'bg-rose-50 text-rose-700 border border-rose-200',
            'medium' => 'bg-amber-50 text-amber-700 border border-amber-200',
            default  => 'bg-slate-100 text-slate-700 border border-slate-200',
        };
    @endphp
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-3 shadow-2xs hover:shadow-sm transition {{ $borderClass }} {{ $alert->is_read ? 'opacity-50' : '' }}"
        x-show="filter==='all' || filter===$alert->urgency || (filter==='unread' && {{ $alert->is_read ? 'false' : 'true' }}) || (filter==='predicted' && {{ $alert->source === 'predicted' ? 'true' : 'false' }})">
        <div class="flex justify-between items-start gap-3">
            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap mb-1.5">
                    <span class="font-bold text-sm text-gray-900 font-mono tracking-tight">
                        {{ $alert->vehicle->plate_number ?? '—' }}
                        <span class="font-normal text-gray-500 text-xs ml-1 font-sans">
                            {{ $alert->vehicle->brand ?? '' }} {{ $alert->vehicle->model ?? '' }}
                        </span>
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider {{ $badgeClass }}">
                        {{ ucfirst($alert->urgency) }}
                    </span>
                    @if($alert->source === 'predicted')
                    <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full font-medium bg-blue-50 text-blue-700 border border-blue-200">
                        <i data-lucide="sparkles" class="w-2.5 h-2.5 text-blue-500"></i> Projected
                    </span>
                    @endif
                    @if($alert->is_read)
                    <span class="text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">Read</span>
                    @else
                    <span class="text-[10px] text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full font-semibold">Unread</span>
                    @endif
                    @if($alert->vehicle)
                        @if($alert->vehicle->isFleet())
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                            <i data-lucide="building-2" class="w-3 h-3 text-amber-600"></i> Fleet: {{ $alert->vehicle->company->name ?? ($alert->vehicle->owner->company->name ?? 'Corporate') }}
                        </span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                            <i data-lucide="user" class="w-3 h-3 text-emerald-600"></i> Individual: {{ $alert->vehicle->owner->name ?? 'Unassigned' }}
                        </span>
                        @endif
                    @endif
                </div>
                <p class="text-sm font-semibold text-gray-900">{{ $alert->alert_type }}</p>
                <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ $alert->recommendation }}</p>
                @if($alert->predicted_due_date)
                <p class="text-xs text-blue-700 mt-1.5 font-medium flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-blue-500"></i>
                    <span>Predicted target: {{ $alert->predicted_due_date->format('d M Y') }}</span>
                </p>
                @endif
                <p class="text-[11px] text-gray-400 mt-1">
                    Created {{ $alert->created_at->diffForHumans() }} &bull; {{ $alert->created_at->format('d M Y') }}
                </p>
            </div>
            <div class="flex flex-col gap-2 items-end shrink-0">
                @php
                    $targetDate = $alert->effective_due_date ? \Carbon\Carbon::parse($alert->effective_due_date)->format('Y-m-d') : now()->addDays(3)->format('Y-m-d');
                    $bookUrl = match($role) {
                        'corporate'   => '/client/appointments/create?vehicle_id=' . ($alert->vehicle_id ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate,
                        'individual'  => '/customer/appointments/create?vehicle_id=' . ($alert->vehicle_id ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate,
                        'coordinator' => '/coordinator/appointments/walkin?plate_number=' . urlencode($alert->vehicle->plate_number ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate . '&walkin_name=' . urlencode($alert->vehicle->owner->name ?? ''),
                        default       => '/admin/appointments/walkin?plate_number=' . urlencode($alert->vehicle->plate_number ?? '') . '&service_type=' . urlencode($alert->alert_type) . '&date=' . $targetDate . '&walkin_name=' . urlencode($alert->vehicle->owner->name ?? ''),
                    };
                @endphp
                <a href="{{ $bookUrl }}"
                    class="inline-flex items-center gap-1.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg shadow-xs transition"
                    title="Book appointment for this vehicle and service">
                    <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                    <span>Book Now</span>
                </a>
                @if(!$alert->is_read)
                <form method="POST" action="/{{ $prefix }}/maintenance/{{ $alert->id }}/read">
                    @csrf @method('PATCH')
                    <button class="text-xs bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-50 whitespace-nowrap transition">
                        Mark read
                    </button>
                </form>
                @endif
                @if($role === 'admin')
                <a href="/admin/vehicles/{{ $alert->vehicle_id }}"
                    class="text-xs text-blue-600 hover:text-blue-800 font-semibold whitespace-nowrap">
                    View vehicle &rarr;
                </a>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-12">
        <i data-lucide="bell-off" class="w-12 h-12 text-gray-200 mx-auto mb-3"></i>
        <p class="text-gray-400">No maintenance alerts for your vehicles.</p>
        @if($role !== 'admin')
        <p class="text-gray-300 text-xs mt-1">Alerts will appear here when the workshop flags your vehicle.</p>
        @endif
    </div>
    @endforelse
    </div>
</div>

@endsection