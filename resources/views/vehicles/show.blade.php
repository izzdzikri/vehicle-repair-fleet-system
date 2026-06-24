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