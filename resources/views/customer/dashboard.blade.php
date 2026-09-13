@extends('layouts.app')
@section('page-title', 'Fleet Dashboard')

@section('content')

@include('partials.greeting')

@if(auth()->user()->status === 'inactive')
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
    ⚠ Your account is currently <strong>inactive</strong>. Contact the administrator.
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Fleet Size</p>
        <p class="text-3xl font-bold text-blue-600">{{ $vehicles->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Appointments</p>
        <p class="text-3xl font-bold text-green-600">{{ $appointments->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Alerts</p>
        <p class="text-3xl font-bold text-red-500">{{ $maintenanceAlerts->count() }}</p>
        @if($maintenanceAlerts->count() > 0)
        <a href="/client/maintenance" class="text-xs text-blue-600 hover:underline">View →</a>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Spent</p>
        <p class="text-2xl font-bold text-purple-600">RM {{ number_format($totalSpent, 0) }}</p>
    </div>
</div>

{{-- Alerts banner --}}
@if($maintenanceAlerts->where('is_read', false)->count())
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
    <div class="flex justify-between items-center mb-2">
        <h2 class="text-red-700 font-semibold">⚠ Maintenance Alerts</h2>
        <a href="/client/maintenance" class="text-xs text-red-600 hover:underline">View all →</a>
    </div>
    @foreach($maintenanceAlerts->where('is_read', false)->take(3) as $alert)
    <div class="flex items-start gap-2 mb-1">
        <span class="text-xs px-2 py-0.5 rounded-full shrink-0 mt-0.5
            {{ $alert->urgency === 'high' ? 'bg-red-200 text-red-800' : 'bg-yellow-100 text-yellow-700' }}">
            {{ ucfirst($alert->urgency) }}
        </span>
        <p class="text-sm text-red-600">
            <span class="font-medium">{{ $alert->vehicle->plate_number ?? '—' }}</span>
            — {{ $alert->alert_type }}:
            {{ $alert->recommendation }}
        </p>
    </div>
    @endforeach
    @if($maintenanceAlerts->where('is_read', false)->count() > 3)
    <a href="/client/maintenance" class="text-sm text-red-700 underline mt-1 inline-block">
        +{{ $maintenanceAlerts->where('is_read', false)->count() - 3 }} more alerts →
    </a>
    @endif
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Fleet --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">
                Fleet Vehicles
                <span class="text-sm font-normal text-gray-400 ml-1">{{ $vehicles->count() }} total</span>
            </h2>
            {{-- Only primary PIC can add vehicles --}}
            @if(!auth()->user()->isSecondaryPic())
            <a href="/client/vehicles/create"
                class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">+ Add</a>
            @endif
        </div>
        @forelse($vehicles as $v)
        <a href="/client/vehicles/{{ $v->id }}"
            class="block border rounded-lg p-3 mb-2 hover:bg-gray-50 transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="font-bold text-blue-700">{{ $v->plate_number }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $v->brand }} {{ $v->model }} ({{ $v->year }})
                    </p>
                    @if($v->owner && $v->owner->id !== auth()->id())
                    <p class="text-xs text-gray-400 mt-0.5">
                        Registered by: {{ $v->owner->name }}
                    </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">{{ number_format($v->mileage) }} km</p>
                    @php
                        $vAlert = $maintenanceAlerts
                            ->where('vehicle_id', $v->id)
                            ->where('is_read', false)
                            ->first();
                    @endphp
                    @if($vAlert)
                    <span class="text-xs px-2 py-0.5 rounded-full mt-1 inline-block
                        {{ $vAlert->urgency === 'high' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600' }}">
                        {{ $vAlert->urgency === 'high' ? '⚠ Action needed' : '• Alert' }}
                    </span>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <x-empty-state icon="car" title="No vehicles registered"
            subtitle="Add your first fleet vehicle to get started."
            :action-href="!auth()->user()->isSecondaryPic() ? '/client/vehicles/create' : null"
            :action-label="!auth()->user()->isSecondaryPic() ? '+ Add Vehicle' : null" />
        @endforelse
    </div>

    {{-- Recent Appointments --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Recent Appointments</h2>
            {{-- Only primary and active users can book --}}
            @if(auth()->user()->status === 'active' && !auth()->user()->isSecondaryPic())
            <a href="/client/appointments/create"
                class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">+ Book</a>
            @endif
        </div>
        @forelse($appointments as $apt)
        <div class="border rounded-lg p-3 mb-2">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium text-sm">{{ $apt->service_type }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $apt->vehicle->plate_number ?? '—' }} —
                        {{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }}
                        at {{ $apt->time }}
                    </p>
                    @if($apt->user && $apt->user->id !== auth()->id())
                    <p class="text-xs text-gray-400 mt-0.5">
                        Booked by: {{ $apt->user->name }}
                    </p>
                    @endif
                    @if($apt->jobCard)
                    <p class="text-xs text-blue-600 mt-0.5">
                        Job #{{ $apt->jobCard->id }} —
                        {{ ucfirst(str_replace('_', ' ', $apt->jobCard->current_stage)) }}
                        @if($apt->jobCard->total_cost > 0)
                        · RM {{ number_format($apt->jobCard->total_cost, 2) }}
                        @endif
                    </p>
                    @endif
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium shrink-0
                    {{ $apt->status === 'completed' ? 'bg-green-100 text-green-700' :
                      ($apt->status === 'confirmed' ? 'bg-blue-100 text-blue-700' :
                      ($apt->status === 'cancelled' ? 'bg-red-100 text-red-700' :
                      'bg-yellow-100 text-yellow-700')) }}">
                    {{ ucfirst($apt->status) }}
                </span>
            </div>
        </div>
        @empty
        <x-empty-state icon="calendar" title="No appointments yet"
            subtitle="{{ (auth()->user()->status === 'active' && !auth()->user()->isSecondaryPic()) ? 'Book your first fleet appointment.' : 'Appointments will appear here once booked.' }}"
            :action-href="(auth()->user()->status === 'active' && !auth()->user()->isSecondaryPic()) ? '/client/appointments/create' : null"
            :action-label="(auth()->user()->status === 'active' && !auth()->user()->isSecondaryPic()) ? '+ Book Appointment' : null" />
        @endforelse
    </div>

</div>

{{-- Service History --}}
@if($serviceHistory->count())
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">
            Fleet Service History
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $totalServices }} total services</span>
        </h2>
        <span class="text-sm font-semibold text-green-700">
            Total: RM {{ number_format($totalSpent, 2) }}
        </span>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-2 pr-4">Date</th>
                <th class="pb-2 pr-4">Vehicle</th>
                <th class="pb-2 pr-4">Service</th>
                <th class="pb-2">Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($serviceHistory as $h)
            <tr class="border-b hover:bg-gray-50">
                <td class="py-2 pr-4 text-gray-500">
                    {{ \Carbon\Carbon::parse($h->service_date)->format('d M Y') }}
                </td>
                <td class="py-2 pr-4 font-medium">{{ $h->vehicle->plate_number ?? '—' }}</td>
                <td class="py-2 pr-4">{{ $h->description }}</td>
                <td class="py-2 font-medium text-green-700">RM {{ number_format($h->cost, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Company PICs quick view --}}
@if(isset($companyPics) && $companyPics->count() > 0)
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">
            Other Person(s) In Charge
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $companyPics->count() }} other PIC(s)</span>
        </h2>
        <a href="/client/company"
            class="text-sm text-blue-600 hover:underline">Manage →</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($companyPics as $pic)
        <div class="flex items-center gap-3 border rounded-lg p-3">
            <img src="{{ $pic->avatar_url }}"
                class="w-10 h-10 rounded-full object-cover border-2 border-blue-200">
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm text-gray-800 truncate">{{ $pic->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ $pic->email }}</p>
                @if($pic->contact_no)
                <p class="text-xs text-gray-400">{{ $pic->contact_no }}</p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                    {{ $pic->pic_role === 'primary' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $pic->pic_role === 'primary' ? '★ Primary' : '◎ Viewer' }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full
                    {{ $pic->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ ucfirst($pic->status) }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection