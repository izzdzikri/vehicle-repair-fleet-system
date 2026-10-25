@extends('layouts.app')
@section('page-title', 'Vehicle Detail')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @php
        $base = match(auth()->user()->role) {
            'admin'       => '/admin',
            'staff'       => '/admin',
            'corporate'   => '/client',
            'individual'  => '/customer',
            default       => '/customer',
        };
    @endphp
    <div>
        <a href="{{ $base }}/vehicles" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Vehicles
        </a>
    </div>

    {{-- Vehicle Header --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <div class="flex justify-between items-start mb-6 pb-5 border-b border-gray-100 flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="font-mono text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight bg-gray-100 px-3 py-1 rounded-lg border border-gray-200 inline-block">
                        {{ $vehicle->plate_number }}
                    </span>

                    {{-- Ownership Badge --}}
                    @if($vehicle->isFleet())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200 shadow-xs">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-amber-600"></i> Corporate Fleet
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-xs">
                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-600"></i> Individual Customer
                    </span>
                    @endif
                </div>

                <p class="text-base font-bold text-gray-800 mt-2">{{ $vehicle->brand }} {{ $vehicle->model }} &bull; Model Year {{ $vehicle->year }}</p>

                {{-- Ownership Details Banner --}}
                <div class="mt-3 p-3.5 rounded-xl border {{ $vehicle->isFleet() ? 'bg-amber-50/50 border-amber-200/70 text-amber-950' : 'bg-emerald-50/40 border-emerald-200/70 text-emerald-950' }}">
                    <div class="flex items-center justify-between flex-wrap gap-2 text-xs">
                        <div>
                            <span class="font-bold uppercase tracking-wider text-[11px] {{ $vehicle->isFleet() ? 'text-amber-800' : 'text-emerald-800' }}">
                                Account Ownership:
                            </span>
                            @if($vehicle->isFleet() && $vehicle->company)
                                <strong class="text-gray-900 text-sm ml-1">{{ $vehicle->company->name }}</strong>
                                @if($vehicle->company->registration_number)
                                    <span class="font-mono text-gray-500">({{ $vehicle->company->registration_number }})</span>
                                @endif
                                <span class="mx-1 text-gray-400">&bull;</span>
                                <span class="text-gray-600">Fleet PIC: <strong>{{ $vehicle->owner->name ?? 'Unassigned' }}</strong></span>
                            @else
                                <strong class="text-gray-900 text-sm ml-1">{{ $vehicle->owner->name ?? 'Unassigned Owner' }}</strong>
                            @endif
                        </div>
                        <div class="text-gray-600 font-medium">
                            <i data-lucide="phone" class="w-3 h-3 inline text-gray-400"></i>
                            {{ $vehicle->owner->contact_no ?? 'No phone recorded' }} &bull; {{ $vehicle->owner->email ?? 'No email' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $base }}/vehicles/{{ $vehicle->id }}/edit"
                    class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-xs font-semibold hover:bg-gray-50 transition shadow-xs flex items-center gap-1.5">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit Details
                </a>
            </div>
        </div>

        {{-- Stats & Fleet Health Score --}}
        @php
            $healthScore = $telemetry['health_score'] ?? 100;
            $healthColor = match(true) {
                $healthScore >= 80 => ['text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'badge' => 'bg-emerald-100 text-emerald-800', 'label' => 'Healthy Condition'],
                $healthScore >= 55 => ['text' => 'text-amber-600',   'bg' => 'bg-amber-50',   'border' => 'border-amber-200',   'badge' => 'bg-amber-100 text-amber-800',   'label' => 'Maintenance Due'],
                default            => ['text' => 'text-rose-600',    'bg' => 'bg-rose-50',    'border' => 'border-rose-200',    'badge' => 'bg-rose-100 text-rose-800',    'label' => 'Critical Action Required'],
            };
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
            {{-- Fleet Health Score (Top Viva Upgrade) --}}
            <div class="{{ $healthColor['bg'] }} border {{ $healthColor['border'] }} rounded-xl p-4 text-center col-span-2 lg:col-span-1 shadow-xs">
                <p class="text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1 flex items-center justify-center gap-1">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 {{ $healthColor['text'] }}"></i> Health Score
                </p>
                <div class="flex items-baseline justify-center gap-1">
                    <span class="text-3xl font-black {{ $healthColor['text'] }} tracking-tight">{{ $healthScore }}</span>
                    <span class="text-xs font-bold text-gray-500">/100</span>
                </div>
                <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full mt-1 {{ $healthColor['badge'] }}">
                    {{ $healthColor['label'] }}
                </span>
            </div>

            <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-4 text-center">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Odometer</p>
                <p class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ number_format($vehicle->mileage) }}</p>
                <p class="text-xs text-gray-400 font-medium">km recorded</p>
            </div>
            <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-4 text-center">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Service Visits</p>
                <p class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $totalVisits }}</p>
                <p class="text-xs text-gray-400 font-medium">completed jobs</p>
            </div>
            <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-4 text-center">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Lifetime Spent</p>
                <p class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($totalSpent, 0) }}</p>
                <p class="text-xs text-gray-400 font-medium">maintenance total</p>
            </div>
            <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-4 text-center">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Last Serviced</p>
                <p class="text-sm font-extrabold text-gray-900 mt-2">
                    {{ $lastService ? \Carbon\Carbon::parse($lastService->service_date)->format('d M Y') : '—' }}
                </p>
                <p class="text-xs text-gray-400 font-medium">service date</p>
            </div>
        </div>
    </div>

    {{-- Predictive Telemetry & Transparent Service Life Progress Bars (Top 1 Wow Factor) --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <div class="flex justify-between items-start mb-6 pb-5 border-b border-gray-100 flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                        <i data-lucide="gauge" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-lg font-extrabold text-gray-900 tracking-tight">Service Life & Predictive Telemetry</h3>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Transparent algorithmic projections based on calibrated daily usage rate
                    <strong class="text-gray-800">({{ $usageStats['daily_km'] }} km/day)</strong>
                    &bull; Confidence:
                    <span class="inline-flex items-center font-bold px-1.5 py-0.5 rounded text-[10px] {{ $usageStats['confidence'] === 'High' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                        {{ $usageStats['confidence'] }}
                    </span>
                    &bull; {{ $usageStats['note'] }}
                </p>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200">
                    {{ count($telemetry['items'] ?? []) }} Monitored Intervals
                </span>
            </div>
        </div>

        @if(!empty($telemetry['items']))
        <div class="space-y-5">
            @foreach($telemetry['items'] as $item)
            @php
                $kmPct = $item['km_percent'] ?? 0;
                $timePct = $item['time_percent'] ?? 0;
                $maxPct = max($kmPct, $timePct);

                $barColor = match(true) {
                    $item['urgency'] === 'overdue' => 'bg-rose-600',
                    $item['urgency'] === 'high'    => 'bg-rose-500',
                    $item['urgency'] === 'medium'  => 'bg-amber-500',
                    default                        => 'bg-emerald-500',
                };

                $badgeClass = match(true) {
                    $item['urgency'] === 'overdue' => 'bg-rose-50 text-rose-700 border-rose-200',
                    $item['urgency'] === 'high'    => 'bg-rose-50 text-rose-700 border-rose-200',
                    $item['urgency'] === 'medium'  => 'bg-amber-50 text-amber-700 border-amber-200',
                    default                        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                };

                $badgeText = match(true) {
                    $item['days_until_due'] === null => 'No Prior Record',
                    $item['days_until_due'] <= 0     => 'Overdue by ' . abs($item['days_until_due']) . 'd',
                    $item['days_until_due'] <= 7     => 'Due in ' . $item['days_until_due'] . 'd (Urgent)',
                    $item['days_until_due'] <= 14    => 'Due in ' . $item['days_until_due'] . 'd',
                    default                          => 'Due in ~' . $item['days_until_due'] . 'd',
                };

                // One-click booking URL prefilling service type, vehicle, and target date
                $targetDate = $item['predicted_due_date'] ? $item['predicted_due_date']->format('Y-m-d') : now()->addDays(7)->format('Y-m-d');
                $bookUrl = match(auth()->user()->role) {
                    'corporate'   => '/client/appointments/create?vehicle_id=' . $vehicle->id . '&service_type=' . urlencode($item['job_type']->name) . '&date=' . $targetDate,
                    'individual'  => '/customer/appointments/create?vehicle_id=' . $vehicle->id . '&service_type=' . urlencode($item['job_type']->name) . '&date=' . $targetDate,
                    'coordinator' => '/coordinator/appointments/walkin?plate_number=' . urlencode($vehicle->plate_number) . '&service_type=' . urlencode($item['job_type']->name) . '&date=' . $targetDate . '&walkin_name=' . urlencode($vehicle->owner->name ?? ''),
                    default       => '/admin/appointments/walkin?plate_number=' . urlencode($vehicle->plate_number) . '&service_type=' . urlencode($item['job_type']->name) . '&date=' . $targetDate . '&walkin_name=' . urlencode($vehicle->owner->name ?? ''),
                };
            @endphp
            <div class="p-4 rounded-xl border border-gray-200/90 bg-gray-50/50 hover:bg-gray-50 transition">
                <div class="flex items-start justify-between flex-wrap gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-sm text-gray-900">{{ $item['job_type']->name }}</h4>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded border {{ $badgeClass }}">
                                {{ $badgeText }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1 font-medium">
                            {{ $item['status_text'] }}
                        </p>
                    </div>

                    {{-- 1-Click Booking Action Button --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ $bookUrl }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition">
                            <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                            <span>Book Now</span>
                        </a>
                    </div>
                </div>

                {{-- Progress Bars (KM and Time Intervals) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 pt-3 border-t border-gray-200/60 text-xs">
                    {{-- Odometer Progress Bar --}}
                    <div>
                        <div class="flex justify-between items-center text-[11px] font-semibold text-gray-600 mb-1">
                            <span>Kilometre Interval</span>
                            <span class="font-bold text-gray-900">
                                @if($item['job_type']->interval_km)
                                    {{ $kmPct }}% used ({{ number_format($item['km_since_last'] ?? 0) }} / {{ number_format($item['job_type']->interval_km) }} km)
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $barColor }} transition-all" style="width: {{ min(100, $kmPct) }}%"></div>
                        </div>
                    </div>

                    {{-- Time Interval Progress Bar --}}
                    <div>
                        <div class="flex justify-between items-center text-[11px] font-semibold text-gray-600 mb-1">
                            <span>Time Interval</span>
                            <span class="font-bold text-gray-900">
                                @if($item['job_type']->interval_months)
                                    {{ $timePct }}% used ({{ $item['days_since_last'] ?? 0 }}d / {{ (int)round($item['job_type']->interval_months * 30.4) }}d)
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $barColor }} transition-all" style="width: {{ min(100, $timePct) }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-gray-500 mt-3 pt-2 border-t border-gray-100 flex-wrap gap-2">
                    <div>
                        Last Serviced:
                        <strong class="text-gray-700">
                            {{ $item['last_service_date'] ? $item['last_service_date']->format('d M Y') : 'No baseline service history' }}
                        </strong>
                        @if($item['days_since_last'] !== null)
                            ({{ $item['days_since_last'] }} days ago)
                        @endif
                    </div>
                    <div>
                        Predicted Due Date:
                        <strong class="text-gray-900 font-mono">
                            {{ $item['predicted_due_date'] ? $item['predicted_due_date']->format('d M Y') : 'Pending Baseline' }}
                        </strong>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-xs text-gray-400 py-4 text-center">No service intervals configured in job types.</p>
        @endif
    </div>

    {{-- Service History --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
            <div>
                <h3 class="text-base font-bold text-gray-900 tracking-tight">Workshop Service History</h3>
                <p class="text-xs text-gray-500 mt-0.5">Chronological record of completed maintenance & repairs</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                {{ $totalVisits }} records
            </span>
        </div>

        @if($vehicle->serviceHistory->count())
        <div class="relative">
            {{-- Timeline --}}
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            @foreach($vehicle->serviceHistory as $history)
            <div class="relative pl-12 pb-6">
                <div class="absolute left-2.5 w-3 h-3 rounded-full bg-slate-900 border-2 border-white shadow-xs"></div>
                <div class="bg-gray-50/70 rounded-xl p-4 border border-gray-100">
                    <div class="flex justify-between items-start mb-1 flex-wrap gap-2">
                        <p class="font-bold text-sm text-gray-900">{{ $history->description }}</p>
                        <span class="text-sm font-bold text-emerald-700">RM {{ number_format($history->cost, 2) }}</span>
                    </div>
                    <p class="text-xs font-medium text-gray-500">
                        {{ \Carbon\Carbon::parse($history->service_date)->format('d M Y') }}
                        @if($history->jobCard)
                        &bull; Job Card #{{ $history->jobCard->id }}
                        @endif
                    </p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-10">
            <x-empty-state icon="wrench" title="No service history yet" subtitle="Completed work orders for this vehicle will automatically appear here." />
        </div>
        @endif
    </div>

    {{-- Recent Appointments --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <h3 class="text-base font-bold text-gray-900 tracking-tight mb-4">Recent Booked Appointments</h3>
        <div class="divide-y divide-gray-100">
            @forelse($vehicle->appointments as $apt)
            <div class="flex justify-between items-center py-3 text-sm">
                <div>
                    <p class="font-bold text-gray-900">{{ $apt->service_type }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }} at {{ $apt->time }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                    {{ $apt->status === 'confirmed'  ? 'bg-blue-50 text-blue-700 border-blue-200' :
                      ($apt->status === 'completed'  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                      ($apt->status === 'cancelled'  ? 'bg-rose-50 text-rose-700 border-rose-200' :
                      'bg-amber-50 text-amber-700 border-amber-200')) }}">
                    {{ ucfirst($apt->status) }}
                </span>
            </div>
            @empty
            <p class="text-gray-400 text-xs py-3">No appointments on record for this vehicle.</p>
            @endforelse
        </div>
    </div>

    {{-- Trip Logs & Daily Usage Rate --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8" x-data="{ showForm: false }">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-gray-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-blue-600"></i>
                    Trip Logs & Dynamic Usage Rate
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold border {{ $usageStats['is_fallback'] ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-purple-50 text-purple-800 border-purple-200' }}">
                        {{ $usageStats['daily_km'] }} km/day
                    </span>
                </h3>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $usageStats['note'] }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(!$isSecondaryPic)
                <button type="button" @click="showForm = !showForm"
                    class="bg-slate-900 text-white hover:bg-slate-800 px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition shadow-xs">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span x-text="showForm ? 'Cancel' : 'Log Trip'"></span>
                </button>
                @endif
                <a href="{{ $base }}/trip-logs?vehicle_id={{ $vehicle->id }}"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                    All Trips &rarr;
                </a>
            </div>
        </div>

        {{-- Quick Log Form --}}
        @if(!$isSecondaryPic)
        <div x-show="showForm" x-transition class="bg-gray-50/80 border border-gray-200 rounded-xl p-4 mb-5" style="display:none">
            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Record Trip for {{ $vehicle->plate_number }}</h4>
            <form method="POST" action="{{ $base }}/trip-logs" class="space-y-3">
                @csrf
                <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Trip Date</label>
                        <input type="date" name="trip_date" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Distance (km)</label>
                        <input type="number" step="0.1" min="0.1" max="5000" name="distance_km" placeholder="e.g. 50"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Terrain / Route</label>
                        <select name="terrain_type" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" required>
                            <option value="urban">Urban (City / Stop-and-Go)</option>
                            <option value="highway">Highway (Expressway)</option>
                            <option value="rural">Rural (Unpaved)</option>
                            <option value="mountain">Mountain (Steep)</option>
                            <option value="mixed" selected>Mixed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="e.g. Outstation trip to Melaka"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-600 font-medium">
                        <input type="checkbox" name="update_mileage" value="1" checked class="rounded text-blue-600">
                        <span>Advance vehicle odometer</span>
                    </label>
                    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs hover:bg-slate-800 font-semibold transition shadow-xs">
                        Save Trip & Refresh Predictions
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Recent Trips List --}}
        @if($vehicle->tripLogs && $vehicle->tripLogs->count())
        <div class="divide-y divide-gray-100 text-sm">
            @foreach($vehicle->tripLogs as $trip)
            <div class="py-3 flex items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <span class="font-bold text-gray-900">{{ $trip->trip_date ? $trip->trip_date->format('d M Y') : '—' }}</span>
                    <span class="font-bold text-blue-600 font-mono">{{ number_format($trip->distance_km, 1) }} km</span>
                    <span class="px-2 py-0.5 rounded-md bg-gray-100 border border-gray-200 text-gray-700 capitalize text-[11px] font-semibold">{{ $trip->terrain_type }}</span>
                    @if($trip->notes)
                    <span class="text-gray-500 truncate max-w-xs hidden sm:inline">{{ $trip->notes }}</span>
                    @endif
                </div>
                <div>
                    @if(!$isSecondaryPic)
                    <form method="POST" action="{{ $base }}/trip-logs/{{ $trip->id }}" class="inline"
                        onsubmit="return confirmSubmit(event, {title: 'Delete Trip Log', message: 'Delete this trip of {{ $trip->distance_km }} km? Predictions will be updated automatically.', confirmLabel: 'Delete'})">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-rose-600 p-1" title="Delete trip">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-400 text-xs py-3">No trips recorded for this vehicle yet.</p>
        @endif
    </div>

    {{-- Maintenance Alerts --}}
    @if($vehicle->maintenanceAlerts->count())
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <h3 class="text-base font-bold text-gray-900 tracking-tight mb-4">Maintenance Alerts & Projections</h3>
        <div class="divide-y divide-gray-100">
            @foreach($vehicle->maintenanceAlerts as $alert)
            <div class="flex justify-between items-center py-3 text-sm">
                <div>
                    <p class="font-bold text-gray-900">{{ $alert->alert_type }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $alert->recommendation }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                    {{ $alert->urgency === 'high'   ? 'bg-rose-50 text-rose-700 border-rose-200' :
                      ($alert->urgency === 'medium' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                      'bg-emerald-50 text-emerald-700 border-emerald-200') }}">
                    {{ ucfirst($alert->urgency) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection