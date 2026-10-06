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
    <a href="{{ $base }}/vehicles" class="text-sm text-blue-600 hover:underline">← Back to Vehicles</a>

    {{-- Vehicle Header --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-6 pb-4 border-b">
            <div>
                <h2 class="text-3xl font-bold text-blue-700">{{ $vehicle->plate_number }}</h2>
                <p class="text-lg text-gray-600 mt-1">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }})</p>
                @if($vehicle->owner)
                <p class="text-sm text-gray-400 mt-1">Owner: {{ $vehicle->owner->name }}
                    — {{ $vehicle->owner->contact_no ?? $vehicle->owner->email }}</p>
                @endif
            </div>
            <a href="{{ $base }}/vehicles/{{ $vehicle->id }}/edit"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-200">
                Edit
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <p class="text-xs text-blue-400 uppercase font-semibold mb-1">Mileage</p>
                <p class="text-2xl font-bold text-blue-700">{{ number_format($vehicle->mileage) }}</p>
                <p class="text-xs text-blue-400">km</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <p class="text-xs text-green-400 uppercase font-semibold mb-1">Total Visits</p>
                <p class="text-2xl font-bold text-green-700">{{ $totalVisits }}</p>
                <p class="text-xs text-green-400">services</p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <p class="text-xs text-purple-400 uppercase font-semibold mb-1">Total Spent</p>
                <p class="text-2xl font-bold text-purple-700">RM {{ number_format($totalSpent, 0) }}</p>
                <p class="text-xs text-purple-400">all time</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-4 text-center">
                <p class="text-xs text-orange-400 uppercase font-semibold mb-1">Last Service</p>
                <p class="text-sm font-bold text-orange-700">
                    {{ $lastService ? \Carbon\Carbon::parse($lastService->service_date)->format('d M Y') : '—' }}
                </p>
                <p class="text-xs text-orange-400">date</p>
            </div>
        </div>
    </div>

    {{-- Service History --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">
            Service History
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $totalVisits }} records</span>
        </h3>

        @if($vehicle->serviceHistory->count())
        <div class="relative">
            {{-- Timeline --}}
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            @foreach($vehicle->serviceHistory as $history)
            <div class="relative pl-12 pb-6">
                <div class="absolute left-2.5 w-3 h-3 rounded-full bg-blue-500 border-2 border-white"></div>
                <div class="bg-gray-50 rounded-lg p-4 border">
                    <div class="flex justify-between items-start mb-1">
                        <p class="font-semibold text-gray-800">{{ $history->description }}</p>
                        <span class="text-sm font-bold text-blue-600">RM {{ number_format($history->cost, 2) }}</span>
                    </div>
                    <p class="text-xs text-gray-400">
                        {{ \Carbon\Carbon::parse($history->service_date)->format('d M Y') }}
                        @if($history->jobCard)
                        — Job Card #{{ $history->jobCard->id }}
                        @endif
                    </p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <p class="text-gray-400 text-sm">No service history yet.</p>
            <p class="text-gray-300 text-xs mt-1">History is recorded when a job card is completed.</p>
        </div>
        @endif
    </div>

    {{-- Recent Appointments --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Recent Appointments</h3>
        @forelse($vehicle->appointments as $apt)
        <div class="flex justify-between items-center border-b py-3 text-sm">
            <div>
                <p class="font-medium">{{ $apt->service_type }}</p>
                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }} at {{ $apt->time }}</p>
            </div>
            <span class="px-2 py-1 rounded-full text-xs font-medium
                {{ $apt->status === 'confirmed'  ? 'bg-green-100 text-green-700' :
                  ($apt->status === 'cancelled'  ? 'bg-red-100 text-red-700' :
                  'bg-yellow-100 text-yellow-700') }}">
                {{ ucfirst($apt->status) }}
            </span>
        </div>
        @empty
        <p class="text-gray-400 text-sm">No appointments for this vehicle.</p>
        @endforelse
    </div>

    {{-- Trip Logs & Daily Usage Rate --}}
    <div class="bg-white rounded-lg shadow p-6" x-data="{ showForm: false }">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-5 h-5 text-blue-600"></i>
                    Trip Logs & Mileage Usage
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-medium {{ $usageStats['is_fallback'] ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800' }}">
                        {{ $usageStats['daily_km'] }} km/day {{ $usageStats['is_fallback'] ? '(Fallback Heuristic)' : '(Dynamic Usage Rate)' }}
                    </span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $usageStats['note'] }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(!$isSecondaryPic)
                <button type="button" @click="showForm = !showForm"
                    class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded text-xs font-medium flex items-center gap-1 transition">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span x-text="showForm ? 'Cancel' : 'Log Trip'"></span>
                </button>
                @endif
                <a href="{{ $base }}/trip-logs?vehicle_id={{ $vehicle->id }}"
                    class="text-xs text-blue-600 hover:underline">
                    All Trips →
                </a>
            </div>
        </div>

        {{-- Quick Log Form --}}
        @if(!$isSecondaryPic)
        <div x-show="showForm" x-transition class="bg-gray-50 border rounded-lg p-4 mb-4" style="display:none">
            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Record Trip for {{ $vehicle->plate_number }}</h4>
            <form method="POST" action="{{ $base }}/trip-logs" class="space-y-3">
                @csrf
                <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Trip Date</label>
                        <input type="date" name="trip_date" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Distance (km)</label>
                        <input type="number" step="0.1" min="0.1" max="5000" name="distance_km" placeholder="e.g. 50"
                            class="w-full border rounded px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Terrain / Route</label>
                        <select name="terrain_type" class="w-full border rounded px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="urban">Urban (City / Stop-and-Go)</option>
                            <option value="highway">Highway (Expressway)</option>
                            <option value="rural">Rural (Unpaved)</option>
                            <option value="mountain">Mountain (Steep)</option>
                            <option value="mixed" selected>Mixed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="e.g. Outstation trip to Melaka"
                        class="w-full border rounded px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-600">
                        <input type="checkbox" name="update_mileage" value="1" checked class="rounded text-blue-600">
                        <span>Advance vehicle odometer</span>
                    </label>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 rounded text-xs hover:bg-blue-700 font-medium">
                        Save Trip & Refresh Predictions
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Recent Trips List --}}
        @if($vehicle->tripLogs && $vehicle->tripLogs->count())
        <div class="divide-y text-sm">
            @foreach($vehicle->tripLogs as $trip)
            <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <span class="font-medium text-gray-800">{{ $trip->trip_date ? $trip->trip_date->format('d M Y') : '—' }}</span>
                    <span class="font-semibold text-blue-700">{{ number_format($trip->distance_km, 1) }} km</span>
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 capitalize text-[11px]">{{ $trip->terrain_type }}</span>
                    @if($trip->notes)
                    <span class="text-gray-400 truncate max-w-xs hidden sm:inline">{{ $trip->notes }}</span>
                    @endif
                </div>
                <div>
                    @if(!$isSecondaryPic)
                    <form method="POST" action="{{ $base }}/trip-logs/{{ $trip->id }}" class="inline"
                        onsubmit="return confirmSubmit(event, {title: 'Delete Trip Log', message: 'Delete this trip of {{ $trip->distance_km }} km? Predictions will be updated automatically.', confirmLabel: 'Delete'})">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 p-1" title="Delete trip">
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
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Maintenance Alerts</h3>
        @foreach($vehicle->maintenanceAlerts as $alert)
        <div class="flex justify-between items-center border-b py-3 text-sm">
            <div>
                <p class="font-medium">{{ $alert->alert_type }}</p>
                <p class="text-xs text-gray-500">{{ $alert->recommendation }}</p>
            </div>
            <span class="px-2 py-1 rounded-full text-xs font-medium
                {{ $alert->urgency === 'high'   ? 'bg-red-100 text-red-700' :
                  ($alert->urgency === 'medium' ? 'bg-yellow-100 text-yellow-700' :
                  'bg-green-100 text-green-700') }}">
                {{ ucfirst($alert->urgency) }}
            </span>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection