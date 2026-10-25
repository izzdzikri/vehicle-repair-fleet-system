@extends('layouts.app')
@section('page-title', 'Customer Dashboard')

@section('content')

@include('partials.greeting')

@if(auth()->user()->status === 'inactive')
<div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm font-medium text-rose-800 flex items-center gap-3">
    <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
    <span>Your account is currently <strong>inactive</strong>. Please contact our workshop support.</span>
</div>
@endif

{{-- Customer Stat Grid --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">My Vehicles</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="car" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $vehicles->count() }}</p>
        <span class="text-xs font-medium text-gray-500 mt-1 inline-block">Registered cars</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Appointments</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="calendar" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $appointments->count() }}</p>
        <span class="text-xs font-medium text-emerald-700 mt-1 inline-block">Service bookings</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Alerts</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-3xl font-extrabold {{ $maintenanceAlerts->count() > 0 ? 'text-rose-600' : 'text-gray-900' }} tracking-tight">
            {{ $maintenanceAlerts->count() }}
        </p>
        @if($maintenanceAlerts->count() > 0)
        <a href="/customer/maintenance" class="text-xs font-semibold text-rose-600 hover:text-rose-700 mt-1 inline-flex items-center gap-1">
            View Alerts <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
        @else
        <span class="text-xs font-medium text-gray-400 mt-1 inline-block">Everything looks good</span>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Spent</span>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                <i data-lucide="receipt" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($totalSpent ?? 0, 0) }}</p>
        <span class="text-xs font-medium text-gray-500 mt-1 inline-block">Total service payments</span>
    </div>
</div>

{{-- Urgent Alerts Banner --}}
@if($maintenanceAlerts->where('is_read', false)->count())
<div class="bg-rose-50/70 border border-rose-200/80 rounded-xl p-5 mb-6 shadow-xs">
    <div class="flex justify-between items-center mb-3">
        <div class="flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600"></i>
            <h2 class="text-sm font-bold text-rose-900 uppercase tracking-wider">Maintenance Alerts for Your Vehicles</h2>
        </div>
        <a href="/customer/maintenance" class="text-xs font-bold text-rose-700 hover:text-rose-800 underline flex items-center gap-1">
            View All <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>
    <div class="space-y-2">
        @foreach($maintenanceAlerts->where('is_read', false)->take(3) as $alert)
        <div class="flex items-start justify-between gap-3 bg-white/80 p-3 rounded-lg border border-rose-100 text-sm">
            <div class="flex items-start gap-2.5">
                <span class="text-xs font-bold px-2 py-0.5 rounded uppercase tracking-wider shrink-0 mt-0.5
                    {{ $alert->urgency === 'high' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                    {{ ucfirst($alert->urgency) }}
                </span>
                <div>
                    <span class="font-mono text-xs font-bold bg-gray-100 px-2 py-0.5 rounded border border-gray-200 text-gray-800 mr-1.5">
                        {{ $alert->vehicle->plate_number ?? '—' }}
                    </span>
                    <span class="font-semibold text-gray-900">{{ $alert->alert_type }}</span>
                    <span class="text-gray-600 block text-xs mt-0.5">{{ $alert->recommendation }}</span>
                </div>
            </div>
            <a href="/customer/maintenance" class="text-xs font-semibold text-blue-600 hover:text-blue-700 shrink-0">Details &rarr;</a>
        </div>
        @endforeach
    </div>
    @if($maintenanceAlerts->where('is_read', false)->count() > 3)
    <div class="mt-3 text-right">
        <a href="/customer/maintenance" class="text-xs font-bold text-rose-700 hover:text-rose-800">
            +{{ $maintenanceAlerts->where('is_read', false)->count() - 3 }} more alerts &rarr;
        </a>
    </div>
    @endif
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- My Vehicles List --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900">My Vehicles</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">{{ $vehicles->count() }} car(s) linked to your account</p>
            </div>
            <a href="/customer/vehicles/create"
                class="inline-flex items-center gap-1.5 bg-blue-600 text-white px-3.5 py-1.5 rounded-lg hover:bg-blue-700 text-xs font-semibold shadow-xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Vehicle
            </a>
        </div>

        <div class="space-y-2.5">
            @forelse($vehicles as $v)
            <a href="/customer/vehicles/{{ $v->id }}"
                class="block border border-gray-200/80 rounded-xl p-3.5 hover:border-blue-300 hover:bg-blue-50/30 transition">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold bg-gray-100 text-gray-900 px-2 py-0.5 rounded border border-gray-200">
                                {{ $v->plate_number }}
                            </span>
                            <span class="text-sm font-bold text-gray-900">{{ $v->brand }} {{ $v->model }}</span>
                            <span class="text-xs text-gray-500">({{ $v->year }})</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-700">{{ number_format($v->mileage) }} km</p>
                        @php
                            $vAlert = $maintenanceAlerts
                                ->where('vehicle_id', $v->id)
                                ->where('is_read', false)
                                ->first();
                        @endphp
                        @if($vAlert)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full mt-1 inline-flex items-center gap-1
                            {{ $vAlert->urgency === 'high' ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-amber-100 text-amber-700 border border-amber-200' }}">
                            <i data-lucide="alert-triangle" class="w-3 h-3"></i> {{ $vAlert->urgency === 'high' ? 'Action Needed' : 'Alert' }}
                        </span>
                        @endif
                    </div>
                </div>
            </a>
            @empty
            <x-empty-state icon="car" title="No vehicles registered"
                subtitle="Add your car to track maintenance and book appointments."
                action-href="/customer/vehicles/create"
                action-label="+ Add Vehicle" />
            @endforelse
        </div>
    </div>

    {{-- Recent Appointments --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Recent Appointments</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">Your workshop bookings & status</p>
            </div>
            @if(auth()->user()->status === 'active')
            <a href="/customer/appointments/create"
                class="inline-flex items-center gap-1.5 bg-slate-900 text-white px-3.5 py-1.5 rounded-lg hover:bg-slate-800 text-xs font-semibold shadow-xs transition">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Book Appointment
            </a>
            @endif
        </div>

        <div class="space-y-2.5">
            @forelse($appointments as $apt)
            <div class="border border-gray-200/80 rounded-xl p-3.5 hover:border-blue-300 hover:bg-blue-50/20 transition group">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold bg-gray-100 px-2 py-0.5 rounded border border-gray-200 text-gray-800">
                                {{ $apt->vehicle->plate_number ?? '—' }}
                            </span>
                            <span class="font-bold text-sm text-gray-900">{{ $apt->service_type }}</span>
                        </div>
                        <p class="text-xs font-medium text-gray-500 mt-1">
                            {{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }} at {{ $apt->time }}
                        </p>
                        @if($apt->jobCard)
                        <div class="mt-2 inline-flex items-center gap-1.5 px-2 py-0.5 bg-blue-50 border border-blue-200 rounded text-xs font-bold text-blue-700">
                            <span>Job #{{ $apt->jobCard->id }}</span>
                            <span>&bull;</span>
                            <span>{{ ucfirst(str_replace('_', ' ', $apt->jobCard->current_stage)) }}</span>
                            @if($apt->jobCard->total_cost > 0)
                            <span>&bull; RM {{ number_format($apt->jobCard->total_cost, 2) }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold shrink-0
                            {{ $apt->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                              ($apt->status === 'confirmed' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                              ($apt->status === 'cancelled' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                              'bg-amber-50 text-amber-700 border border-amber-200')) }}">
                            {{ ucfirst($apt->status) }}
                        </span>
                        <a href="/customer/appointments/{{ $apt->id }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 group-hover:translate-x-0.5 transition">
                            View Details &rarr;
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <x-empty-state icon="calendar" title="No appointments yet"
                subtitle="Book your first appointment to schedule workshop service."
                action-href="/customer/appointments/create"
                action-label="+ Book Appointment" />
            @endforelse
        </div>
    </div>

</div>

{{-- Service History --}}
@if($serviceHistory->count())
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Vehicle Service History</h2>
            <p class="text-xs font-medium text-gray-500 mt-0.5">{{ $totalServices }} total service visits recorded</p>
        </div>
        <div class="text-right">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Total Spent</span>
            <span class="text-base font-extrabold text-emerald-700">RM {{ number_format($totalSpent ?? 0, 2) }}</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/75 border-y border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4">Vehicle</th>
                    <th class="py-3 px-4">Service Description</th>
                    <th class="py-3 px-4 text-right">Cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($serviceHistory as $h)
                <tr class="hover:bg-gray-50/80 transition">
                    <td class="py-3 px-4 text-xs font-semibold text-gray-500">
                        {{ \Carbon\Carbon::parse($h->service_date)->format('d M Y') }}
                    </td>
                    <td class="py-3 px-4">
                        <span class="font-mono text-xs font-bold bg-gray-100 px-2 py-0.5 rounded border border-gray-200 text-gray-800">
                            {{ $h->vehicle->plate_number ?? '—' }}
                        </span>
                    </td>
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $h->description }}</td>
                    <td class="py-3 px-4 text-right font-bold text-gray-900">RM {{ number_format($h->cost, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
