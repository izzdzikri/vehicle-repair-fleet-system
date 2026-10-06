@extends('layouts.app')
@section('page-title', 'Trip Logs & Mileage Tracking')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    {{-- Top Heading & Context --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Trip Logs & Mileage Tracking</h2>
            <p class="text-sm text-gray-500 mt-1">
                Log vehicle journeys to compute real-world daily mileage rates for the predictive maintenance engine.
            </p>
        </div>
        @if(!$isSecondaryPic)
        <a href="#log-trip-card"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-sm transition shrink-0">
            <i data-lucide="plus" class="w-4 h-4"></i> Log New Trip
        </a>
        @endif
    </div>

    {{-- Summary KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Trips</p>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="route" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($totalTrips) }}</p>
            <p class="text-xs text-gray-400 mt-1">Recorded journeys</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Distance</p>
                <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                    <i data-lucide="gauge" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700">{{ number_format($totalDistance, 1) }} <span class="text-sm font-normal text-gray-500">km</span></p>
            <p class="text-xs text-gray-400 mt-1">Across all logged trips</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active Rate</p>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                </div>
            </div>
            @if($activeUsageStats)
                <p class="text-2xl font-bold text-purple-700">
                    {{ $activeUsageStats['daily_km'] }} <span class="text-sm font-normal text-gray-500">km/day</span>
                </p>
                <p class="text-xs {{ $activeUsageStats['is_fallback'] ? 'text-amber-600 font-medium' : 'text-purple-600' }} mt-1">
                    {{ $activeUsageStats['label'] }}
                </p>
            @else
                <p class="text-2xl font-bold text-purple-700">{{ $vehicles->count() }}</p>
                <p class="text-xs text-gray-400 mt-1">Fleet vehicles tracked</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Heuristic Engine</p>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-base font-bold text-gray-800">Rule-Based</p>
            <p class="text-xs text-gray-400 mt-1">Next-due = Last service + km rate</p>
        </div>
    </div>

    {{-- Explainer Banner: Rule-Based Predictive Maintenance --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-900 flex items-start gap-3">
        <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5"></i>
        <div class="space-y-1">
            <p class="font-semibold text-blue-950">How Trip Logs Drive Predictive Maintenance</p>
            <p class="text-xs text-blue-800 leading-relaxed">
                The maintenance scanner calculates each vehicle's average daily distance:
                <code class="bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-mono text-[11px]">(Total km between earliest & latest trips) ÷ (Days span)</code>.
                When a vehicle has fewer than 2 trip logs, it automatically falls back to the transparent <strong>40 km/day</strong> baseline.
                Logging trips here immediately tailors the upcoming service due dates on your Maintenance Alerts page!
            </p>
        </div>
    </div>

    {{-- Log Trip Form & Vehicle Rate Card --}}
    @if(!$isSecondaryPic)
    <div id="log-trip-card" class="bg-white rounded-xl shadow-sm border p-6">
        <div class="flex items-center justify-between pb-4 mb-4 border-b">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-5 h-5 text-blue-600"></i> Record a Journey
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Enter trip details to automatically advance vehicle mileage and refresh predictions.</p>
            </div>
            <span class="text-xs px-2.5 py-1 bg-green-50 text-green-700 font-medium rounded-full border border-green-200">
                Live Auto-Sync
            </span>
        </div>

        <form method="POST" action="{{ $baseRoute }}/trip-logs" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Vehicle selection --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Vehicle <span class="text-red-500">*</span></label>
                    <select name="vehicle_id" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                        <option value="">Select vehicle...</option>
                        @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ (old('vehicle_id') == $v->id || $selectedVehicleId == $v->id) ? 'selected' : '' }}>
                            {{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }}
                            @if(auth()->user()->role === 'admin' && $v->owner) ({{ $v->owner->name }}) @endif
                        </option>
                        @endforeach
                    </select>
                    @error('vehicle_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Trip Date --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Trip Date <span class="text-red-500">*</span></label>
                    <input type="date" name="trip_date" max="{{ date('Y-m-d') }}"
                        value="{{ old('trip_date', date('Y-m-d')) }}"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    @error('trip_date')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Distance in km --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Distance (km) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.1" min="0.1" max="5000" name="distance_km"
                            value="{{ old('distance_km') }}" placeholder="e.g. 65.5"
                            class="w-full border rounded-lg px-3 py-2 pr-10 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                        <span class="absolute right-3 top-2.5 text-xs font-medium text-gray-400">km</span>
                    </div>
                    @error('distance_km')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Terrain Type --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Terrain / Route Type <span class="text-red-500">*</span></label>
                    <select name="terrain_type" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                        <option value="urban" {{ old('terrain_type') == 'urban' ? 'selected' : '' }}>Urban (City commute / stop-and-go)</option>
                        <option value="highway" {{ old('terrain_type') == 'highway' ? 'selected' : '' }}>Highway (Expressway / intercity)</option>
                        <option value="rural" {{ old('terrain_type') == 'rural' ? 'selected' : '' }}>Rural (Countryside / unpaved paths)</option>
                        <option value="mountain" {{ old('terrain_type') == 'mountain' ? 'selected' : '' }}>Mountain (Steep gradients / heavy load)</option>
                        <option value="mixed" {{ old('terrain_type', 'mixed') == 'mixed' ? 'selected' : '' }}>Mixed (Combination)</option>
                    </select>
                </div>

                {{-- Trip Notes --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Trip Notes (Optional)</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                        placeholder="e.g. Senai to Pasir Gudang client deliveries"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-600 select-none">
                    <input type="checkbox" name="update_mileage" value="1" checked
                        class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 w-4 h-4">
                    <span>Automatically advance vehicle odometer mileage by this trip's distance</span>
                </label>

                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-lg text-sm transition flex items-center justify-center gap-2 shadow-sm">
                    <i data-lucide="check" class="w-4 h-4"></i> Save Trip & Refresh Predictions
                </button>
            </div>
        </form>
    </div>
    @else
    {{-- Secondary PIC Notice --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800 flex items-center gap-3">
        <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600 shrink-0"></i>
        <p>You are logged in as a <strong>Secondary PIC (Viewer)</strong>. You have read-only access to fleet trip logs. Please contact your company's Primary PIC to record new journeys.</p>
    </div>
    @endif

    {{-- Filter Bar & Table --}}
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Trip History</h3>
                <p class="text-xs text-gray-400 mt-0.5">Browse past journeys and filter by vehicle or terrain.</p>
            </div>

            <form method="GET" action="{{ $baseRoute }}/trip-logs" class="flex flex-wrap items-center gap-2">
                {{-- Vehicle filter --}}
                <select name="vehicle_id" onchange="this.form.submit()"
                    class="border rounded-lg px-3 py-1.5 text-xs text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Vehicles ({{ $vehicles->count() }})</option>
                    @foreach($vehicles as $v)
                    <option value="{{ $v->id }}" {{ $selectedVehicleId == $v->id ? 'selected' : '' }}>
                        {{ $v->plate_number }} — {{ $v->model }}
                    </option>
                    @endforeach
                </select>

                {{-- Terrain filter --}}
                <select name="terrain_type" onchange="this.form.submit()"
                    class="border rounded-lg px-3 py-1.5 text-xs text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Terrains</option>
                    <option value="urban" {{ $selectedTerrain === 'urban' ? 'selected' : '' }}>Urban</option>
                    <option value="highway" {{ $selectedTerrain === 'highway' ? 'selected' : '' }}>Highway</option>
                    <option value="rural" {{ $selectedTerrain === 'rural' ? 'selected' : '' }}>Rural</option>
                    <option value="mountain" {{ $selectedTerrain === 'mountain' ? 'selected' : '' }}>Mountain</option>
                    <option value="mixed" {{ $selectedTerrain === 'mixed' ? 'selected' : '' }}>Mixed</option>
                </select>

                @if($selectedVehicleId || $selectedTerrain)
                <a href="{{ $baseRoute }}/trip-logs"
                    class="text-xs text-red-600 hover:text-red-700 px-2 py-1 hover:underline">
                    Reset
                </a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        @if($tripLogs->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50/75 text-gray-600 text-xs uppercase tracking-wider font-semibold">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Vehicle</th>
                        <th class="py-3 px-4">Distance</th>
                        <th class="py-3 px-4">Terrain</th>
                        <th class="py-3 px-4">Notes</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @foreach($tripLogs as $trip)
                    @php
                        $terrainColors = match($trip->terrain_type) {
                            'urban'    => 'bg-blue-50 text-blue-700 border-blue-200',
                            'highway'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'rural'    => 'bg-amber-50 text-amber-700 border-amber-200',
                            'mountain' => 'bg-purple-50 text-purple-700 border-purple-200',
                            default    => 'bg-gray-100 text-gray-700 border-gray-200',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-3.5 px-4 font-medium text-gray-900 whitespace-nowrap">
                            {{ $trip->trip_date ? $trip->trip_date->format('d M Y') : '—' }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($trip->vehicle)
                            <a href="{{ $baseRoute }}/vehicles/{{ $trip->vehicle->id }}" class="font-semibold text-blue-600 hover:underline">
                                {{ $trip->vehicle->plate_number }}
                            </a>
                            <p class="text-xs text-gray-400">{{ $trip->vehicle->brand }} {{ $trip->vehicle->model }}</p>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-gray-800 whitespace-nowrap">
                            {{ number_format($trip->distance_km, 1) }} <span class="text-xs font-normal text-gray-400">km</span>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $terrainColors }} capitalize">
                                {{ $trip->terrain_type }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-gray-500 max-w-xs truncate">
                            {{ $trip->notes ?: '—' }}
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            @if(!$isSecondaryPic)
                            <form method="POST" action="{{ $baseRoute }}/trip-logs/{{ $trip->id }}" class="inline"
                                onsubmit="return confirmSubmit(event, {title: 'Delete Trip Log', message: 'Delete this trip entry of {{ $trip->distance_km }} km? The predictive maintenance scanner will recalculate service due dates.', confirmLabel: 'Delete'})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 p-1 rounded transition" title="Delete trip">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tripLogs->links() }}
        </div>
        @else
        <x-empty-state
            icon="map-pin-off"
            title="No trip logs found"
            subtitle="Record vehicle journeys above to start gathering real daily mileage data."
        />
        @endif
    </div>

</div>
@endsection
