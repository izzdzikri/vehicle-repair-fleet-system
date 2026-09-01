@extends('layouts.app')
@section('page-title', 'Job Schedule (EDF)')

@section('content')

<div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
    ℹ Earliest Deadline First — active jobs are ordered by their estimated completion time so the most urgent work is always at the top.
    Jobs with no update in over 3 hours are flagged below as a reminder to check in on their status.
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">
            Active Jobs by Deadline
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $jobs->count() }} in progress</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[850px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Priority</th>
                <th class="px-4 py-3">Vehicle</th>
                <th class="px-4 py-3">Job Type</th>
                <th class="px-4 py-3">Staff</th>
                <th class="px-4 py-3">Stage</th>
                <th class="px-4 py-3">Deadline</th>
                <th class="px-4 py-3">Status Check</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jobs as $i => $job)
            @php
                $overdue = $job->estimated_completion && $job->estimated_completion->isPast();
                $urgent  = $job->estimated_completion && !$overdue && now()->diffInHours($job->estimated_completion, false) <= 2;
                $rowClass = $overdue ? 'bg-red-50' : ($urgent ? 'bg-yellow-50' : '');
            @endphp
            <tr class="border-b hover:bg-gray-100 {{ $rowClass }}">
                <td class="px-4 py-3 font-bold text-gray-400">#{{ $i + 1 }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $job->vehicle->plate_number ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</p>
                </td>
                <td class="px-4 py-3">{{ $job->jobType->name ?? $job->appointment->service_type ?? '—' }}</td>
                <td class="px-4 py-3">{{ $job->staff->name ?? 'Unassigned' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $job->current_stage === 'repairing' ? 'bg-blue-100 text-blue-700' :
                          ($job->current_stage === 'quality_check' ? 'bg-purple-100 text-purple-700' :
                          ($job->current_stage === 'waiting_parts' ? 'bg-red-100 text-red-700' :
                          'bg-yellow-100 text-yellow-700')) }}">
                        {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($job->estimated_completion)
                    <p class="{{ $overdue ? 'text-red-600 font-semibold' : ($urgent ? 'text-yellow-600 font-medium' : 'text-gray-500') }}">
                        {{ $job->estimated_completion->format('d M, H:i') }}
                    </p>
                    @if($overdue)
                    <span class="text-xs text-red-500 font-medium">⚠ Overdue</span>
                    @elseif($urgent)
                    <span class="text-xs text-yellow-600 font-medium">⏱ Due soon</span>
                    @endif
                    @else
                    <span class="text-gray-400 text-xs">No deadline set</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($job->is_stale)
                    <span class="inline-flex items-center gap-1 text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-medium animate-pulse">
                        <i data-lucide="alarm-clock" class="w-3 h-3"></i> {{ $job->hours_since_update }}h — check in
                    </span>
                    @else
                    <span class="text-xs text-gray-400">Updated {{ $job->hours_since_update }}h ago</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="/{{ auth()->user()->role === 'admin' ? 'admin' : 'staff' }}/job-cards/{{ $job->id }}"
                        class="text-blue-600 hover:underline text-xs font-medium">Open</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="py-8 text-center text-gray-400">No active jobs. Everything's caught up.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection