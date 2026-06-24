@extends('layouts.app')
@section('page-title', 'Job Cards')

@section('content')

<div class="flex justify-between items-center mb-4 flex-wrap gap-3">
    <h2 class="text-lg font-semibold text-gray-700">
        Job Cards
        <span class="text-sm font-normal text-gray-400 ml-2">{{ $jobs->count() }} total</span>
    </h2>
    <a href="/admin/job-cards/create"
        class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
        + New Job Card
    </a>
</div>

{{-- Filter tabs --}}
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/job-cards"
        class="px-4 py-2 rounded text-sm font-medium {{ !request('stage') ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        All ({{ $jobs->count() }})
    </a>
    @foreach(['received','diagnosing','waiting_parts','repairing','quality_check','completed'] as $stage)
    <a href="/admin/job-cards?stage={{ $stage }}"
        class="px-4 py-2 rounded text-sm font-medium capitalize {{ request('stage') === $stage ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        {{ ucfirst(str_replace('_',' ',$stage)) }}
    </a>
    @endforeach
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Vehicle</th>
                <th class="px-4 py-3">Job Type</th>
                <th class="px-4 py-3">Staff</th>
                <th class="px-4 py-3">Stage</th>
                <th class="px-4 py-3">Est. Completion</th>
                <th class="px-4 py-3">Cost</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jobs as $job)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">#{{ $job->id }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $job->vehicle->plate_number ?? '—' }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}
                    </p>
                </td>
                <td class="px-4 py-3">
                    @if($job->jobType)
                    <p class="font-medium">{{ $job->jobType->name }}</p>
                    <p class="text-xs text-gray-400">{{ $job->jobType->estimated_time }}</p>
                    @else
                    <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">{{ $job->staff->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $job->current_stage === 'completed'     ? 'bg-green-100 text-green-700' :
                          ($job->current_stage === 'repairing'     ? 'bg-blue-100 text-blue-700' :
                          ($job->current_stage === 'quality_check' ? 'bg-purple-100 text-purple-700' :
                          ($job->current_stage === 'waiting_parts' ? 'bg-red-100 text-red-700' :
                          'bg-yellow-100 text-yellow-700'))) }}">
                        {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs">
                    @if($job->estimated_completion)
                        <p class="{{ $job->estimated_completion->isPast() && $job->current_stage !== 'completed' ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                            {{ $job->estimated_completion->format('d M Y') }}
                        </p>
                        <p class="text-gray-400">{{ $job->estimated_completion->format('H:i') }}</p>
                        @if($job->estimated_completion->isPast() && $job->current_stage !== 'completed')
                        <span class="text-red-500 font-medium">⚠ Overdue</span>
                        @endif
                    @else
                    <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">RM {{ number_format($job->total_cost, 2) }}</td>
                <td class="px-4 py-3">
                    <a href="/admin/job-cards/{{ $job->id }}"
                        class="text-blue-600 hover:underline text-xs font-medium">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-8 text-center text-gray-400">No job cards found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection