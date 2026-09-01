@extends('layouts.app')
@section('page-title', 'Repair Progress Dashboard')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="clipboard-list" class="w-6 h-6 text-blue-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Active Jobs</p>
        <p class="text-3xl font-bold text-blue-600">{{ $totalActive }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="alarm-clock" class="w-6 h-6 {{ $staleCount > 0 ? 'text-red-500' : 'text-gray-400' }} mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Needs Check-in</p>
        <p class="text-3xl font-bold {{ $staleCount > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $staleCount }}</p>
        @if($staleCount > 0)
        <a href="/coordinator/job-cards?filter=stale" class="text-xs text-blue-600 hover:underline">Review →</a>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="alert-triangle" class="w-6 h-6 {{ $overdueCount > 0 ? 'text-orange-500' : 'text-gray-400' }} mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Overdue Jobs</p>
        <p class="text-3xl font-bold {{ $overdueCount > 0 ? 'text-orange-500' : 'text-gray-400' }}">{{ $overdueCount }}</p>
        @if($overdueCount > 0)
        <a href="/coordinator/job-cards?filter=overdue" class="text-xs text-blue-600 hover:underline">Review →</a>
        @endif
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-700">Most Urgent Right Now</h2>
        <a href="/coordinator/job-cards" class="text-sm text-blue-600 hover:underline">View all active jobs →</a>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Vehicle</th>
                <th class="px-4 py-3">Staff</th>
                <th class="px-4 py-3">Stage</th>
                <th class="px-4 py-3">Deadline</th>
                <th class="px-4 py-3">Last Update</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($urgentJobs as $job)
            @php
                $overdue = $job->estimated_completion && $job->estimated_completion->isPast();
            @endphp
            <tr class="border-b hover:bg-gray-50 {{ $job->is_stale ? 'bg-red-50' : ($overdue ? 'bg-orange-50' : '') }}">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $job->vehicle->plate_number ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</p>
                </td>
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
            <tr><td colspan="6" class="py-8 text-center text-gray-400">No active jobs right now.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection