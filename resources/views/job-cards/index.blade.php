@extends('layouts.app')
@section('page-title', 'Job Cards')

@section('content')

<div class="flex justify-between items-center mb-6 flex-wrap gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Job Cards</h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                {{ $stageCounts['all'] }} total repair orders
            </span>
        </div>
        <p class="text-sm text-gray-500 mt-1">Manage workshop repair work orders, assigned mechanics, and stage lifecycles</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="/admin/job-cards/board"
            class="bg-white border border-gray-200 text-gray-700 px-3.5 py-2.5 rounded-xl text-xs font-semibold hover:bg-gray-50 transition shadow-xs flex items-center gap-1.5">
            <i data-lucide="kanban" class="w-4 h-4 text-purple-600"></i> Kanban Board
        </a>
        <a href="/admin/job-cards/create"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> New Job Card
        </a>
    </div>
</div>

{{-- Search & Filters --}}
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
    {{-- Search --}}
    <form method="GET" class="flex gap-2 max-w-md w-full">
        @if(request('stage'))<input type="hidden" name="stage" value="{{ request('stage') }}">@endif
        <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="search" value="{{ $search }}"
                placeholder="Search plate number or mechanic..."
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
        </div>
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
            Search
        </button>
        @if($search)
        <a href="{{ url()->current() }}{{ request('stage') ? '?stage='.request('stage') : '' }}"
            class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 flex items-center">
            Clear
        </a>
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
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
        <a href="{{ $tabHref() }}"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ !request('stage') ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            All <span class="ml-1 opacity-75 font-normal">({{ $stageCounts['all'] }})</span>
        </a>
        @foreach(['received','diagnosing','waiting_parts','repairing','quality_check','completed'] as $stage)
        <a href="{{ $tabHref($stage) }}"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border capitalize {{ request('stage') === $stage ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            {{ ucfirst(str_replace('_',' ',$stage)) }} <span class="ml-1 opacity-75 font-normal">({{ $stageCounts[$stage] }})</span>
        </a>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                <th class="px-5 py-3.5">Job ID</th>
                <th class="px-5 py-3.5">Vehicle</th>
                <th class="px-5 py-3.5">Job Type</th>
                <th class="px-5 py-3.5">Assigned Mechanic</th>
                <th class="px-5 py-3.5">Stage</th>
                <th class="px-5 py-3.5">Target Completion</th>
                <th class="px-5 py-3.5">Total Cost</th>
                <th class="px-5 py-3.5 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($jobs as $job)
            <tr class="hover:bg-gray-50/60 transition">
                <td class="px-5 py-4 font-mono text-xs font-bold text-gray-500">#{{ $job->id }}</td>
                <td class="px-5 py-4">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                            {{ $job->vehicle->plate_number ?? '—' }}
                        </span>
                        @if($job->vehicle)
                            @if($job->vehicle->isFleet())
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                    <i data-lucide="building-2" class="w-2.5 h-2.5 text-amber-600"></i> Fleet: {{ $job->vehicle->company->name ?? 'Corporate' }}
                                </span>
                            @else
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                    <i data-lucide="user" class="w-2.5 h-2.5 text-emerald-600"></i> Individual
                                </span>
                            @endif
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}
                    </p>
                </td>
                <td class="px-5 py-4">
                    @if($job->jobType)
                    <p class="text-sm font-semibold text-gray-800">{{ $job->jobType->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Est: {{ $job->jobType->estimated_time }}</p>
                    @else
                    <span class="text-gray-400 text-sm">—</span>
                    @endif
                </td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $job->staff->name ?? 'Unassigned' }}</td>
                <td class="px-5 py-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                        {{ $job->current_stage === 'completed'     ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          ($job->current_stage === 'repairing'     ? 'bg-blue-50 text-blue-700 border-blue-200' :
                          ($job->current_stage === 'quality_check' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                          ($job->current_stage === 'waiting_parts' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                          'bg-amber-50 text-amber-700 border-amber-200'))) }}">
                        <span class="w-1.5 h-1.5 rounded-full
                            {{ $job->current_stage === 'completed' ? 'bg-emerald-500' :
                              ($job->current_stage === 'repairing' ? 'bg-blue-500' :
                              ($job->current_stage === 'quality_check' ? 'bg-purple-500' :
                              ($job->current_stage === 'waiting_parts' ? 'bg-rose-500' : 'bg-amber-500'))) }}"></span>
                        {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    @if($job->estimated_completion)
                        <p class="text-sm font-bold {{ $job->estimated_completion->isPast() && $job->current_stage !== 'completed' ? 'text-rose-600' : 'text-gray-900' }}">
                            {{ $job->estimated_completion->format('d M Y') }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $job->estimated_completion->format('H:i') }}</p>
                        @if($job->estimated_completion->isPast() && $job->current_stage !== 'completed')
                        <span class="inline-block mt-0.5 text-[11px] font-bold text-rose-600 uppercase bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">
                            Overdue
                        </span>
                        @endif
                    @else
                    <span class="text-gray-400 text-sm">—</span>
                    @endif
                </td>
                <td class="px-5 py-4 font-bold text-sm text-gray-900">
                    RM {{ number_format($job->total_cost, 2) }}
                </td>
                <td class="px-5 py-4 text-right">
                    <a href="/admin/job-cards/{{ $job->id }}"
                        class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition">
                        Details &rarr;
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-12 text-center">
                    <x-empty-state icon="clipboard-list" title="No job cards found" subtitle="Try selecting a different stage filter or clearing search." />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($jobs->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $jobs->links() }}
    </div>
    @endif
</div>
@endsection