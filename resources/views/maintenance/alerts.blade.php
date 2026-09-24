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
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Alerts</p>
        <p class="text-3xl font-bold text-gray-700">{{ $alerts->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Unread</p>
        <p class="text-3xl font-bold text-yellow-500">{{ $unread }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">High Urgency</p>
        <p class="text-3xl font-bold text-red-500">{{ $highUrge }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">🔮 Predicted</p>
        <p class="text-3xl font-bold text-purple-500">{{ $predicted }}</p>
    </div>
</div>

{{-- Admin can create manual alerts + trigger predictions --}}
@if($role === 'admin')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-start flex-wrap gap-4 mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-700">Create Manual Alert</h2>
            <p class="text-xs text-gray-400 mt-1">
                Predictive alerts (🔮) are generated automatically from service history and job type intervals —
                set intervals on the <a href="/pricing/job-types" class="text-blue-600 hover:underline">Job Types</a> page.
            </p>
        </div>
        <form method="POST" action="/admin/maintenance/predict" class="shrink-0">
            @csrf
            <button type="submit"
                class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700 flex items-center gap-2">
                <i data-lucide="sparkles" class="w-4 h-4"></i> Run Predictions Now
            </button>
        </form>
    </div>
    <form method="POST" action="/admin/maintenance" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
            <select name="vehicle_id"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Alert Type</label>
            <select name="alert_type"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Urgency</label>
            <select name="urgency"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                required>
                <option value="low">Low — informational</option>
                <option value="medium" selected>Medium — schedule soon</option>
                <option value="high">High — action required</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recommendation</label>
            <input type="text" name="recommendation"
                placeholder="e.g. Schedule oil change within 500 km or 1 month"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                required>
        </div>
        <div class="md:col-span-2">
            <button type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Create Alert
            </button>
        </div>
    </form>
</div>
@endif

{{-- Alerts list --}}
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">
            Maintenance Alerts
            @if($unread > 0)
            <span class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium">
                {{ $unread }} unread
            </span>
            @endif
        </h2>
    </div>

    {{-- Filter tabs --}}
    <div class="flex gap-2 mb-4 flex-wrap" x-data="{ filter: 'all' }">
        <button @click="filter='all'"
            :class="filter==='all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600'"
            class="px-3 py-1 rounded text-xs font-medium">
            All ({{ $alerts->count() }})
        </button>
        <button @click="filter='high'"
            :class="filter==='high' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600'"
            class="px-3 py-1 rounded text-xs font-medium">
            High ({{ $alerts->where('urgency','high')->count() }})
        </button>
        <button @click="filter='medium'"
            :class="filter==='medium' ? 'bg-yellow-500 text-white' : 'bg-yellow-50 text-yellow-600'"
            class="px-3 py-1 rounded text-xs font-medium">
            Medium ({{ $alerts->where('urgency','medium')->count() }})
        </button>
        <button @click="filter='low'"
            :class="filter==='low' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600'"
            class="px-3 py-1 rounded text-xs font-medium">
            Low ({{ $alerts->where('urgency','low')->count() }})
        </button>
        <button @click="filter='unread'"
            :class="filter==='unread' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600'"
            class="px-3 py-1 rounded text-xs font-medium">
            Unread ({{ $unread }})
        </button>
        <button @click="filter='predicted'"
            :class="filter==='predicted' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600'"
            class="px-3 py-1 rounded text-xs font-medium">
            🔮 Predicted ({{ $predicted }})
        </button>
    </div>

    @forelse($alerts as $alert)
    @php
        $urgencyClass = match($alert->urgency) {
            'high'   => 'border-red-400 bg-red-50',
            'medium' => 'border-yellow-400 bg-yellow-50',
            default  => 'border-green-400 bg-green-50',
        };
        $badgeClass = match($alert->urgency) {
            'high'   => 'bg-red-100 text-red-700',
            'medium' => 'bg-yellow-100 text-yellow-700',
            default  => 'bg-green-100 text-green-700',
        };
    @endphp
    <div class="border-l-4 rounded-lg p-4 mb-3 {{ $urgencyClass }} {{ $alert->is_read ? 'opacity-50' : '' }}"
        x-show="filter==='all' || filter===$alert->urgency || (filter==='unread' && {{ $alert->is_read ? 'false' : 'true' }}) || (filter==='predicted' && {{ $alert->source === 'predicted' ? 'true' : 'false' }})">
        <div class="flex justify-between items-start gap-3">
            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="font-semibold text-sm text-gray-800">
                        {{ $alert->vehicle->plate_number ?? '—' }}
                        <span class="font-normal text-gray-500 text-xs ml-1">
                            {{ $alert->vehicle->brand ?? '' }} {{ $alert->vehicle->model ?? '' }}
                        </span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $badgeClass }}">
                        {{ ucfirst($alert->urgency) }}
                    </span>
                    @if($alert->source === 'predicted')
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-purple-100 text-purple-700">
                        🔮 Predicted
                    </span>
                    @endif
                    @if($alert->is_read)
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Read</span>
                    @else
                    <span class="text-xs text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full font-medium">Unread</span>
                    @endif
                    @if($role === 'admin' && $alert->vehicle->owner)
                    <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded-full border">
                        {{ $alert->vehicle->owner->name }}
                    </span>
                    @endif
                </div>
                <p class="text-sm font-semibold text-gray-700">{{ $alert->alert_type }}</p>
                <p class="text-sm text-gray-600 mt-0.5">{{ $alert->recommendation }}</p>
                @if($alert->predicted_due_date)
                <p class="text-xs text-purple-600 mt-1 font-medium">
                    Predicted due: {{ $alert->predicted_due_date->format('d M Y') }}
                </p>
                @endif
                <p class="text-xs text-gray-400 mt-1">
                    Created {{ $alert->created_at->diffForHumans() }}
                    &bull; {{ $alert->created_at->format('d M Y') }}
                </p>
            </div>
            <div class="flex flex-col gap-2 items-end">
                @if(!$alert->is_read)
                <form method="POST"
                    action="/{{ $prefix }}/maintenance/{{ $alert->id }}/read">
                    @csrf @method('PATCH')
                    <button class="text-xs bg-white border text-gray-600 px-3 py-1 rounded hover:bg-gray-50 whitespace-nowrap">
                        Mark read
                    </button>
                </form>
                @endif
                @if($role === 'admin')
                <a href="/admin/vehicles/{{ $alert->vehicle_id }}"
                    class="text-xs text-blue-600 hover:underline whitespace-nowrap">
                    View vehicle →
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

@endsection