@extends('layouts.app')
@section('page-title', "Today's Service Queue")

@section('content')

<div class="mb-5 p-4 bg-blue-50/70 border border-blue-200/80 rounded-xl flex items-center gap-3 text-sm text-blue-800">
    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 text-blue-700">
        <i data-lucide="info" class="w-4 h-4"></i>
    </div>
    <div class="leading-relaxed">
        <strong class="font-bold">Queue Priority Logic:</strong> Pre-scheduled appointments are prioritized by their confirmed time slots first; walk-in check-ins are sequenced directly afterwards based on check-in arrival timestamp.
    </div>
</div>

@if(isset($stats))
<div class="mb-5 bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
    <div class="flex items-center justify-between flex-wrap gap-4 pb-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <i data-lucide="gauge" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-gray-900 tracking-tight">Real-Time Workshop Bay Capacity & Intake Status</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                        {{ $stats['utilization_rate'] >= 90 ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                          ($stats['utilization_rate'] >= 70 ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                          'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                        {{ $stats['utilization_rate'] }}% Utilized
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">Live monitoring of daily intake slots and active service bays across workshop</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()->role, ['admin', 'coordinator']))
            <a href="/{{ auth()->user()->role }}/appointments" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition flex items-center gap-1.5">
                <i data-lucide="sliders" class="w-3.5 h-3.5"></i> Adjust Daily Cap
            </a>
            @endif
            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg
                {{ $stats['available_slots'] > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                <span class="w-2 h-2 rounded-full {{ $stats['available_slots'] > 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                {{ $stats['available_slots'] > 0 ? $stats['available_slots'] . ' Intake Slots Available' : 'Workshop Fully Booked' }}
            </span>
        </div>
    </div>

    {{-- Capacity Progress Meter --}}
    <div class="mt-4">
        <div class="flex justify-between items-center text-xs mb-1.5">
            <span class="font-medium text-gray-600">Daily Booking Load</span>
            <span class="font-bold text-gray-900">{{ $stats['total_booked'] }} / {{ $stats['capacity'] }} slots scheduled</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
            <div class="h-2.5 rounded-full transition-all duration-500
                {{ $stats['utilization_rate'] >= 90 ? 'bg-rose-500' : ($stats['utilization_rate'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                style="width: {{ min(100, $stats['utilization_rate']) }}%"></div>
        </div>
    </div>

    {{-- 4 Stat Micro-Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100">
        <div class="bg-gray-50/70 rounded-lg p-3 border border-gray-100">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 block">Daily Limit</span>
            <span class="text-xl font-extrabold text-gray-900">{{ $stats['capacity'] }}</span>
            <span class="text-[11px] text-gray-400 block mt-0.5">configured capacity</span>
        </div>
        <div class="bg-blue-50/50 rounded-lg p-3 border border-blue-100/60">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700 block">Queue Today</span>
            <span class="text-xl font-extrabold text-blue-900">{{ $stats['total_booked'] }}</span>
            <span class="text-[11px] text-blue-600 block mt-0.5">confirmed & walk-ins</span>
        </div>
        <div class="bg-purple-50/50 rounded-lg p-3 border border-purple-100/60">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-purple-700 block">In Service Bays</span>
            <span class="text-xl font-extrabold text-purple-900">{{ $stats['active_in_bay'] }}</span>
            <span class="text-[11px] text-purple-600 block mt-0.5">active job cards</span>
        </div>
        <div class="bg-emerald-50/50 rounded-lg p-3 border border-emerald-100/60">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 block">Completed Today</span>
            <span class="text-xl font-extrabold text-emerald-900">{{ $stats['completed_today'] }}</span>
            <span class="text-[11px] text-emerald-600 block mt-0.5">finished & inspected</span>
        </div>
    </div>
</div>
@endif

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900 tracking-tight">
                {{ now()->format('l, d F Y') }}
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Real-time daily service bay queue</p>
        </div>
        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-900 text-white shadow-xs">
            {{ $appointments->count() }} vehicles scheduled
        </span>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                <th class="px-5 py-3.5 w-12 text-center">Pos</th>
                <th class="px-5 py-3.5">Type</th>
                <th class="px-5 py-3.5">Slot / Arrived</th>
                <th class="px-5 py-3.5">Customer</th>
                <th class="px-5 py-3.5">Vehicle</th>
                <th class="px-5 py-3.5">Requested Service</th>
                <th class="px-5 py-3.5 text-right">Job Order</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($appointments as $i => $apt)
            <tr class="hover:bg-gray-50/60 transition">
                <td class="px-5 py-4 text-center font-mono text-sm font-bold text-gray-400">{{ $i + 1 }}</td>
                <td class="px-5 py-4">
                    @if($apt->is_walkin)
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded">
                        Walk-in
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded">
                        Booked
                    </span>
                    @endif
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm font-bold text-gray-900">
                        {{ $apt->is_walkin ? $apt->created_at->format('H:i') : $apt->time }}
                    </span>
                    @if($apt->is_walkin)
                    <span class="text-xs text-gray-400 block">Checked in</span>
                    @endif
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm font-semibold text-gray-900 block">
                        {{ $apt->is_walkin ? ($apt->walkin_name ?? 'Walk-in Customer') : ($apt->user->name ?? '—') }}
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ $apt->is_walkin ? ($apt->walkin_contact ?? 'No contact') : ($apt->user->contact_no ?? 'No contact') }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                        {{ $apt->vehicle->plate_number ?? '—' }}
                    </span>
                    <p class="text-xs text-gray-500 mt-1">{{ $apt->vehicle->brand ?? '' }} {{ $apt->vehicle->model ?? '' }}</p>
                </td>
                <td class="px-5 py-4">
                    <span class="text-sm font-semibold text-gray-800">{{ $apt->service_type }}</span>
                </td>
                <td class="px-5 py-4 text-right">
                    @if($apt->jobCard)
                    @php
                        $jobCardUrl = match(auth()->user()->role) {
                            'staff'       => "/staff/job-cards/{$apt->jobCard->id}",
                            'coordinator' => "/coordinator/job-cards/{$apt->jobCard->id}",
                            default       => "/admin/job-cards/{$apt->jobCard->id}",
                        };
                    @endphp
                    <a href="{{ $jobCardUrl }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition">
                        <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i>
                        #{{ $apt->jobCard->id }} &bull; {{ $apt->jobCard->staff->name ?? 'Unassigned' }}
                    </a>
                    @else
                    @if(auth()->user()->role === 'admin')
                    <a href="/admin/job-cards/create?appointment_id={{ $apt->id }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Dispatch Job
                    </a>
                    @else
                    <span class="inline-flex items-center gap-1 text-xs text-gray-400 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span> Not started
                    </span>
                    @endif
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-12 text-center">
                    <x-empty-state icon="clock" title="Queue is empty today" subtitle="No confirmed appointments or walk-ins are currently waiting in queue." />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection