@extends('layouts.app')
@section('page-title', 'Job Cards')

@section('content')

<div class="flex justify-between items-center mb-4 flex-wrap gap-3">
    <h2 class="text-lg font-semibold text-gray-700">
        Job Cards
        <span class="text-sm font-normal text-gray-400 ml-2">{{ $stageCounts['all'] }} total</span>
    </h2>
    <a href="/admin/job-cards/create"
        class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
        + New Job Card
    </a>
</div>

{{-- Search --}}
<form method="GET" class="mb-4 flex gap-2">
    @if(request('stage'))<input type="hidden" name="stage" value="{{ request('stage') }}">@endif
    <input type="text" name="search" value="{{ $search }}"
        placeholder="Search by plate number or staff name..."
        class="flex-1 max-w-md border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Search</button>
    @if($search)
    <a href="{{ url()->current() }}{{ request('stage') ? '?stage='.request('stage') : '' }}"
        class="px-4 py-2 rounded text-sm border text-gray-600 hover:bg-gray-50">Clear</a>
    @endif
</form>

{{-- Filter tabs --}}
@php
    $searchQs = $search ? 'search=' . urlencode($search) : '';
    $tabHref  = function ($stage = null) use ($searchQs) {
        $params = array_filter([$stage ? "stage={$stage}" : null, $searchQs]);
        return '/admin/job-cards' . (count($params) ? '?' . implode('&', $params) : '');
    };
@endphp
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="{{ $tabHref() }}"
        class="px-4 py-2 rounded text-sm font-medium {{ !request('stage') ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        All ({{ $stageCounts['all'] }})
    </a>
    @foreach(['received','diagnosing','waiting_parts','repairing','quality_check','completed'] as $stage)
    <a href="{{ $tabHref($stage) }}"
        class="px-4 py-2 rounded text-sm font-medium capitalize {{ request('stage') === $stage ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        {{ ucfirst(str_replace('_',' ',$stage)) }} ({{ $stageCounts[$stage] }})
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
    @if($jobs->hasPages())
    <div class="px-4 py-3 border-t">
        {{ $jobs->links() }}
    </div>
    @endif
</div>
@endsection