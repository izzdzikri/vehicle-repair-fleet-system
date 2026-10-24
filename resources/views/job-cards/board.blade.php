@extends('layouts.app')
@section('page-title', 'Job Board')

@section('content')

@php
    $prefix = auth()->user()->role === 'admin' ? '/admin' : '/staff';
    $user   = auth()->user();
    $stageLabels = [
        'received' => 'Received', 'diagnosing' => 'Diagnosing', 'waiting_parts' => 'Waiting Parts',
        'repairing' => 'Repairing', 'quality_check' => 'Quality Check', 'completed' => 'Completed',
    ];
    $stageAccents = [
        'received' => 'border-t-4 border-amber-400',
        'diagnosing' => 'border-t-4 border-blue-400',
        'waiting_parts' => 'border-t-4 border-rose-400',
        'repairing' => 'border-t-4 border-indigo-500',
        'quality_check' => 'border-t-4 border-cyan-400',
        'completed' => 'border-t-4 border-emerald-500',
    ];
@endphp

<div class="flex justify-between items-center mb-6 flex-wrap gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Workshop Kanban Board</h2>
        <p class="text-sm text-gray-500 mt-1">Live visual pipeline of active repairs and bay transitions</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ $prefix }}/job-cards"
            class="bg-white border border-gray-200 text-gray-700 px-3.5 py-2.5 rounded-xl text-xs font-semibold hover:bg-gray-50 transition shadow-xs flex items-center gap-1.5">
            <i data-lucide="list" class="w-4 h-4 text-gray-500"></i> Table View
        </a>
    </div>
</div>

<div class="mb-5 p-4 bg-blue-50/70 border border-blue-200/80 rounded-xl flex items-center gap-3 text-sm text-blue-800">
    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 text-blue-700">
        <i data-lucide="info" class="w-4 h-4"></i>
    </div>
    <div class="leading-relaxed">
        <strong class="font-bold">Interactive Kanban:</strong> Drag any job card across stage columns to instantly update its status.
        @if($user->role === 'staff' && !$user->hasPermission('job_cards.manage_all'))
        <span class="block text-xs text-blue-700 mt-0.5">Cards marked with 🔒 belong to fellow mechanics and are view-only.</span>
        @endif
    </div>
</div>

<div class="flex gap-4 overflow-x-auto pb-6" style="min-height: 72vh;">
    @foreach($stages as $stage)
    <div class="flex-shrink-0 w-80 flex flex-col">
        <div class="flex items-center justify-between mb-2.5 px-2">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ str_replace('border-t-4 border-', 'bg-', $stageAccents[$stage]) }}"></span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">{{ $stageLabels[$stage] }}</h3>
            </div>
            <span id="count-{{ $stage }}" class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-200/80 text-gray-700">
                {{ $grouped[$stage]->count() }}
            </span>
        </div>
        <div class="kanban-column bg-gray-100/70 border border-gray-200/80 rounded-xl p-2.5 space-y-2.5 min-h-[300px] flex-1"
            data-stage="{{ $stage }}">
            @foreach($grouped[$stage] as $job)
            @php
                $overdue  = $job->estimated_completion && $job->estimated_completion->isPast() && $stage !== 'completed';
                $canEdit  = $job->canBeEditedBy($user);
            @endphp
            <div class="kanban-card {{ $canEdit ? '' : 'kanban-locked' }} bg-white {{ $stageAccents[$stage] }} rounded-xl border border-gray-200 shadow-2xs p-4 transition
                {{ $canEdit ? 'cursor-grab active:cursor-grabbing hover:shadow-md hover:border-gray-300' : 'opacity-75 cursor-not-allowed' }}"
                data-id="{{ $job->id }}">
                <div class="flex justify-between items-start mb-2">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                        {{ $job->vehicle->plate_number ?? '—' }}
                    </span>
                    <div class="flex items-center gap-1.5">
                        @if(!$canEdit)
                        <span class="text-xs text-gray-400" title="Assigned to {{ $job->staff->name ?? 'another mechanic' }} — view only">🔒</span>
                        @endif
                        @if($job->is_stale && $stage !== 'completed')
                        <span class="text-xs text-amber-500 font-bold" title="No update in {{ $job->hours_since_update }}h">⏰ Stale</span>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-gray-500 mb-1">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</p>
                <p class="text-sm font-bold text-gray-900 mb-3">{{ $job->jobType->name ?? 'General Repair' }}</p>
                
                <div class="pt-2.5 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-600 flex items-center gap-1">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i>
                        {{ $job->staff->name ?? 'Unassigned' }}
                    </span>
                    @if($job->estimated_completion)
                    <span class="font-semibold {{ $overdue ? 'text-rose-600' : 'text-gray-500' }}">
                        {{ $job->estimated_completion->format('d M, H:i') }}
                    </span>
                    @endif
                </div>

                <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-xs font-mono font-semibold text-gray-400">#{{ $job->id }}</span>
                    <a href="{{ $prefix }}/job-cards/{{ $job->id }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                        {{ $canEdit ? 'Manage Card &rarr;' : 'View Details &rarr;' }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const columns   = document.querySelectorAll('.kanban-column');
    const csrfToken = document.querySelector('meta[name=csrf-token]').content;
    const prefix    = '{{ $prefix }}';

    function syncCounts() {
        columns.forEach(col => {
            const badge = document.getElementById('count-' + col.dataset.stage);
            if (badge) badge.textContent = col.children.length;
        });
    }

    columns.forEach(col => {
        new Sortable(col, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-40',
            // Locked cards (assigned to someone else, not editable by the
            // current user) simply cannot be picked up at all.
            filter: '.kanban-locked',
            preventOnFilter: true,
            onEnd: function (evt) {
                const cardId    = evt.item.dataset.id;
                const newStage  = evt.to.dataset.stage;
                const oldStage  = evt.from.dataset.stage;

                syncCounts();

                if (newStage === oldStage) return;

                fetch(`${prefix}/job-cards/${cardId}/stage`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ current_stage: newStage }),
                })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || 'Failed');
                    return data;
                })
                .catch((err) => {
                    alert(err.message || 'Could not update stage. Reverting.');
                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                    syncCounts();
                });
            }
        });
    });
});
</script>
@endsection