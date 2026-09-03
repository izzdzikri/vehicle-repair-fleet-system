@extends('layouts.app')
@section('page-title', 'Job Board')

@section('content')

@php
    $prefix = auth()->user()->role === 'admin' ? '/admin' : '/staff';
    $stageLabels = [
        'received' => 'Received', 'diagnosing' => 'Diagnosing', 'waiting_parts' => 'Waiting Parts',
        'repairing' => 'Repairing', 'quality_check' => 'Quality Check', 'completed' => 'Completed',
    ];
    $stageColors = [
        'received' => 'border-yellow-400', 'diagnosing' => 'border-blue-400', 'waiting_parts' => 'border-red-400',
        'repairing' => 'border-purple-400', 'quality_check' => 'border-cyan-400', 'completed' => 'border-green-400',
    ];
@endphp

<div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
    ℹ Drag a job card between columns to update its stage. Changes save automatically.
</div>

<div class="flex gap-4 overflow-x-auto pb-4" style="min-height: 70vh;">
    @foreach($stages as $stage)
    <div class="flex-shrink-0 w-72">
        <div class="flex items-center justify-between mb-2 px-1">
            <h3 class="text-sm font-semibold text-gray-600">{{ $stageLabels[$stage] }}</h3>
            <span id="count-{{ $stage }}" class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
                {{ $grouped[$stage]->count() }}
            </span>
        </div>
        <div class="kanban-column bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg p-2 space-y-2 min-h-[200px]"
            data-stage="{{ $stage }}">
            @foreach($grouped[$stage] as $job)
            @php
                $overdue = $job->estimated_completion && $job->estimated_completion->isPast() && $stage !== 'completed';
            @endphp
            <div class="kanban-card bg-white border-l-4 {{ $stageColors[$stage] }} rounded-lg shadow-sm p-3 cursor-grab active:cursor-grabbing hover:shadow-md transition"
                data-id="{{ $job->id }}">
                <div class="flex justify-between items-start mb-1">
                    <p class="font-semibold text-sm text-gray-800">{{ $job->vehicle->plate_number ?? '—' }}</p>
                    @if($job->is_stale && $stage !== 'completed')
                    <span class="text-xs" title="No update in {{ $job->hours_since_update }}h">⏰</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mb-1">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</p>
                <p class="text-xs text-gray-600 mb-2">{{ $job->jobType->name ?? '—' }}</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400">{{ $job->staff->name ?? 'Unassigned' }}</span>
                    @if($job->estimated_completion)
                    <span class="text-xs {{ $overdue ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                        {{ $job->estimated_completion->format('d M, H:i') }}
                    </span>
                    @endif
                </div>
                <a href="{{ $prefix }}/job-cards/{{ $job->id }}" class="block mt-2 text-xs text-blue-600 hover:underline">Open →</a>
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
                .then(res => {
                    if (!res.ok) throw new Error('Failed');
                    return res.json();
                })
                .catch(() => {
                    alert('Could not update stage. Reverting.');
                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                    syncCounts();
                });
            }
        });
    });
});
</script>
@endsection