@extends('layouts.app')
@section('page-title', 'Appointments')

@section('content')

<div class="flex justify-between items-center mb-6 flex-wrap gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Appointments</h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                {{ $statusCounts['all'] }} total bookings
            </span>
        </div>
        <p class="text-sm text-gray-500 mt-1">Manage scheduled service appointments and customer walk-ins</p>
    </div>
    <div class="flex items-center gap-2">
        @if(auth()->user()->role === 'admin')
        <a href="/admin/appointments/walkin"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i> New Walk-in
        </a>
        @elseif(auth()->user()->role === 'coordinator')
        <a href="/coordinator/appointments/walkin"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i> New Walk-in
        </a>
        @elseif(auth()->user()->role === 'staff')
        <a href="/staff/appointments/walkin"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i> New Walk-in
        </a>
        @else
        <a href="{{ request()->segment(1) === 'customer' ? '/customer/appointments/create' : '/client/appointments/create' }}"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="calendar-plus" class="w-4 h-4"></i> Book Appointment
        </a>
        @endif
    </div>
</div>

@if(auth()->user()->role === 'admin')
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 mb-6 flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <i data-lucide="gauge" class="w-5 h-5"></i>
        </div>
        <div>
            <div class="text-sm font-bold text-gray-900">Daily Booking Capacity</div>
            <p class="text-xs text-gray-500">Maximum concurrent appointments allowed per working day: <span class="font-bold text-blue-600">{{ $capacity }} slots</span></p>
        </div>
    </div>
    <form method="POST" action="/admin/appointments/capacity" class="flex items-center gap-2">
        @csrf
        <label for="capacity-input" class="text-xs font-semibold text-gray-600">Max/Day:</label>
        <input id="capacity-input" type="number" name="daily_appointment_capacity" value="{{ $capacity }}" min="1" max="200"
            class="w-24 border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
        <button type="submit" class="bg-slate-900 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
            Save
        </button>
    </form>
</div>
@endif

{{-- Search & Filters --}}
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
    {{-- Search --}}
    <form method="GET" class="flex gap-2 max-w-md w-full">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="search" value="{{ $search }}"
                placeholder="Search plate, customer, or service..."
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
        </div>
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
            Search
        </button>
        @if($search)
        <a href="{{ url()->current() }}{{ request('status') ? '?status='.request('status') : '' }}"
            class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 flex items-center">
            Clear
        </a>
        @endif
    </form>

    {{-- Filter tabs — accessible to all roles --}}
    @php
        $baseRoute = match(auth()->user()->role) {
            'coordinator' => '/coordinator/appointments',
            'corporate'   => '/client/appointments',
            'individual'  => '/customer/appointments',
            default       => '/admin/appointments',
        };
        $searchQs = $search ? 'search=' . urlencode($search) : '';
        $tabHref  = function ($status = null) use ($baseRoute, $searchQs) {
            $params = array_filter([$status ? "status={$status}" : null, $searchQs]);
            return $baseRoute . (count($params) ? '?' . implode('&', $params) : '');
        };
    @endphp
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
        <a href="{{ $tabHref() }}"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ !request('status') ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            All <span class="ml-1 opacity-75 font-normal">({{ $statusCounts['all'] }})</span>
        </a>
        <a href="{{ $tabHref('pending') }}"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ request('status') === 'pending' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            Pending <span class="ml-0.5 opacity-75 font-normal">({{ $statusCounts['pending'] }})</span>
        </a>
        <a href="{{ $tabHref('confirmed') }}"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ request('status') === 'confirmed' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            Confirmed <span class="ml-0.5 opacity-75 font-normal">({{ $statusCounts['confirmed'] }})</span>
        </a>
        <a href="{{ $tabHref('completed') }}"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ request('status') === 'completed' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Completed <span class="ml-0.5 opacity-75 font-normal">({{ $statusCounts['completed'] }})</span>
        </a>
        <a href="{{ $tabHref('cancelled') }}"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ request('status') === 'cancelled' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
            Cancelled <span class="ml-0.5 opacity-75 font-normal">({{ $statusCounts['cancelled'] }})</span>
        </a>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                <th class="px-5 py-3.5">Schedule</th>
                <th class="px-5 py-3.5">Service Requested</th>
                <th class="px-5 py-3.5">Vehicle</th>
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'coordinator')
                <th class="px-5 py-3.5">Customer / Contact</th>
                @endif
                <th class="px-5 py-3.5">Status</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($appointments as $apt)
            <tr class="hover:bg-gray-50/60 transition">
                <td class="px-5 py-4">
                    <p class="font-bold text-sm text-gray-900">{{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }}</p>
                    <p class="text-xs font-medium text-gray-500 flex items-center gap-1 mt-0.5">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-gray-400"></i>
                        {{ $apt->time }}
                    </p>
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm font-semibold text-gray-800">{{ $apt->service_type }}</span>
                    @if($apt->notes)
                    @php
                        $displayNote = $apt->notes;
                        if (str_contains($displayNote, 'Customer Complaint & Symptoms:')) {
                            $displayNote = \Illuminate\Support\Str::after($displayNote, 'Customer Complaint & Symptoms:');
                            $displayNote = trim(explode('•', explode("\n", $displayNote)[0])[0]);
                        }
                    @endphp
                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-1" title="{{ $displayNote }}">
                        <span class="font-semibold text-gray-600">Symptom:</span> {{ $displayNote }}
                    </p>
                    @endif
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                            {{ $apt->vehicle->plate_number ?? '—' }}
                        </span>
                        @if($apt->vehicle)
                            @if($apt->vehicle->isFleet())
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                    <i data-lucide="building-2" class="w-2.5 h-2.5 text-amber-600"></i> Fleet: {{ $apt->vehicle->company->name ?? 'Corporate' }}
                                </span>
                            @else
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                    <i data-lucide="user" class="w-2.5 h-2.5 text-emerald-600"></i> Individual
                                </span>
                            @endif
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $apt->vehicle->brand ?? '' }} {{ $apt->vehicle->model ?? '' }}</p>
                </td>
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'coordinator')
                <td class="px-5 py-4">
                    @if($apt->is_walkin)
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ $apt->walkin_name ?? 'Walk-in Customer' }}</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase">Walk-in</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $apt->walkin_contact ?? 'No phone' }}</p>
                    @else
                        <div class="flex items-center gap-2 flex-wrap">
                            @if(($apt->user->role ?? '') === 'corporate' && $apt->user->company)
                                <span class="text-sm font-bold text-gray-900">{{ $apt->user->company->name }}</span>
                                <span class="text-[11px] font-bold px-1.5 py-0.5 rounded border uppercase bg-amber-50 text-amber-800 border-amber-200">
                                    PIC: {{ $apt->user->name }}
                                </span>
                            @else
                                <span class="text-sm font-semibold text-gray-900">{{ $apt->user->name ?? '—' }}</span>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded border uppercase bg-emerald-50 text-emerald-700 border-emerald-200">
                                    Individual
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $apt->user->contact_no ?? $apt->user->email ?? '—' }}</p>
                    @endif
                </td>
                @endif
                <td class="px-5 py-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                        {{ $apt->status === 'completed'  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          ($apt->status === 'confirmed'  ? 'bg-blue-50 text-blue-700 border-blue-200' :
                          ($apt->status === 'cancelled'  ? 'bg-rose-50 text-rose-700 border-rose-200' :
                          'bg-amber-50 text-amber-700 border-amber-200')) }}">
                        <span class="w-1.5 h-1.5 rounded-full
                            {{ $apt->status === 'completed' ? 'bg-emerald-500' :
                              ($apt->status === 'confirmed' ? 'bg-blue-500' :
                              ($apt->status === 'cancelled' ? 'bg-rose-500' : 'bg-amber-500')) }}"></span>
                        {{ ucfirst($apt->status) }}
                    </span>
                </td>

                {{-- ACTION COLUMN --}}
                <td class="px-5 py-4 text-right">
                    @if(in_array(auth()->user()->role, ['admin', 'coordinator']))
                        @php $rolePrefix = auth()->user()->role === 'coordinator' ? 'coordinator' : 'admin'; @endphp
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="/{{ $rolePrefix }}/appointments/{{ $apt->id }}"
                                class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                <i data-lucide="eye" class="w-3.5 h-3.5 text-gray-400"></i> Details
                            </a>

                            @if($apt->status !== 'completed' && $apt->status !== 'cancelled')
                            <div class="relative inline-block text-left" x-data="{ open: false, dropUp: false }" @click.outside="open = false">
                                <button @click="open = !open; if(open) { dropUp = ($el.getBoundingClientRect().bottom + 180 > window.innerHeight); }" class="p-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 text-gray-600 transition">
                                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                </button>
                                <div x-show="open" x-transition
                                    :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                    class="absolute right-0 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1.5 z-30 text-left"
                                    style="display:none">
                                    @if($apt->status === 'pending')
                                        <form method="POST" action="/{{ $rolePrefix }}/appointments/{{ $apt->id }}/confirm" onsubmit="return confirm('Confirm this appointment and reserve shop bay?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                                                <i data-lucide="check-circle" class="w-4 h-4"></i> Confirm Appointment
                                            </button>
                                        </form>
                                        <form method="POST" action="/{{ $rolePrefix }}/appointments/{{ $apt->id }}/cancel" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 border-t border-gray-100">
                                                <i data-lucide="x-circle" class="w-4 h-4"></i> Cancel Booking
                                            </button>
                                        </form>
                                    @elseif($apt->status === 'confirmed')
                                        @if(!$apt->jobCard && auth()->user()->role === 'admin')
                                        <a href="/admin/job-cards/create?appointment_id={{ $apt->id }}"
                                            class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                            <i data-lucide="clipboard-plus" class="w-4 h-4"></i> Create Job Card
                                        </a>
                                        @elseif($apt->jobCard)
                                        @php
                                            $jobCardLink = auth()->user()->role === 'coordinator'
                                                ? "/coordinator/job-cards/{$apt->jobCard->id}"
                                                : "/admin/job-cards/{$apt->jobCard->id}";
                                        @endphp
                                        <a href="{{ $jobCardLink }}"
                                            class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                            <i data-lucide="clipboard-list" class="w-4 h-4"></i> View Job #{{ $apt->jobCard->id }}
                                        </a>
                                        @endif
                                        <form method="POST" action="/{{ $rolePrefix }}/appointments/{{ $apt->id }}/complete" onsubmit="return confirm('Mark this appointment as completed?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                                <i data-lucide="check-circle" class="w-4 h-4"></i> Mark Completed
                                            </button>
                                        </form>
                                        <form method="POST" action="/{{ $rolePrefix }}/appointments/{{ $apt->id }}/cancel" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 border-t border-gray-100">
                                                <i data-lucide="x-circle" class="w-4 h-4"></i> Cancel Booking
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    @else
                        @php
                            $detailUrl = auth()->user()->role === 'corporate'
                                ? "/client/appointments/{$apt->id}"
                                : "/customer/appointments/{$apt->id}";
                        @endphp
                        <a href="{{ $detailUrl }}"
                            class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-gray-400"></i> Details
                        </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-12 text-center">
                    <x-empty-state icon="calendar" title="No appointments found" subtitle="Try adjusting your search criteria or filter tabs." />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($appointments->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $appointments->links() }}
    </div>
    @endif
</div>
@endsection