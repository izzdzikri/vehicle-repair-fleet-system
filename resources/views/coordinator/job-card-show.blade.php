@extends('layouts.app')
@section('page-title', 'Job Card #' . $jobCard->id . ' — Progress Check')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <a href="/coordinator/job-cards" class="text-sm text-blue-600 hover:underline">← Back to Job Cards</a>

    @if(session('success'))
    <div class="p-3 bg-green-100 text-green-700 rounded text-sm">{{ session('success') }}</div>
    @endif

    {{-- Job Info (read-only) --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Job Card #{{ $jobCard->id }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $jobCard->vehicle->plate_number ?? '—' }}
                    &bull; {{ $jobCard->vehicle->brand ?? '' }} {{ $jobCard->vehicle->model ?? '' }}
                    &bull; {{ $jobCard->jobType->name ?? $jobCard->appointment->service_type ?? '—' }}
                </p>
                <p class="text-sm text-gray-500">
                    Assigned to: {{ $jobCard->staff->name ?? '—' }}
                </p>
            </div>
            @if($jobCard->is_stale)
            <span class="inline-flex items-center gap-1 text-xs bg-red-100 text-red-600 px-3 py-1.5 rounded-full font-medium animate-pulse shrink-0">
                <i data-lucide="alarm-clock" class="w-3.5 h-3.5"></i> No update in {{ $jobCard->hours_since_update }}h
            </span>
            @endif
        </div>

        {{-- Stage Tracker (read-only) --}}
        <div class="mb-2">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">Progress</h3>
            <div class="flex items-center gap-1 flex-wrap">
                @php
                    $stages  = ['received','diagnosing','waiting_parts','repairing','quality_check','completed'];
                    $current = array_search($jobCard->current_stage, $stages);
                @endphp
                @foreach($stages as $i => $stage)
                <div class="flex items-center">
                    <div class="px-3 py-1 rounded-full text-xs font-medium
                        {{ $i < $current  ? 'bg-green-500 text-white' :
                          ($i === $current ? 'bg-blue-600 text-white' :
                          'bg-gray-200 text-gray-500') }}">
                        {{ ucfirst(str_replace('_', ' ', $stage)) }}
                    </div>
                    @if(!$loop->last)
                    <div class="w-4 h-0.5 {{ $i < $current ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-2">
                Last touched by the workshop system {{ $jobCard->hours_since_update }}h ago.
                @if($jobCard->estimated_completion)
                Estimated completion: {{ $jobCard->estimated_completion->format('d M Y, H:i') }}
                @if($jobCard->estimated_completion->isPast() && $jobCard->current_stage !== 'completed')
                <span class="text-red-500 font-medium">— overdue</span>
                @endif
                @endif
            </p>
        </div>
    </div>

    {{-- Diagnosis & symptoms (read-only) --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">Diagnosis</h3>
        <p class="text-sm text-gray-700">{{ $jobCard->diagnosis ?? 'Not yet recorded.' }}</p>
        @if($jobCard->symptoms && count($jobCard->symptoms) > 0)
        <div class="flex flex-wrap gap-1 mt-3">
            @foreach($jobCard->symptoms as $s)
            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $s }}</span>
            @endforeach
        </div>
        @endif
        @if($jobCard->technician_notes)
        <p class="text-sm text-gray-500 mt-3 italic">"{{ $jobCard->technician_notes }}"</p>
        @endif
    </div>

    {{-- Parts & Labour (read-only) --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">Parts & Labour So Far</h3>
        @forelse($jobCard->parts as $p)
        <div class="flex justify-between items-center border-b py-2 text-sm">
            <span>{{ $p->sparePart->name ?? '—' }} &times; {{ $p->quantity }}</span>
            <span class="text-gray-600">RM {{ number_format($p->quantity * $p->unit_price, 2) }}</span>
        </div>
        @empty
        <p class="text-gray-400 text-sm">No parts logged yet.</p>
        @endforelse
        @forelse($jobCard->labourCharges as $l)
        <div class="flex justify-between items-center border-b py-2 text-sm">
            <span>{{ $l->description }} <span class="text-xs text-gray-400">(Labour)</span></span>
            <span class="text-gray-600">RM {{ number_format($l->charge, 2) }}</span>
        </div>
        @empty
        @if($jobCard->parts->isEmpty())
        <p class="text-gray-400 text-sm mt-2">No labour logged yet.</p>
        @endif
        @endforelse
        <div class="flex justify-between items-center pt-3 font-semibold text-sm border-t mt-2">
            <span>Total So Far</span>
            <span class="text-blue-600 text-lg">RM {{ number_format($jobCard->total_cost, 2) }}</span>
        </div>
    </div>

    {{-- Check-in log + new check-in form --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Progress Check-ins</h3>

        <form method="POST" action="/coordinator/job-cards/{{ $jobCard->id }}/checkin" class="mb-5">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1">Log a check-in</label>
            <textarea name="note" rows="2" maxlength="500"
                placeholder="e.g. Confirmed with technician — waiting on brake pad delivery, ETA 2pm."
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            <button type="submit"
                class="mt-2 bg-blue-600 text-white px-5 py-2 rounded text-sm hover:bg-blue-700">
                Log Check-in
            </button>
        </form>

        @forelse($jobCard->checkins as $c)
        <div class="border-b py-3 text-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium text-gray-800">{{ $c->coordinator->name ?? '—' }}</p>
                    @if($c->note)
                    <p class="text-gray-600 mt-0.5">{{ $c->note }}</p>
                    @else
                    <p class="text-gray-400 mt-0.5 italic">Checked in — no additional note.</p>
                    @endif
                </div>
                <span class="text-xs text-gray-400 shrink-0">{{ $c->created_at->diffForHumans() }}</span>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm">No check-ins logged yet for this job.</p>
        @endforelse
    </div>

</div>
@endsection