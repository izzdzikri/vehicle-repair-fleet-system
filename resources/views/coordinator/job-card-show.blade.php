@extends('layouts.app')
@section('page-title', 'Job Card #' . $jobCard->id . ' — Progress Check')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <a href="/coordinator/job-cards"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Job Cards
    </a>

    {{-- Job Info (read-only) --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-start mb-5 flex-wrap gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-md">
                        Job Card #{{ $jobCard->id }}
                    </span>
                    <span class="font-mono text-xs font-bold bg-gray-100 text-gray-900 px-2 py-0.5 rounded border border-gray-200">
                        {{ $jobCard->vehicle->plate_number ?? '—' }}
                    </span>
                </div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">
                    {{ $jobCard->jobType->name ?? $jobCard->appointment->service_type ?? 'Standard Workshop Service' }}
                </h2>
                <p class="text-xs font-medium text-gray-500 mt-1">
                    {{ $jobCard->vehicle->brand ?? '' }} {{ $jobCard->vehicle->model ?? '' }} &bull;
                    Assigned Technician: <strong class="text-gray-800">{{ $jobCard->staff->name ?? 'Unassigned' }}</strong>
                </p>
            </div>
            @if($jobCard->is_stale)
            <span class="inline-flex items-center gap-1.5 text-xs bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1.5 rounded-full font-bold shrink-0">
                <i data-lucide="alarm-clock" class="w-3.5 h-3.5 text-rose-600"></i> No update in {{ $jobCard->hours_since_update }}h
            </span>
            @endif
        </div>

        {{-- Stage Tracker (read-only) --}}
        <div class="pt-4 border-t border-gray-100">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Service Pipeline Stage</h3>
            <div class="flex items-center gap-1.5 flex-wrap">
                @php
                    $stages  = ['received','diagnosing','waiting_parts','repairing','quality_check','completed'];
                    $current = array_search($jobCard->current_stage, $stages);
                @endphp
                @foreach($stages as $i => $stage)
                <div class="flex items-center">
                    <div class="px-3 py-1 rounded-full text-xs font-bold transition
                        {{ $i < $current  ? 'bg-emerald-600 text-white' :
                          ($i === $current ? 'bg-blue-600 text-white ring-2 ring-blue-200' :
                          'bg-gray-100 text-gray-500 border border-gray-200') }}">
                        @if($i < $current) ✓ @endif {{ ucfirst(str_replace('_', ' ', $stage)) }}
                    </div>
                    @if(!$loop->last)
                    <div class="w-3 h-0.5 mx-1 {{ $i < $current ? 'bg-emerald-500' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500 mt-3 pt-2">
                <span>Last system touch: <strong>{{ $jobCard->hours_since_update }}h ago</strong></span>
                @if($jobCard->estimated_completion)
                <span>
                    Est. Completion: <strong>{{ $jobCard->estimated_completion->format('d M Y, H:i') }}</strong>
                    @if($jobCard->estimated_completion->isPast() && $jobCard->current_stage !== 'completed')
                    <span class="text-rose-600 font-bold ml-1">(Overdue)</span>
                    @endif
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Diagnosis & symptoms (read-only) --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-3">Diagnostic Inspection</h3>
        <p class="text-sm text-gray-800 leading-relaxed font-medium">
            {{ $jobCard->diagnosis ?? 'No diagnostic assessment recorded yet.' }}
        </p>
        @if($jobCard->symptoms && count($jobCard->symptoms) > 0)
        <div class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-gray-100">
            @foreach($jobCard->symptoms as $s)
            <span class="text-xs bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md font-semibold border border-gray-200">
                {{ $s }}
            </span>
            @endforeach
        </div>
        @endif
        @if($jobCard->technician_notes)
        <div class="mt-3.5 p-3 bg-amber-50/60 border border-amber-200/80 rounded-lg text-xs text-amber-900 italic">
            <span class="font-bold not-italic block mb-0.5">Technician Field Notes:</span>
            "{{ $jobCard->technician_notes }}"
        </div>
        @endif
    </div>

    {{-- Parts & Labour (read-only) --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-3">Parts & Labour Accrued</h3>
        <div class="divide-y divide-gray-100">
            @forelse($jobCard->parts as $p)
            <div class="flex justify-between items-center py-2.5 text-sm">
                <div>
                    <span class="font-bold text-gray-900">{{ $p->sparePart->name ?? 'Spare Part' }}</span>
                    <span class="text-xs text-gray-500 font-mono ml-2">&times; {{ $p->quantity }}</span>
                </div>
                <span class="font-bold text-gray-900">RM {{ number_format($p->quantity * $p->unit_price, 2) }}</span>
            </div>
            @empty
            <p class="text-gray-400 text-sm py-2">No spare parts billed yet.</p>
            @endforelse

            @forelse($jobCard->labourCharges as $l)
            <div class="flex justify-between items-center py-2.5 text-sm">
                <div>
                    <span class="font-bold text-gray-900">{{ $l->description }}</span>
                    <span class="text-xs text-blue-600 font-semibold ml-2">(Labour)</span>
                </div>
                <span class="font-bold text-gray-900">RM {{ number_format($l->charge, 2) }}</span>
            </div>
            @empty
            @if($jobCard->parts->isEmpty())
            <p class="text-gray-400 text-sm py-2">No labour charges logged yet.</p>
            @endif
            @endforelse
        </div>
        <div class="flex justify-between items-center pt-4 font-bold text-sm border-t border-gray-200 mt-2">
            <span class="text-gray-700 uppercase tracking-wider text-xs">Accumulated Total</span>
            <span class="text-blue-700 text-xl font-extrabold">RM {{ number_format($jobCard->total_cost, 2) }}</span>
        </div>
    </div>

    {{-- Check-in log + new check-in form --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-gray-900">Coordinator Check-in Log</h3>
            <span class="text-xs font-semibold px-2 py-0.5 bg-gray-100 text-gray-600 rounded-md">
                {{ $jobCard->checkins->count() }} check-in(s)
            </span>
        </div>

        <form method="POST" action="/coordinator/job-cards/{{ $jobCard->id }}/checkin" class="mb-6 p-4 bg-gray-50/60 rounded-xl border border-gray-200/80">
            @csrf
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Add Coordinator Note / Follow-up</label>
            <textarea name="note" rows="2" maxlength="500"
                placeholder="e.g. Confirmed with mechanic — awaiting brake fluid delivery from vendor, ETA 2:30 PM."
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" required></textarea>
            <div class="mt-2.5 text-right">
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 shadow-xs transition">
                    Log Check-in Note
                </button>
            </div>
        </form>

        <div class="space-y-3">
            @forelse($jobCard->checkins as $c)
            <div class="border border-gray-200/80 rounded-xl p-3.5 text-sm bg-white">
                <div class="flex justify-between items-start gap-2">
                    <div>
                        <p class="font-bold text-gray-900 text-xs">{{ $c->coordinator->name ?? 'Coordinator' }}</p>
                        @if($c->note)
                        <p class="text-gray-700 text-sm mt-1 leading-relaxed">{{ $c->note }}</p>
                        @else
                        <p class="text-gray-400 text-xs mt-1 italic">Checked in on status — no extra note recorded.</p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 shrink-0 font-medium">{{ $c->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-4">No coordinator check-ins logged yet for this job card.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
