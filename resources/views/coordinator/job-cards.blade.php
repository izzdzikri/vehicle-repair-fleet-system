@extends('layouts.app')
@section('page-title', 'Job Cards — Progress Check')

@section('content')

{{-- Filter segmented toolbar --}}
<div class="flex items-center gap-2 mb-5 flex-wrap">
    <a href="/coordinator/job-cards?filter=all"
        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition border {{ (!$activeFilter || $activeFilter === 'all') ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' }}">
        All Active ({{ $totalActive }})
    </a>
    <a href="/coordinator/job-cards?filter=stale"
        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition border {{ $activeFilter === 'stale' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' }}">
        Needs Check-in ({{ $staleTotal }})
    </a>
    <a href="/coordinator/job-cards?filter=overdue"
        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition border {{ $activeFilter === 'overdue' ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' }}">
        Overdue ({{ $overdueTotal }})
    </a>
</div>

{{-- Job Cards Table --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[850px]">
            <thead class="bg-gray-50/75 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="py-3 px-4">Vehicle</th>
                    <th class="py-3 px-4">Job Type</th>
                    <th class="py-3 px-4">Assigned Technician</th>
                    <th class="py-3 px-4">Stage</th>
                    <th class="py-3 px-4">Target Deadline</th>
                    <th class="py-3 px-4">Last Update</th>
                    <th class="py-3 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($jobs as $job)
                @php
                    $overdue = $job->estimated_completion && $job->estimated_completion->isPast();
                @endphp
                <tr class="hover:bg-gray-50/80 transition {{ $job->is_stale ? 'bg-rose-50/30' : ($overdue ? 'bg-amber-50/30' : '') }}">
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold bg-gray-100 px-2 py-0.5 rounded border border-gray-200 text-gray-900">
                                {{ $job->vehicle->plate_number ?? '—' }}
                            </span>
                            <span class="text-xs text-gray-500 font-medium">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</span>
                        </div>
                    </td>
                    <td class="py-3.5 px-4 font-bold text-gray-900">
                        {{ $job->jobType->name ?? 'Standard Service' }}
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="text-sm font-medium text-gray-700 flex items-center gap-1.5">
                            <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i>
                            {{ $job->staff->name ?? 'Unassigned' }}
                        </span>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold inline-block
                            {{ $job->current_stage === 'repairing' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                              ($job->current_stage === 'quality_check' ? 'bg-purple-50 text-purple-700 border border-purple-200' :
                              ($job->current_stage === 'waiting_parts' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                              'bg-amber-50 text-amber-700 border border-amber-200')) }}">
                            {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-xs font-semibold {{ $overdue ? 'text-rose-700 font-bold' : 'text-gray-600' }}">
                        @if($job->estimated_completion)
                            {{ $job->estimated_completion->format('d M, H:i') }}
                            @if($overdue)
                            <span class="text-rose-600 block text-[11px] font-bold">OVERDUE</span>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4">
                        @if($job->is_stale)
                        <span class="inline-flex items-center gap-1 text-xs bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded-full font-bold">
                            <i data-lucide="alarm-clock" class="w-3 h-3 text-rose-600"></i> {{ $job->hours_since_update }}h ago
                        </span>
                        @else
                        <span class="text-xs font-medium text-gray-500">{{ $job->hours_since_update }}h ago</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 text-right">
                        <a href="/coordinator/job-cards/{{ $job->id }}"
                            class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200 transition">
                            Open <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-10 text-center">
                        <x-empty-state icon="clipboard-check" title="No job cards match this filter" subtitle="All job cards are up-to-date or currently complete." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
