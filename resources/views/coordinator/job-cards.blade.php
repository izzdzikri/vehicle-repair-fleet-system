@extends('layouts.app')
@section('page-title', 'Job Cards — Progress Check')

@section('content')

<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/coordinator/job-cards" class="px-4 py-2 rounded text-sm font-medium {{ !request('filter') ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        All Active ({{ $totalActive }})
    </a>
    <a href="/coordinator/job-cards?filter=stale" class="px-4 py-2 rounded text-sm font-medium {{ request('filter') === 'stale' ? 'bg-red-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        Needs Check-in ({{ $staleTotal }})
    </a>
    <a href="/coordinator/job-cards?filter=overdue" class="px-4 py-2 rounded text-sm font-medium {{ request('filter') === 'overdue' ? 'bg-orange-500 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        Overdue ({{ $overdueTotal }})
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[800px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Vehicle</th>
                <th class="px-4 py-3">Job Type</th>
                <th class="px-4 py-3">Staff</th>
                <th class="px-4 py-3">Stage</th>
                <th class="px-4 py-3">Deadline</th>
                <th class="px-4 py-3">Last Update</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jobs as $job)
            @php
                $overdue = $job->estimated_completion && $job->estimated_completion->isPast();
            @endphp
            <tr class="border-b hover:bg-gray-50 {{ $job->is_stale ? 'bg-red-50' : ($overdue ? 'bg-orange-50' : '') }}">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $job->vehicle->plate_number ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</p>
                </td>
                <td class="px-4 py-3">{{ $job->jobType->name ?? '—' }}</td>
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
                <td class="px-4 py-3 text-xs {{ $overdue ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                    {{ $job->estimated_completion ? $job->estimated_completion->format('d M, H:i') : '—' }}
                </td>
                <td class="px-4 py-3">
                    @if($job->is_stale)
                    <span class="inline-flex items-center gap-1 text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-medium animate-pulse">
                        <i data-lucide="alarm-clock" class="w-3 h-3"></i> {{ $job->hours_since_update }}h ago
                    </span>
                    @else
                    <span class="text-xs text-gray-400">{{ $job->hours_since_update }}h ago</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="/coordinator/job-cards/{{ $job->id }}" class="text-blue-600 hover:underline text-xs font-medium">Check</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="py-8 text-center text-gray-400">No job cards match this filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection