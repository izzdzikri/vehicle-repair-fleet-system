@extends('layouts.app')
@section('page-title', 'Fleet Dashboard')

@section('content')

<div class="flex justify-between items-start mb-2 flex-wrap gap-4">
    <div>
        @include('partials.greeting')
    </div>
    <div class="flex items-center gap-2.5 mb-6">
        <a href="/client/statement"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition">
            <i data-lucide="file-text" class="w-4 h-4"></i> Fleet Statement
        </a>
    </div>
</div>

@if(auth()->user()->status === 'inactive')
<div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm font-medium text-rose-800 flex items-center gap-3">
    <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
    <span>Your account is currently <strong>inactive</strong>. Please contact the workshop administrator.</span>
</div>
@endif

{{-- Executive Stat Grid --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fleet Size</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="truck" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $vehicles->count() }}</p>
        <span class="text-xs font-medium text-gray-500 mt-1 inline-block">Registered vehicles</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Appointments</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="calendar" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $appointments->count() }}</p>
        <span class="text-xs font-medium text-emerald-700 mt-1 inline-block">Recent bookings</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Alerts</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-3xl font-extrabold {{ $maintenanceAlerts->count() > 0 ? 'text-rose-600' : 'text-gray-900' }} tracking-tight">
            {{ $maintenanceAlerts->count() }}
        </p>
        @if($maintenanceAlerts->count() > 0)
        <a href="/client/maintenance" class="text-xs font-semibold text-rose-600 hover:text-rose-700 mt-1 inline-flex items-center gap-1">
            Review Alerts <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
        @else
        <span class="text-xs font-medium text-gray-400 mt-1 inline-block">All systems normal</span>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-gray-300 transition">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Spent</span>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                <i data-lucide="receipt" class="w-4 h-4"></i>
            </div>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($totalSpent, 0) }}</p>
        <span class="text-xs font-medium text-gray-500 mt-1 inline-block">Lifetime maintenance</span>
    </div>
</div>

{{-- Urgent Alerts Banner --}}
@if($maintenanceAlerts->where('is_read', false)->count())
<div class="bg-rose-50/70 border border-rose-200/80 rounded-xl p-5 mb-6 shadow-xs">
    <div class="flex justify-between items-center mb-3">
        <div class="flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600"></i>
            <h2 class="text-sm font-bold text-rose-900 uppercase tracking-wider">Maintenance Alerts Pending Attention</h2>
        </div>
        <a href="/client/maintenance" class="text-xs font-bold text-rose-700 hover:text-rose-800 underline flex items-center gap-1">
            View All Alerts <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
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
            <a href="/client/maintenance" class="text-xs font-semibold text-blue-600 hover:text-blue-700 shrink-0">Details &rarr;</a>
        </div>
        @endforeach
    </div>
    @if($maintenanceAlerts->where('is_read', false)->count() > 3)
    <div class="mt-3 text-right">
        <a href="/client/maintenance" class="text-xs font-bold text-rose-700 hover:text-rose-800">
            +{{ $maintenanceAlerts->where('is_read', false)->count() - 3 }} more alerts requiring action &rarr;
        </a>
    </div>
    @endif
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Fleet Vehicles List --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Fleet Vehicles</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">{{ $vehicles->count() }} active assets registered</p>
            </div>
            @if(!auth()->user()->isSecondaryPic())
            <a href="/client/vehicles/create"
                class="inline-flex items-center gap-1.5 bg-slate-900 text-white px-3.5 py-1.5 rounded-lg hover:bg-slate-800 text-xs font-semibold shadow-xs transition">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Vehicle
            </a>
            @endif
        </div>

        <div class="space-y-2.5">
            @forelse($vehicles as $v)
            <a href="/client/vehicles/{{ $v->id }}"
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
                        @if($v->owner && $v->owner->id !== auth()->id())
                        <p class="text-xs font-medium text-gray-400 mt-1">
                            Registered by: {{ $v->owner->name }}
                        </p>
                        @endif
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
                            <i data-lucide="alert-triangle" class="w-3 h-3"></i> {{ $vAlert->urgency === 'high' ? 'Action Required' : 'Alert' }}
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
    </div>

    {{-- Recent Appointments --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Recent Appointments</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">Track servicing schedules and bookings</p>
            </div>
            @if(auth()->user()->status === 'active' && !auth()->user()->isSecondaryPic())
            <a href="/client/appointments/create"
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
                        @if($apt->user && $apt->user->id !== auth()->id())
                        <p class="text-xs text-gray-400 mt-0.5">
                            Booked by: {{ $apt->user->name }}
                        </p>
                        @endif
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
                        <a href="/client/appointments/{{ $apt->id }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 group-hover:translate-x-0.5 transition">
                            View Details &rarr;
                        </a>
                    </div>
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

</div>

{{-- Service History --}}
@if($serviceHistory->count())
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6">
    <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Fleet Service History</h2>
            <p class="text-xs font-medium text-gray-500 mt-0.5">{{ $totalServices }} total logged service events</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Total Spent</span>
                <span class="text-base font-extrabold text-emerald-700">RM {{ number_format($totalSpent, 2) }}</span>
            </div>
            <a href="/client/statement"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Full Statement
            </a>
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

{{-- Company PICs quick view --}}
@if(isset($companyPics) && $companyPics->count() > 0)
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
    <div class="flex justify-between items-center mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Authorized Persons In Charge (PIC)</h2>
            <p class="text-xs font-medium text-gray-500 mt-0.5">{{ $companyPics->count() }} registered contact(s)</p>
        </div>
        <a href="/client/company"
            class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
            Manage Team <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
        @foreach($companyPics as $pic)
        <div class="flex items-center gap-3.5 border border-gray-200/80 rounded-xl p-3.5 hover:bg-gray-50/50 transition">
            <img src="{{ $pic->avatar_url }}"
                class="w-11 h-11 rounded-full object-cover border-2 border-blue-200 shrink-0">
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm text-gray-900 truncate">{{ $pic->name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ $pic->email }}</p>
                @if($pic->contact_no)
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $pic->contact_no }}</p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-1.5 shrink-0">
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold
                    {{ $pic->pic_role === 'primary' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                    {{ $pic->pic_role === 'primary' ? '★ Primary' : '◎ Viewer' }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                    {{ $pic->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                    {{ ucfirst($pic->status) }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
