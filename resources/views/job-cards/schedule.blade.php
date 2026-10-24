@extends('layouts.app')
@section('page-title', 'Job Schedule (EDF)')

@section('content')

<div class="mb-5 p-4 bg-blue-50/70 border border-blue-200/80 rounded-xl flex items-center gap-3 text-sm text-blue-800">
    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 text-blue-700">
        <i data-lucide="calendar-clock" class="w-4 h-4"></i>
    </div>
    <div class="leading-relaxed">
        <strong class="font-bold">Earliest Deadline First (EDF) Dispatch:</strong> In-flight repair work orders sorted dynamically by nearest estimated completion time so urgent service commitments remain on schedule.
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900 tracking-tight">Active Jobs by Deadline Priority</h2>
            <p class="text-xs text-gray-500 mt-0.5">Shop floor dispatch order and update health</p>
        </div>
        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-900 text-white shadow-xs">
            {{ $jobs->count() }} active orders
        </span>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                <th class="px-5 py-3.5 w-12 text-center">Rank</th>
                <th class="px-5 py-3.5">Vehicle</th>
                <th class="px-5 py-3.5">Job Type</th>
                <th class="px-5 py-3.5">Assigned Mechanic</th>
                <th class="px-5 py-3.5">Current Stage</th>
                <th class="px-5 py-3.5">Target Deadline</th>
                <th class="px-5 py-3.5">Activity Health</th>
                <th class="px-5 py-3.5 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($jobs as $i => $job)
            @php
                $overdue = $job->estimated_completion && $job->estimated_completion->isPast();
                $urgent  = $job->estimated_completion && !$overdue && now()->diffInHours($job->estimated_completion, false) <= 2;
                $rowClass = $overdue ? 'bg-rose-50/30' : ($urgent ? 'bg-amber-50/30' : '');
            @endphp
            <tr class="hover:bg-gray-50/60 transition {{ $rowClass }}">
                <td class="px-5 py-4 text-center font-mono text-sm font-bold text-gray-400">#{{ $i + 1 }}</td>
                <td class="px-5 py-4">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                        {{ $job->vehicle->plate_number ?? '—' }}
                    </span>
                    <p class="text-xs text-gray-500 mt-1">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</p>
                </td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                    {{ $job->jobType->name ?? $job->appointment->service_type ?? '—' }}
                </td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-800">
                    {{ $job->staff->name ?? 'Unassigned' }}
                </td>
                <td class="px-5 py-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                        {{ $job->current_stage === 'repairing' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                          ($job->current_stage === 'quality_check' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                          ($job->current_stage === 'waiting_parts' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                          'bg-amber-50 text-amber-700 border-amber-200')) }}">
                        <span class="w-1.5 h-1.5 rounded-full
                            {{ $job->current_stage === 'repairing' ? 'bg-blue-500' :
                              ($job->current_stage === 'quality_check' ? 'bg-purple-500' :
                              ($job->current_stage === 'waiting_parts' ? 'bg-rose-500' : 'bg-amber-500')) }}"></span>
                        {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    @if($job->estimated_completion)
                    <p class="text-sm font-bold {{ $overdue ? 'text-rose-600' : ($urgent ? 'text-amber-700' : 'text-gray-900') }}">
                        {{ $job->estimated_completion->format('d M, H:i') }}
                    </p>
                    @if($overdue)
                    <span class="inline-block mt-0.5 text-[11px] font-bold text-rose-600 uppercase bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">
                        Overdue
                    </span>
                    @elseif($urgent)
                    <span class="inline-block mt-0.5 text-[11px] font-bold text-amber-700 uppercase bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">
                        Due soon
                    </span>
                    @endif
                    @else
                    <span class="text-gray-400 text-xs">No deadline set</span>
                    @endif
                </td>
                <td class="px-5 py-4">
                    @if($job->is_stale)
                    <span class="inline-flex items-center gap-1.5 text-xs bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 rounded-full font-bold">
                        <i data-lucide="alarm-clock" class="w-3.5 h-3.5 text-rose-500 animate-pulse"></i> {{ $job->hours_since_update }}h inactive
                    </span>
                    @else
                    <span class="text-xs font-medium text-gray-500">Updated {{ $job->hours_since_update }}h ago</span>
                    @endif
                </td>
                <td class="px-5 py-4 text-right">
                    <a href="/{{ auth()->user()->role === 'admin' ? 'admin' : 'staff' }}/job-cards/{{ $job->id }}"
                        class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition">
                        Open &rarr;
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-12 text-center">
                    <x-empty-state icon="check-circle" title="All caught up" subtitle="No active jobs are currently awaiting completion." />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection