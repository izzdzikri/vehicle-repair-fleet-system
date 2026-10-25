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