@extends('layouts.app')
@section('page-title', 'My Dashboard')

@section('content')

@if(auth()->user()->status === 'inactive')
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 flex items-start gap-2">
    <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 shrink-0"></i>
    <div>
        <p class="font-semibold">Account Inactive</p>
        <p>You cannot make new bookings. Please contact the administrator to reactivate your account.</p>
    </div>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="car" class="w-6 h-6 text-blue-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">My Vehicles</p>
        <p class="text-3xl font-bold text-blue-600">{{ $vehicles->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="calendar" class="w-6 h-6 text-green-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Appointments</p>
        <p class="text-3xl font-bold text-green-600">{{ $appointments->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="clipboard-check" class="w-6 h-6 text-purple-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Services Done</p>
        <p class="text-3xl font-bold text-purple-600">{{ $totalServices }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        @php $unreadAlerts = isset($maintenanceAlerts) ? $maintenanceAlerts->where('is_read', false)->count() : 0; @endphp
        <i data-lucide="bell" class="w-6 h-6 {{ $unreadAlerts > 0 ? 'text-red-500' : 'text-gray-400' }} mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Alerts</p>
        <p class="text-3xl font-bold {{ $unreadAlerts > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $unreadAlerts }}</p>
        @if($unreadAlerts > 0)
        <a href="/customer/maintenance" class="text-xs text-red-500 hover:underline">View →</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- My Vehicles --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">My Vehicles</h2>
            <a href="/customer/vehicles/create"
                class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                + Add
            </a>
        </div>
        @forelse($vehicles as $v)
        <a href="/customer/vehicles/{{ $v->id }}"
            class="block border rounded-lg p-3 mb-2 hover:bg-gray-50 transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="font-bold text-blue-700">{{ $v->plate_number }}</p>
                    <p class="text-sm text-gray-500">{{ $v->brand }} {{ $v->model }} ({{ $v->year }})</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">{{ number_format($v->mileage) }} km</p>
                    @php
                        $vAlert = isset($maintenanceAlerts)
                            ? $maintenanceAlerts->where('vehicle_id', $v->id)->where('is_read', false)->first()
                            : null;
                    @endphp
                    @if($vAlert)
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $vAlert->urgency === 'high' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600' }}">
                        {{ $vAlert->urgency === 'high' ? '⚠ Action needed' : '• Alert' }}
                    </span>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <p class="text-gray-400 text-sm text-center py-4">No vehicles yet.
            <a href="/customer/vehicles/create" class="text-blue-600 hover:underline">Add one →</a>
        </p>
        @endforelse
    </div>

    {{-- My Appointments --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Recent Appointments</h2>
            @if(auth()->user()->status === 'active')
            <a href="/customer/appointments/create"
                class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                + Book
            </a>
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
                    @if($apt->jobCard)
                    <p class="text-xs text-blue-600 mt-0.5">
                        Job Card #{{ $apt->jobCard->id }} —
                        {{ ucfirst(str_replace('_', ' ', $apt->jobCard->current_stage)) }}
                    </p>
                    @endif
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium
                    {{ $apt->status === 'completed' ? 'bg-green-100 text-green-700' :
                      ($apt->status === 'confirmed' ? 'bg-blue-100 text-blue-700' :
                      ($apt->status === 'cancelled' ? 'bg-red-100 text-red-700' :
                      'bg-yellow-100 text-yellow-700')) }}">
                    {{ ucfirst($apt->status) }}
                </span>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm text-center py-4">No appointments yet.</p>
        @endforelse
    </div>

</div>

{{-- Maintenance Alerts section --}}
@if(isset($maintenanceAlerts) && $maintenanceAlerts->count() > 0)
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">
            Maintenance Alerts
            @if($unreadAlerts > 0)
            <span class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $unreadAlerts }} unread</span>
            @endif
        </h2>
        <a href="/customer/maintenance" class="text-sm text-blue-600 hover:underline">View all →</a>
    </div>
    @foreach($maintenanceAlerts->take(3) as $alert)
    @php
        $lClass = match($alert->urgency) {
            'high'   => 'border-red-400 bg-red-50',
            'medium' => 'border-yellow-400 bg-yellow-50',
            default  => 'border-green-400 bg-green-50',
        };
        $bClass = match($alert->urgency) {
            'high'   => 'bg-red-100 text-red-700',
            'medium' => 'bg-yellow-100 text-yellow-700',
            default  => 'bg-green-100 text-green-700',
        };
    @endphp
    <div class="border-l-4 rounded p-3 mb-2 {{ $lClass }} {{ $alert->is_read ? 'opacity-60' : '' }}">
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-xs font-semibold text-gray-600">
                        {{ $alert->vehicle->plate_number ?? '—' }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $bClass }}">{{ ucfirst($alert->urgency) }}</span>
                </div>
                <p class="text-sm font-medium text-gray-700">{{ $alert->alert_type }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $alert->recommendation }}</p>
            </div>
            @if(!$alert->is_read)
            <form method="POST" action="/customer/maintenance/{{ $alert->id }}/read">
                @csrf @method('PATCH')
                <button class="text-xs text-blue-600 hover:underline whitespace-nowrap ml-2">
                    Mark read
                </button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
    @if($maintenanceAlerts->count() > 3)
    <a href="/customer/maintenance" class="text-sm text-blue-600 hover:underline">
        + {{ $maintenanceAlerts->count() - 3 }} more alerts
    </a>
    @endif
</div>
@endif

{{-- Service History --}}
@if($serviceHistory->count())
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">
        Service History
        <span class="text-sm font-normal text-gray-400 ml-2">{{ $serviceHistory->count() }} records</span>
    </h2>
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

@endsection
