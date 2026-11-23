@extends('layouts.app')
@section('page-title', 'Maintenance Alerts')

@section('content')

@php
    $role      = auth()->user()->role;
    $prefix    = match($role) { 'admin' => 'admin', 'corporate' => 'client', default => 'customer' };
    $unread    = $alerts->where('is_read', false)->count();
    $highUrge  = $alerts->where('urgency', 'high')->where('is_read', false)->count();
    $predicted = $alerts->where('source', 'predicted')->count();

    $alertsArray = $alerts->map(function ($a) use ($role, $prefix) {
        $due = $a->effective_due_date;
        $isOverdue = $due ? $due->isPast() : false;
        $days = $due ? abs((int) now()->startOfDay()->diffInDays($due->startOfDay(), false)) : 9999;
        
        $urgencyWeight = match($a->effective_urgency) {
            'high' => 3,
            'medium' => 2,
            default => 1,
        };

        $targetDate = $due ? $due->format('Y-m-d') : now()->addDays(3)->format('Y-m-d');
        $plate = $a->vehicle->plate_number ?? '—';
        $svc = $a->alert_type ?? 'Service';
        $ownerName = $a->vehicle->owner->name ?? '';

        $bookUrl = match($role) {
            'corporate'   => '/client/appointments/create?vehicle_id=' . ($a->vehicle_id ?? '') . '&service_type=' . urlencode($svc) . '&date=' . $targetDate,
            'individual'  => '/customer/appointments/create?vehicle_id=' . ($a->vehicle_id ?? '') . '&service_type=' . urlencode($svc) . '&date=' . $targetDate,
            'coordinator' => '/coordinator/appointments/walkin?plate_number=' . urlencode($plate) . '&service_type=' . urlencode($svc) . '&date=' . $targetDate . '&walkin_name=' . urlencode($ownerName),
            default       => '/admin/appointments/walkin?plate_number=' . urlencode($plate) . '&service_type=' . urlencode($svc) . '&date=' . $targetDate . '&walkin_name=' . urlencode($ownerName),
        };

        return [
            'id'                 => $a->id,
            'vehicle_plate'      => $plate,
            'vehicle_model'      => trim(($a->vehicle->brand ?? '') . ' ' . ($a->vehicle->model ?? '')),
            'owner_name'         => $ownerName,
            'is_fleet'           => $a->vehicle ? $a->vehicle->isFleet() : false,
            'company_name'       => $a->vehicle->company->name ?? ($a->vehicle->owner->company->name ?? null),
            'issue'              => $svc,
            'recommendation'     => $a->recommendation,
            'urgency'            => $a->effective_urgency,
            'urgency_weight'     => $urgencyWeight,
            'confidence_score'   => $a->confidence_score !== null ? (float) $a->confidence_score : null,
            'plain_confidence'   => $a->plain_confidence,
            'plain_agreement'    => $a->plain_agreement_rate,
            'predicted_due_date' => $due ? $due->format('d M Y') : null,
            'raw_due_date'       => $due ? $due->format('Y-m-d') : '9999-12-31',
            'is_overdue'         => $isOverdue,
            'days_remaining'     => $days,
            'verified_status'    => $a->plain_verification_status,
            'is_verified'        => $a->is_verified || $a->ml_staff_verified === 'verified',
            'is_read'            => $a->is_read,
            'source'             => $a->source,
            'read_url'           => "/{$prefix}/maintenance/{$a->id}/read",
            'verify_url'         => "/admin/predictions/{$a->id}/verify",
            'book_url'           => $bookUrl,
        ];
    });
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
            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $predicted }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Multi-model consensus</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600">
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
                Automated alerts are derived from multi-model consensus and recommended service thresholds —
                configure intervals in the <a href="/pricing/job-types" class="text-indigo-600 hover:underline">Job Types</a> directory.
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
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
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

{{-- ══════════════════════════════════════════════════════════════════════════
     COMPACT SORTABLE TABLE & ALERTS LOG
══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6"
     x-data="{
         viewMode: 'table',
         filter: 'all',
         search: '',
         sortCol: 'urgency',
         sortAsc: false,
         alerts: {{ Js::from($alertsArray) }},

         sortBy(col) {
             if (this.sortCol === col) {
                 this.sortAsc = !this.sortAsc;
             } else {
                 this.sortCol = col;
                 this.sortAsc = (col === 'due_date' || col === 'vehicle' || col === 'issue' || col === 'status');
             }
         },

         get sortedAlerts() {
             let list = this.alerts.slice();

             // Category Filter
             if (this.filter === 'high') {
                 list = list.filter(a => a.urgency === 'high');
             } else if (this.filter === 'medium') {
                 list = list.filter(a => a.urgency === 'medium');
             } else if (this.filter === 'low') {
                 list = list.filter(a => a.urgency === 'low');
             } else if (this.filter === 'unread') {
                 list = list.filter(a => !a.is_read);
             } else if (this.filter === 'predicted') {
                 list = list.filter(a => a.source === 'predicted');
             } else if (this.filter === 'verified') {
                 list = list.filter(a => a.is_verified);
             }

             // Text Search
             if (this.search && this.search.trim().length > 0) {
                 const q = this.search.toLowerCase().trim();
                 list = list.filter(a =>
                     (a.vehicle_plate && a.vehicle_plate.toLowerCase().includes(q)) ||
                     (a.vehicle_model && a.vehicle_model.toLowerCase().includes(q)) ||
                     (a.owner_name && a.owner_name.toLowerCase().includes(q)) ||
                     (a.company_name && a.company_name.toLowerCase().includes(q)) ||
                     (a.issue && a.issue.toLowerCase().includes(q)) ||
                     (a.recommendation && a.recommendation.toLowerCase().includes(q)) ||
                     (a.verified_status && a.verified_status.toLowerCase().includes(q))
                 );
             }

             // Sorting
             list.sort((a, b) => {
                 let va, vb;
                 if (this.sortCol === 'vehicle') {
                     va = a.vehicle_plate || '';
                     vb = b.vehicle_plate || '';
                 } else if (this.sortCol === 'issue') {
                     va = a.issue || '';
                     vb = b.issue || '';
                 } else if (this.sortCol === 'urgency') {
                     va = a.urgency_weight || 0;
                     vb = b.urgency_weight || 0;
                 } else if (this.sortCol === 'confidence') {
                     va = a.confidence_score !== null ? a.confidence_score : -1;
                     vb = b.confidence_score !== null ? b.confidence_score : -1;
                 } else if (this.sortCol === 'due_date') {
                     va = a.raw_due_date || '9999-12-31';
                     vb = b.raw_due_date || '9999-12-31';
                 } else if (this.sortCol === 'status') {
                     va = a.verified_status || '';
                     vb = b.verified_status || '';
                 } else {
                     va = a.urgency_weight || 0;
                     vb = b.urgency_weight || 0;
                 }

                 if (va < vb) return this.sortAsc ? -1 : 1;
                 if (va > vb) return this.sortAsc ? 1 : -1;
                 return 0;
             });

             return list;
         }
     }">

    {{-- Header & View Toggle Controls --}}
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
            <p class="text-xs text-gray-500 mt-0.5">Compact, sortable view of fleet alerts with multi-model consensus and verification status</p>
        </div>

        <div class="flex items-center gap-2 no-print flex-wrap">
            {{-- Quick Search Input --}}
            <div class="relative">
                <input type="text" x-model="search" placeholder="Filter plate, vehicle, issue..."
                       class="text-xs border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 w-48 sm:w-60 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="11" cy="11" r="8" stroke-width="2"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"></line>
                </svg>
            </div>

            {{-- Table / Cards View Toggle --}}
            <div class="flex items-center bg-gray-100 p-0.5 rounded-lg text-xs">
                <button type="button" @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-white text-gray-900 font-semibold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                    class="px-2.5 py-1 rounded-md transition">
                    Table View
                </button>
                <button type="button" @click="viewMode = 'cards'"
                    :class="viewMode === 'cards' ? 'bg-white text-gray-900 font-semibold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                    class="px-2.5 py-1 rounded-md transition">
                    Cards View
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

    {{-- Filter Tabs --}}
    <div class="flex items-center gap-1.5 mb-4 flex-wrap no-print">
        <button type="button" @click="filter='all'"
            :class="filter==='all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <span>All Alerts</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='all' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $alerts->count() }}
            </span>
        </button>
        <button type="button" @click="filter='high'"
            :class="filter==='high' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span>High Urgency</span>
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
            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-500"></i>
            <span>Projected</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='predicted' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $predicted }}
            </span>
        </button>
        <button type="button" @click="filter='verified'"
            :class="filter==='verified' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-500"></i>
            <span>Verified</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded-full"
                :class="filter==='verified' ? 'bg-slate-800 text-slate-200' : 'bg-gray-100 text-gray-600'">
                {{ $alerts->where('is_verified', true)->count() }}
            </span>
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         COMPACT SORTABLE TABLE (Phase 4 Supervisor Design)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="viewMode === 'table'" class="overflow-x-auto rounded-xl border border-gray-200 mb-4 bg-white">
        <table class="w-full text-left text-xs min-w-[820px]">
            <thead class="bg-gray-50/90 border-b border-gray-200 text-gray-600 uppercase text-[10px] tracking-wider font-bold select-none">
                <tr>
                    {{-- 1. Vehicle --}}
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('vehicle')">
                        <div class="flex items-center gap-1.5">
                            <span>Vehicle</span>
                            <span class="text-gray-400" :class="sortCol === 'vehicle' ? 'text-indigo-600 font-bold' : ''">
                                <span x-show="sortCol !== 'vehicle'">↕</span>
                                <span x-show="sortCol === 'vehicle' && sortAsc">↑</span>
                                <span x-show="sortCol === 'vehicle' && !sortAsc">↓</span>
                            </span>
                        </div>
                    </th>

                    {{-- 2. Issue --}}
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('issue')">
                        <div class="flex items-center gap-1.5">
                            <span>Issue / Service</span>
                            <span class="text-gray-400" :class="sortCol === 'issue' ? 'text-indigo-600 font-bold' : ''">
                                <span x-show="sortCol !== 'issue'">↕</span>
                                <span x-show="sortCol === 'issue' && sortAsc">↑</span>
                                <span x-show="sortCol === 'issue' && !sortAsc">↓</span>
                            </span>
                        </div>
                    </th>

                    {{-- 3. Urgency Badge --}}
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('urgency')">
                        <div class="flex items-center gap-1.5">
                            <span>Urgency</span>
                            <span class="text-gray-400" :class="sortCol === 'urgency' ? 'text-indigo-600 font-bold' : ''">
                                <span x-show="sortCol !== 'urgency'">↕</span>
                                <span x-show="sortCol === 'urgency' && sortAsc">↑</span>
                                <span x-show="sortCol === 'urgency' && !sortAsc">↓</span>
                            </span>
                        </div>
                    </th>

                    {{-- 4. Confidence & Agreement --}}
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('confidence')">
                        <div class="flex items-center gap-1.5">
                            <span>Confidence & Agreement</span>
                            <span class="text-gray-400" :class="sortCol === 'confidence' ? 'text-indigo-600 font-bold' : ''">
                                <span x-show="sortCol !== 'confidence'">↕</span>
                                <span x-show="sortCol === 'confidence' && sortAsc">↑</span>
                                <span x-show="sortCol === 'confidence' && !sortAsc">↓</span>
                            </span>
                        </div>
                    </th>

                    {{-- 5. Predicted Due Date --}}
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('due_date')">
                        <div class="flex items-center gap-1.5">
                            <span>Predicted Due Date</span>
                            <span class="text-gray-400" :class="sortCol === 'due_date' ? 'text-indigo-600 font-bold' : ''">
                                <span x-show="sortCol !== 'due_date'">↕</span>
                                <span x-show="sortCol === 'due_date' && sortAsc">↑</span>
                                <span x-show="sortCol === 'due_date' && !sortAsc">↓</span>
                            </span>
                        </div>
                    </th>

                    {{-- 6. Verified Status --}}
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('status')">
                        <div class="flex items-center gap-1.5">
                            <span>Verified Status</span>
                            <span class="text-gray-400" :class="sortCol === 'status' ? 'text-indigo-600 font-bold' : ''">
                                <span x-show="sortCol !== 'status'">↕</span>
                                <span x-show="sortCol === 'status' && sortAsc">↑</span>
                                <span x-show="sortCol === 'status' && !sortAsc">↓</span>
                            </span>
                        </div>
                    </th>

                    {{-- Expert Verification for Print Form --}}
                    <th class="py-3 px-4 print:table-cell hidden">Mechanic Validation</th>

                    {{-- 7. Actions --}}
                    <th class="py-3 px-4 text-right no-print">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="item in sortedAlerts" :key="item.id">
                    <tr class="hover:bg-gray-50/80 transition" :class="item.is_read ? 'opacity-60 bg-gray-50/30' : ''">
                        {{-- Vehicle --}}
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-gray-900 font-mono text-xs" x-text="item.vehicle_plate"></span>
                                <template x-if="item.is_fleet">
                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">
                                        Fleet
                                    </span>
                                </template>
                                <template x-if="!item.is_fleet && item.owner_name">
                                    <span class="text-[9px] font-medium px-1.5 py-0.2 rounded bg-gray-100 text-gray-600 border border-gray-200">
                                        Individual
                                    </span>
                                </template>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                <span x-text="item.vehicle_model"></span>
                                <template x-if="item.company_name">
                                    <span x-text="'• ' + item.company_name"></span>
                                </template>
                                <template x-if="!item.company_name && item.owner_name">
                                    <span x-text="'• ' + item.owner_name"></span>
                                </template>
                            </p>
                        </td>

                        {{-- Issue --}}
                        <td class="py-3 px-4">
                            <p class="font-bold text-gray-900 text-xs" x-text="item.issue"></p>
                            <p class="text-[11px] text-gray-500 line-clamp-1 max-w-xs mt-0.5" x-text="item.recommendation"></p>
                        </td>

                        {{-- Urgency Badge --}}
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider"
                                  :class="{
                                      'bg-rose-50 text-rose-700 border border-rose-200': item.urgency === 'high',
                                      'bg-amber-50 text-amber-700 border border-amber-200': item.urgency === 'medium',
                                      'bg-slate-100 text-slate-700 border border-slate-200': item.urgency === 'low'
                                  }"
                                  x-text="item.urgency">
                            </span>
                        </td>

                        {{-- Confidence & Agreement (Plain Language) --}}
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-gray-800" x-text="item.plain_confidence"></span>
                                    <template x-if="item.source === 'predicted'">
                                        <span class="text-[9px] bg-indigo-50 text-indigo-700 border border-indigo-200 px-1 py-0.2 rounded font-semibold">
                                            AI
                                        </span>
                                    </template>
                                </div>
                                <template x-if="item.plain_agreement">
                                    <p class="text-[10px] text-indigo-700 font-semibold" x-text="item.plain_agreement"></p>
                                </template>
                            </div>
                        </td>

                        {{-- Predicted Due Date --}}
                        <td class="py-3 px-4 whitespace-nowrap">
                            <template x-if="item.predicted_due_date">
                                <div>
                                    <p class="font-bold text-gray-900 text-xs" x-text="item.predicted_due_date"></p>
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold mt-0.5"
                                          :class="item.is_overdue ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                                          x-text="item.is_overdue ? ('Overdue by ' + item.days_remaining + 'd') : ('In ~' + item.days_remaining + 'd')">
                                    </span>
                                </div>
                            </template>
                            <template x-if="!item.predicted_due_date">
                                <span class="text-gray-400">—</span>
                            </template>
                        </td>

                        {{-- Verified Status --}}
                        <td class="py-3 px-4 whitespace-nowrap">
                            <template x-if="item.verified_status === 'Verified'">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Verified</span>
                                </span>
                            </template>
                            <template x-if="item.verified_status === 'Flagged'">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span>Flagged</span>
                                </span>
                            </template>
                            <template x-if="item.verified_status !== 'Verified' && item.verified_status !== 'Flagged'">
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-500 bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full">
                                    <span>Pending Review</span>
                                </span>
                            </template>
                        </td>

                        {{-- Print validation checklist --}}
                        <td class="py-3 px-4 print:table-cell hidden text-center border-l border-gray-200">
                            <div class="text-[11px] text-gray-700 space-y-1">
                                <span class="border px-1.5 py-0.5 mr-2">[ ] Agree</span>
                                <span class="border px-1.5 py-0.5">[ ] Disagree</span>
                                <p class="text-[9px] text-gray-400 mt-2">Sign: ____________</p>
                            </div>
                        </td>

                        {{-- Action Buttons --}}
                        <td class="py-3 px-4 text-right whitespace-nowrap no-print">
                            <div class="flex items-center justify-end gap-1.5">
                                <a :href="item.book_url"
                                   class="inline-flex items-center gap-1 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white px-2.5 py-1.5 rounded-lg shadow-xs transition"
                                   title="Book appointment for this vehicle and service">
                                    <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                                    <span>Book Now</span>
                                </a>

                                <template x-if="!item.is_read">
                                    <form method="POST" :action="item.read_url">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-xs text-gray-600 hover:text-gray-900 border border-gray-200 hover:bg-gray-50 px-2.5 py-1.5 rounded-lg transition">
                                            Mark Read
                                        </button>
                                    </form>
                                </template>

                                @if($role === 'admin')
                                <template x-if="!item.is_verified">
                                    <form method="POST" :action="item.verify_url">
                                        @csrf
                                        <input type="hidden" name="admin_notes" value="Verified in Alerts Review">
                                        <button type="submit" class="text-xs text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2 py-1.5 rounded-lg font-bold transition"
                                                title="Mark verified ground-truth">
                                            Verify
                                        </button>
                                    </form>
                                </template>
                                @endif
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        {{-- Empty Table State --}}
        <template x-if="sortedAlerts.length === 0">
            <div class="py-12 text-center text-gray-400">
                <i data-lucide="filter-x" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                <p class="text-xs font-semibold text-gray-700">No maintenance alerts match the selected criteria.</p>
                <button type="button" @click="filter = 'all'; search = ''" class="mt-2 text-xs text-indigo-600 font-bold hover:underline">
                    Reset Filter & Search
                </button>
            </div>
        </template>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ALTERNATIVE CARDS VIEW
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="viewMode === 'cards'" style="display:none">
        <template x-for="item in sortedAlerts" :key="item.id">
            <div class="bg-white border border-gray-200 rounded-xl p-4 mb-3 shadow-2xs hover:shadow-sm transition"
                 :class="{
                     'border-l-4 border-l-rose-500': item.urgency === 'high',
                     'border-l-4 border-l-amber-500': item.urgency === 'medium',
                     'border-l-4 border-l-slate-400': item.urgency === 'low',
                     'opacity-50': item.is_read
                 }">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1.5">
                            <span class="font-bold text-sm text-gray-900 font-mono tracking-tight" x-text="item.vehicle_plate"></span>
                            <span class="font-normal text-gray-500 text-xs ml-1" x-text="item.vehicle_model"></span>

                            <span class="text-[10px] px-2 py-0.5 rounded font-extrabold uppercase tracking-wider"
                                  :class="{
                                      'bg-rose-50 text-rose-700 border border-rose-200': item.urgency === 'high',
                                      'bg-amber-50 text-amber-700 border border-amber-200': item.urgency === 'medium',
                                      'bg-slate-100 text-slate-700 border border-slate-200': item.urgency === 'low'
                                  }"
                                  x-text="item.urgency">
                            </span>

                            <span class="text-[10px] px-2 py-0.5 rounded font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                  x-text="item.plain_confidence"></span>

                            <template x-if="item.plain_agreement">
                                <span class="text-[10px] px-2 py-0.5 rounded font-semibold bg-indigo-50/70 text-indigo-800 border border-indigo-100"
                                      x-text="item.plain_agreement"></span>
                            </template>

                            <template x-if="item.verified_status === 'Verified'">
                                <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full font-bold">
                                    Verified
                                </span>
                            </template>
                        </div>

                        <p class="text-sm font-semibold text-gray-900" x-text="item.issue"></p>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed" x-text="item.recommendation"></p>

                        <template x-if="item.predicted_due_date">
                            <p class="text-xs text-indigo-700 mt-1.5 font-medium flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500"></i>
                                <span x-text="'Predicted Target: ' + item.predicted_due_date"></span>
                            </p>
                        </template>
                    </div>

                    <div class="flex flex-col gap-2 items-end shrink-0">
                        <a :href="item.book_url"
                           class="inline-flex items-center gap-1.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg shadow-xs transition">
                            <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                            <span>Book Now</span>
                        </a>

                        <template x-if="!item.is_read">
                            <form method="POST" :action="item.read_url">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-50 whitespace-nowrap transition">
                                    Mark Read
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="sortedAlerts.length === 0">
            <div class="py-12 text-center text-gray-400">
                <i data-lucide="bell-off" class="w-12 h-12 text-gray-200 mx-auto mb-3"></i>
                <p class="text-gray-400">No maintenance alerts match the selected criteria.</p>
            </div>
        </template>
    </div>
</div>

@endsection
