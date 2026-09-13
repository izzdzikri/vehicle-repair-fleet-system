@extends('layouts.app')
@section('page-title', 'Job Card #' . $jobCard->id)

@section('content')
@php
    $prefix = match(auth()->user()->role) {
        'admin'       => 'admin',
        'coordinator' => 'coordinator',
        default       => 'staff',
    };
    $backHref = match(auth()->user()->role) {
        'admin'       => '/admin/job-cards',
        'coordinator' => '/coordinator/job-cards',
        default       => '/staff/job-cards/board',
    };
@endphp
<div class="max-w-4xl mx-auto space-y-6">

    <a href="{{ $backHref }}"
        class="text-sm text-blue-600 hover:underline">← Back</a>

    @if(!$canEdit)
    <div class="p-3 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-600 flex items-center gap-2">
        <i data-lucide="lock" class="w-4 h-4 shrink-0 text-gray-400"></i>
        This job card is assigned to <strong>{{ $jobCard->staff->name ?? 'another staff member' }}</strong>.
        You can view the full details below, but only the assigned mechanic (or a staff member with full job card access) can make changes.
    </div>
    @endif

    {{-- Job Info --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-gray-800">Job Card #{{ $jobCard->id }}</h2>
                    @if($jobCard->appointment && $jobCard->appointment->is_walkin)
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Walk-in</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $jobCard->appointment->service_type ?? '—' }}
                    &bull; {{ $jobCard->vehicle->plate_number ?? '—' }}
                    &bull; {{ $jobCard->vehicle->brand ?? '' }} {{ $jobCard->vehicle->model ?? '' }}
                </p>
                <p class="text-sm text-gray-500">
                    Customer:
                    @if($jobCard->appointment && $jobCard->appointment->is_walkin)
                        {{ $jobCard->appointment->walkin_name ?? 'Walk-in' }}
                        ({{ $jobCard->appointment->walkin_contact ?? 'no contact' }})
                    @else
                        {{ $jobCard->appointment->user->name ?? '—' }}
                    @endif
                    &bull; Assigned to: {{ $jobCard->staff->name ?? '—' }}
                    @if($jobCard->jobType)
                    &bull; {{ $jobCard->jobType->name }}
                    @endif
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Created</p>
                <p class="text-sm font-medium">{{ $jobCard->created_at->format('d M Y') }}</p>
                @if($jobCard->estimated_completion)
                <p class="text-xs {{ $jobCard->estimated_completion->isPast() && $jobCard->current_stage !== 'completed' ? 'text-red-500 font-semibold' : 'text-gray-400' }} mt-1">
                    Est: {{ $jobCard->estimated_completion->format('d M Y H:i') }}
                    @if($jobCard->estimated_completion->isPast() && $jobCard->current_stage !== 'completed')
                    ⚠ Overdue
                    @endif
                </p>
                @endif
            </div>
        </div>

        {{-- Stage Tracker --}}
        <div class="mb-6">
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
        </div>

        {{-- Update Stage --}}
        @if($canEdit)
        <form method="POST"
            action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/stage"
            class="flex gap-3 items-center">
            @csrf @method('PATCH')
            <select name="current_stage"
                class="border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach($stages as $stage)
                <option value="{{ $stage }}" {{ $jobCard->current_stage === $stage ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $stage)) }}
                </option>
                @endforeach
            </select>
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                Update Stage
            </button>
        </form>
        @endif
    </div>

    {{-- --------------------------------------------------------- --}}
    {{-- Diagnosis & Symptom Checklist                             --}}
    {{-- --------------------------------------------------------- --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-1">Diagnosis & Symptoms</h3>
        <p class="text-xs text-gray-400 mb-4">
            Technician fills in observed symptoms. Compare against customer's initial complaint above.
        </p>

        {{-- Customer initial complaint --}}
        @if($jobCard->appointment)
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm">
            <p class="text-xs font-semibold text-yellow-700 uppercase mb-1">Customer's Initial Complaint</p>
            <p class="text-gray-700">{{ $jobCard->appointment->service_type }}</p>
            @if($jobCard->appointment->notes)
            <p class="text-gray-500 mt-1 text-xs">Notes: {{ $jobCard->appointment->notes }}</p>
            @endif
        </div>
        @endif

        @if($canEdit)
        <form method="POST"
            action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/symptoms">
            @csrf

            {{-- Initial diagnosis text --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Technician Diagnosis</label>
                <input type="text" name="diagnosis" value="{{ old('diagnosis', $jobCard->diagnosis) }}"
                    placeholder="e.g. Worn brake pads, low coolant level"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Symptom checklist --}}
            <div class="mb-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Observed Symptoms (tick all that apply)</p>
                @php
                    $allSymptoms = [
                        'Engine' => [
                            'Engine warning light on',
                            'Engine stalling or misfiring',
                            'Unusual engine noise',
                            'Excessive exhaust smoke',
                            'Oil leak',
                            'Overheating',
                        ],
                        'Brakes' => [
                            'Squeaking / grinding brakes',
                            'Brake pedal soft or spongy',
                            'Vibration when braking',
                            'Vehicle pulls to one side when braking',
                        ],
                        'Transmission' => [
                            'Gear slipping',
                            'Delayed engagement',
                            'Transmission fluid leak',
                            'Unusual transmission noise',
                        ],
                        'Electrical' => [
                            'Battery warning light',
                            'Lights flickering',
                            'Starter motor issue',
                            'Electrical accessories not working',
                        ],
                        'Suspension & Steering' => [
                            'Excessive vibration while driving',
                            'Pulling to one side',
                            'Unusual tyre wear',
                            'Steering loose or stiff',
                        ],
                        'Cooling & Fluids' => [
                            'Coolant leak',
                            'Low fluid levels',
                            'Radiator issue',
                        ],
                    ];
                    $savedSymptoms = $jobCard->symptoms ?? [];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($allSymptoms as $category => $items)
                    <div class="border rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $category }}</p>
                        @foreach($items as $symptom)
                        <label class="flex items-center gap-2 text-sm text-gray-700 py-0.5 hover:text-gray-900 cursor-pointer">
                            <input type="checkbox" name="symptoms[]" value="{{ $symptom }}"
                                {{ in_array($symptom, $savedSymptoms) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $symptom }}
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Technician notes --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Technician Notes</label>
                <textarea name="technician_notes" rows="3"
                    placeholder="Additional observations, parts needed, recommendations..."
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('technician_notes', $jobCard->technician_notes) }}</textarea>
            </div>

            {{-- Tally indicator --}}
            @if($jobCard->symptoms && count($jobCard->symptoms) > 0)
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm">
                <p class="text-xs font-semibold text-blue-700 uppercase mb-1">
                    Recorded Symptoms ({{ count($jobCard->symptoms) }})
                </p>
                <div class="flex flex-wrap gap-1 mt-1">
                    @foreach($jobCard->symptoms as $s)
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ $s }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            <button type="submit"
                class="bg-blue-600 text-white px-5 py-2 rounded text-sm hover:bg-blue-700 font-medium">
                Save Diagnosis & Symptoms
            </button>
        </form>
        @else
        {{-- Read-only view for non-owners --}}
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Technician Diagnosis</p>
            <p class="text-sm text-gray-700">{{ $jobCard->diagnosis ?? 'Not yet recorded.' }}</p>
        </div>
        @if($jobCard->symptoms && count($jobCard->symptoms) > 0)
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Recorded Symptoms ({{ count($jobCard->symptoms) }})</p>
            <div class="flex flex-wrap gap-1">
                @foreach($jobCard->symptoms as $s)
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $s }}</span>
                @endforeach
            </div>
        </div>
        @endif
        @if($jobCard->technician_notes)
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Technician Notes</p>
            <p class="text-sm text-gray-600 italic">"{{ $jobCard->technician_notes }}"</p>
        </div>
        @endif
        @endif
    </div>

    {{-- --------------------------------------------------------- --}}
    {{-- Add Parts                                                 --}}
    {{-- --------------------------------------------------------- --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Spare Parts Used</h3>

        @if($canEdit)
        <form method="POST"
            action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/parts"
            class="flex gap-3 items-end flex-wrap mb-4">
            @csrf
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Part</label>
                <select name="spare_part_id"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                    <option value="">Select part</option>
                    @foreach($spareParts as $part)
                    <option value="{{ $part->id }}">
                        {{ $part->name }}
                        @if($part->brand) ({{ $part->brand }}) @endif
                        — Stock: {{ $part->stock }} — RM {{ number_format($part->unit_price, 2) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="w-28">
                <label class="block text-sm font-medium text-gray-700 mb-1">Qty</label>
                <input type="number" name="quantity" min="1" value="1"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>
            <button type="submit"
                class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                Add Part
            </button>
        </form>
        @endif

        {{-- Parts list --}}
        @forelse($jobCard->parts as $p)
        <div class="flex justify-between items-center border-b py-2 text-sm">
            <div>
                <span class="font-medium">{{ $p->sparePart->name ?? '—' }}</span>
                <span class="text-gray-400 ml-2">× {{ $p->quantity }}</span>
                @if($p->sparePart->brand)
                <span class="text-xs text-gray-400 ml-1">({{ $p->sparePart->brand }})</span>
                @endif
            </div>
            <div class="text-right">
                <span class="text-gray-600">RM {{ number_format($p->quantity * $p->unit_price, 2) }}</span>
                <p class="text-xs text-gray-400">@ RM {{ number_format($p->unit_price, 2) }} each</p>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm mt-2">No parts added yet.</p>
        @endforelse

        @if($jobCard->parts->count())
        <div class="flex justify-between items-center pt-3 font-semibold text-sm border-t mt-2">
            <span>Total Parts Cost</span>
            <span class="text-blue-600 text-lg">RM {{ number_format($jobCard->total_cost, 2) }}</span>
        </div>
        @endif

    </div>

    {{-- Labour Charges --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Labour Charges</h3>

        @if($canEdit)
        <form method="POST"
            action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/labour"
            class="flex gap-3 items-end flex-wrap mb-4"
            x-data="{
                selected: '',
                charge: '',
                jobTypes: {{ $jobTypes->map(fn($j) => ['name' => $j->name, 'price' => $j->base_price])->values()->toJson() }},
                onSelect(val) {
                    this.selected = val;
                    const found = this.jobTypes.find(j => j.name === val);
                    if (found && found.price > 0) {
                        this.charge = found.price;
                    }
                }
            }">
            @csrf
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Service / Labour Description</label>
                <select name="description"
                    x-model="selected"
                    @change="onSelect($event.target.value)"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                    <option value="">Select job type</option>
                    @foreach($jobTypes->groupBy('category') as $category => $types)
                    <optgroup label="{{ $category }}">
                        @foreach($types as $jt)
                        <option value="{{ $jt->name }}">
                            {{ $jt->name }}
                            @if($jt->base_price > 0)
                                — RM {{ number_format($jt->base_price, 2) }}
                            @endif
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-sm font-medium text-gray-700 mb-1">Charge (RM)</label>
                <input type="number" name="charge" min="1" step="0.50"
                    placeholder="e.g. 50"
                    x-model="charge"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                <p class="text-xs text-gray-400 mt-0.5">Auto-filled from base price</p>
            </div>

            {{-- Remark Field (between charge input and submit button) --}}
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Remark <span class="text-gray-400 font-normal">(optional — e.g. "Dashboard removal required for this model")</span>
                </label>
                <input type="text" name="remark"
                    placeholder="e.g. Additional disassembly needed based on vehicle model"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit"
                class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700 mt-2">
                Add Labour
            </button>
        </form>
        @endif

        {{-- Labour list (updated to show remark) --}}
        @forelse($jobCard->labourCharges as $labour)
        <div class="border-b py-2 text-sm">
            <div class="flex justify-between items-start">
                <div class="flex items-start gap-2 flex-1">
                    <i data-lucide="wrench" class="w-4 h-4 text-purple-400 mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-medium text-gray-800">{{ $labour->description }}</p>
                        @if($labour->remark)
                        <p class="text-xs text-gray-400 mt-0.5 italic">{{ $labour->remark }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-4 shrink-0 ml-4">
                    <span class="font-medium text-gray-700">RM {{ number_format($labour->charge, 2) }}</span>
                    @if($canEdit)
                    <form method="POST"
                        action="/{{ $prefix }}/job-cards/labour/{{ $labour->id }}">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-400 hover:text-red-600">Remove</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm">No labour charges added yet.</p>
        @endforelse

        {{-- Cost summary (updated with descriptive labels) --}}
        @if($jobCard->labourCharges->count() || $jobCard->parts->count())
        @php
            $partsCost  = $jobCard->parts->sum(fn($p) => $p->quantity * $p->unit_price);
            $labourCost = $jobCard->labourCharges->sum('charge');
        @endphp
        <div class="mt-4 pt-3 border-t space-y-1 text-sm">
            <div class="flex justify-between text-gray-500">
                <span>Parts — Fixed Price</span>
                <span>RM {{ number_format($partsCost, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
                <div>
                    <span>Service Charge — Varies by Vehicle</span>
                    <p class="text-xs text-gray-400">Based on vehicle model & complexity</p>
                </div>
                <span>RM {{ number_format($labourCost, 2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-blue-700 text-base pt-2 border-t mt-1">
                <span>Total Job Cost</span>
                <span>RM {{ number_format($partsCost + $labourCost, 2) }}</span>
            </div>
        </div>
        @endif
    </div>

    {{-- --------------------------------------------------------- --}}
    {{-- Coordinator Check-in Log — coordinator-only, on top of the  --}}
    {{-- shared mechanic edit surface above.                        --}}
    {{-- --------------------------------------------------------- --}}
    @if(auth()->user()->role === 'coordinator')
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
    @endif

</div>
@endsection