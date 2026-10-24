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

    <div>
        <a href="{{ $backHref }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Job Cards
        </a>
    </div>

    @if(!$canEdit)
    <div class="p-4 bg-gray-100 border border-gray-200 rounded-xl text-xs font-medium text-gray-700 flex items-center gap-2.5">
        <i data-lucide="lock" class="w-4 h-4 shrink-0 text-gray-500"></i>
        <span>This job card is assigned to <strong>{{ $jobCard->staff->name ?? 'another staff member' }}</strong>. You are in read-only mode.</span>
    </div>
    @endif

    {{-- Job Info --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <div class="flex justify-between items-start mb-6 pb-5 border-b border-gray-100 flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Job Card #{{ $jobCard->id }}</h2>
                    @if($jobCard->appointment && $jobCard->appointment->is_walkin)
                    <span class="text-xs font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-0.5 rounded-full">
                        Walk-in
                    </span>
                    @endif
                </div>
                <div class="mt-2 flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2.5 py-1 rounded border border-gray-200">
                        {{ $jobCard->vehicle->plate_number ?? '—' }}
                    </span>
                    @if($jobCard->vehicle)
                        @if($jobCard->vehicle->isFleet())
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                <i data-lucide="building-2" class="w-3 h-3 text-amber-600"></i>
                                Fleet: {{ $jobCard->vehicle->company->name ?? ($jobCard->vehicle->owner->company->name ?? 'Corporate') }}
                            </span>
                        @else
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                <i data-lucide="user" class="w-3 h-3 text-emerald-600"></i> Individual
                            </span>
                        @endif
                    @endif
                    <span class="text-sm font-semibold text-gray-800">
                        {{ $jobCard->vehicle->brand ?? '' }} {{ $jobCard->vehicle->model ?? '' }}
                    </span>
                    <span class="text-xs text-gray-400">&bull;</span>
                    <span class="text-xs font-medium text-gray-600">
                        Service: <strong>{{ $jobCard->jobType->name ?? $jobCard->appointment->service_type ?? 'General' }}</strong>
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Customer:
                    @if($jobCard->appointment && $jobCard->appointment->is_walkin)
                        <strong class="text-gray-900">{{ $jobCard->appointment->walkin_name ?? 'Walk-in' }}</strong>
                        ({{ $jobCard->appointment->walkin_contact ?? 'no contact' }})
                    @else
                        <strong class="text-gray-900">{{ $jobCard->appointment->user->name ?? '—' }}</strong>
                        @if($jobCard->vehicle && $jobCard->vehicle->isFleet() && $jobCard->vehicle->company)
                            <span class="text-gray-600">({{ $jobCard->vehicle->company->name }} PIC)</span>
                        @endif
                    @endif
                    &bull; Assigned Technician: <strong class="text-gray-900">{{ $jobCard->staff->name ?? 'Unassigned' }}</strong>
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Created</p>
                <p class="text-sm font-bold text-gray-900">{{ $jobCard->created_at->format('d M Y') }}</p>
                @if($jobCard->estimated_completion)
                <p class="text-xs font-semibold {{ $jobCard->estimated_completion->isPast() && $jobCard->current_stage !== 'completed' ? 'text-rose-600' : 'text-gray-500' }} mt-1">
                    Est: {{ $jobCard->estimated_completion->format('d M Y, H:i') }}
                    @if($jobCard->estimated_completion->isPast() && $jobCard->current_stage !== 'completed')
                    <span class="block text-[11px] font-bold uppercase text-rose-600">⚠ Overdue</span>
                    @endif
                </p>
                @endif
            </div>
        </div>

        {{-- Stage Tracker --}}
        <div class="mb-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Lifecycle Progress</h3>
            <div class="flex items-center gap-1.5 flex-wrap">
                @php
                    $stages  = ['received','diagnosing','waiting_parts','repairing','quality_check','completed'];
                    $current = array_search($jobCard->current_stage, $stages);
                @endphp
                @foreach($stages as $i => $stage)
                <div class="flex items-center">
                    <div class="px-3 py-1.5 rounded-lg text-xs font-semibold border
                        {{ $i < $current  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          ($i === $current ? 'bg-slate-900 text-white border-slate-900 shadow-xs' :
                          'bg-gray-50 text-gray-400 border-gray-200') }}">
                        @if($i < $current) &check; @endif
                        {{ ucfirst(str_replace('_', ' ', $stage)) }}
                    </div>
                    @if(!$loop->last)
                    <div class="w-3 h-0.5 mx-1 {{ $i < $current ? 'bg-emerald-500' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Update Stage --}}
        @if($canEdit)
        <form method="POST"
            action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/stage"
            class="flex gap-2 items-center pt-4 border-t border-gray-100 flex-wrap">
            @csrf @method('PATCH')
            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Update Stage:</label>
            <select name="current_stage"
                class="border border-gray-200 rounded-xl px-3 py-2 text-xs font-semibold bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
                @foreach($stages as $stage)
                <option value="{{ $stage }}" {{ $jobCard->current_stage === $stage ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $stage)) }}
                </option>
                @endforeach
            </select>
            <button type="submit"
                class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
                Update Stage
            </button>
        </form>
        @endif
    </div>

    {{-- --------------------------------------------------------- --}}
    {{-- Diagnosis & Symptom Checklist                             --}}
    {{-- --------------------------------------------------------- --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <h3 class="text-base font-bold text-gray-900 tracking-tight mb-1">Diagnosis & Observed Symptoms</h3>
        <p class="text-xs text-gray-500 mb-5">
            Technician inspection findings mapped against original driver complaints.
        </p>

        {{-- Customer initial complaint --}}
        @if($jobCard->appointment)
        <div class="mb-5 p-4 bg-amber-50/70 border border-amber-200/80 rounded-xl text-sm">
            <p class="text-xs font-bold text-amber-800 uppercase tracking-wider mb-1">Customer's Initial Complaint</p>
            <p class="font-semibold text-gray-900">{{ $jobCard->appointment->service_type }}</p>
            @if($jobCard->appointment->notes)
            <p class="text-gray-700 mt-1 text-xs">Notes: {{ $jobCard->appointment->notes }}</p>
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
    {{-- Inspection Photos                                         --}}
    {{-- --------------------------------------------------------- --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-1">Inspection Photos</h3>
        <p class="text-xs text-gray-400 mb-4">
            Photo documentation of the vehicle's condition — captured at whichever stage the job was in at upload time.
        </p>

        @if($canEdit)
        <form method="POST"
            action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/photos"
            enctype="multipart/form-data"
            class="flex gap-3 items-end flex-wrap mb-5">
            @csrf
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                <input type="file" name="photo" accept="image/jpeg,image/png"
                    class="w-full text-sm text-gray-500 border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                <p class="text-xs text-gray-400 mt-1">JPG or PNG, max 5MB</p>
            </div>
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Caption <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input type="text" name="caption" maxlength="150"
                    placeholder="e.g. Worn brake pad — front left"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                Upload Photo
            </button>
        </form>
        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif
        @endif

        @php $photos = $jobCard->inspection_photos ?? []; @endphp

        @if(count($photos))
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($photos as $photo)
            <div class="border rounded-lg overflow-hidden relative">
                <a href="{{ asset('storage/' . $photo['path']) }}" target="_blank">
                    <img src="{{ asset('storage/' . $photo['path']) }}"
                        alt="{{ $photo['caption'] ?? 'Inspection photo' }}"
                        class="w-full h-32 object-cover">
                </a>
                <div class="p-2">
                    @if(!empty($photo['caption']))
                    <p class="text-xs text-gray-700 truncate">{{ $photo['caption'] }}</p>
                    @endif
                    <p class="text-xs text-gray-400">
                        {{ ucfirst(str_replace('_',' ', $photo['stage'] ?? '—')) }}
                        &bull; {{ \Carbon\Carbon::parse($photo['uploaded_at'])->format('d M Y, H:i') }}
                    </p>
                </div>
                @if($canEdit)
                <form method="POST"
                    action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/photos/{{ $photo['id'] }}"
                    class="absolute top-1.5 right-1.5"
                    onsubmit="return confirmSubmit(event, {title:'Delete photo?', message:'This will permanently remove this inspection photo. This cannot be undone.', confirmLabel:'Delete'})">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="bg-white/90 hover:bg-red-50 text-red-500 rounded-full p-1.5 shadow"
                        title="Delete photo">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-400 text-sm">No inspection photos uploaded yet.</p>
        @endif
    </div>

    {{-- --------------------------------------------------------- --}}
    {{-- Add Parts                                                 --}}
    {{-- --------------------------------------------------------- --}}
    <div class="bg-white rounded-lg shadow p-6" x-data="jobPartsScanner()">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
            <h3 class="text-lg font-semibold text-gray-700">Spare Parts Used</h3>
            @if($canEdit)
            <button type="button" @click="openScanner()"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-500 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-100 transition shadow-sm">
                <i data-lucide="scan-barcode" class="w-4 h-4 text-blue-600"></i>
                <span>Scan Part Barcode / QR</span>
            </button>
            @endif
        </div>

        @if($canEdit)
        {{-- Scanned success feedback --}}
        <div x-show="scannedPart" x-transition
            class="mb-3 p-3 bg-emerald-50 border border-emerald-300 rounded-lg text-xs text-emerald-800 flex items-center justify-between"
            style="display:none">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <span>Scanned: <strong x-text="scannedPart?.name"></strong> (<span x-text="scannedPart?.part_number"></span>) &bull; Available Stock: <strong x-text="scannedPart?.stock"></strong> &bull; RM <strong x-text="scannedPart?.unit_price"></strong></span>
            </div>
            <button type="button" @click="scannedPart = null" class="text-emerald-700 hover:text-emerald-900 font-bold">&times;</button>
        </div>

        <form method="POST"
            action="/{{ $prefix }}/job-cards/{{ $jobCard->id }}/parts"
            class="flex gap-3 items-end flex-wrap mb-4">
            @csrf
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Part</label>
                <select name="spare_part_id" x-model="selectedPartId" id="job-card-part-select"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                    <option value="">Select part or scan barcode above</option>
                    @foreach($spareParts as $part)
                    <option value="{{ $part->id }}">
                        {{ $part->name }}
                        @if($part->brand) ({{ $part->brand }}) @endif
                        [{{ $part->part_number }}]
                        — Stock: {{ $part->stock }} — RM {{ number_format($part->unit_price, 2) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="w-28">
                <label class="block text-sm font-medium text-gray-700 mb-1">Qty</label>
                <input type="number" name="quantity" min="1" value="1" id="job-card-part-qty"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>
            <button type="submit"
                class="bg-green-600 text-white px-5 py-2 rounded text-sm font-medium hover:bg-green-700 shadow-sm transition">
                Add Part
            </button>
        </form>

        {{-- Barcode Scanner Modal --}}
        <div x-show="showScanner" x-transition.opacity
            class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 no-print"
            style="display:none">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden" @click.away="closeScanner()">
                <div class="bg-gray-900 text-white px-4 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <i data-lucide="scan-barcode" class="w-5 h-5 text-blue-400"></i>
                        <span class="font-semibold text-sm">Scan Part Barcode / QR</span>
                    </div>
                    <button type="button" @click="closeScanner()" class="text-gray-400 hover:text-white">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <div id="jobcard-scanner-viewport" class="w-full bg-black rounded-lg overflow-hidden min-h-[220px] flex items-center justify-center text-white text-xs">
                        Camera preview loading...
                    </div>
                    <p id="jobcard-scanner-status" class="text-xs text-center text-gray-500">
                        Aim camera at the part label or barcode.
                    </p>

                    <div class="pt-2 border-t">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Or Handheld USB Laser Scanner / Direct Input:
                        </label>
                        <div class="flex gap-2">
                            <input type="text" x-model="manualCode" @keydown.enter.prevent="lookupManual()"
                                id="jobcard-scanner-manual-input"
                                placeholder="Scan or type part number (e.g. CO-5W30-4L)..."
                                class="flex-1 border rounded px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                            <button type="button" @click="lookupManual()"
                                class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-semibold hover:bg-blue-700">
                                Select
                            </button>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-2.5 border-t text-right">
                    <button type="button" @click="closeScanner()"
                        class="px-3 py-1.5 border rounded text-xs text-gray-600 hover:bg-gray-100">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
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
                        action="/{{ $prefix }}/job-cards/labour/{{ $labour->id }}"
                        onsubmit="return confirmSubmit(event, {title:'Remove labour charge?', message:'This will remove &quot;{{ addslashes($labour->description) }}&quot; (RM {{ number_format($labour->charge, 2) }}) from this job card and recalculate the total cost.', confirmLabel:'Remove'})">
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

<script>
function jobPartsScanner() {
    return {
        showScanner: false,
        selectedPartId: '',
        scannedPart: null,
        manualCode: '',
        parts: @json($spareParts),
        scannerInstance: null,

        openScanner() {
            this.showScanner = true;
            this.manualCode = '';
            this.$nextTick(() => {
                this.startCamera();
                const input = document.getElementById('jobcard-scanner-manual-input');
                if (input) input.focus();
            });
        },

        closeScanner() {
            this.showScanner = false;
            this.stopCamera();
        },

        startCamera() {
            const container = document.getElementById('jobcard-scanner-viewport');
            const status = document.getElementById('jobcard-scanner-status');
            if (!container) return;

            this.stopCamera();
            container.innerHTML = '';

            if (typeof Html5Qrcode === 'undefined') {
                if (status) status.textContent = 'Scanner library loading...';
                return;
            }

            this.scannerInstance = new Html5Qrcode('jobcard-scanner-viewport');
            Html5Qrcode.getCameras().then(cameras => {
                if (!cameras || cameras.length === 0) {
                    if (status) status.textContent = 'No camera detected. You can type/scan with laser scanner below.';
                    return;
                }
                const cameraId = cameras[cameras.length - 1].id;
                if (status) status.textContent = 'Camera active — point at barcode.';

                this.scannerInstance.start(
                    cameraId,
                    { fps: 12, qrbox: { width: 250, height: 160 } },
                    (decodedText) => {
                        this.handleDecoded(decodedText.trim());
                    },
                    () => {}
                ).catch(err => {
                    if (status) status.textContent = 'Camera unavailable. Use manual/laser input below.';
                });
            }).catch(err => {
                if (status) status.textContent = 'Camera permission required.';
            });
        },

        stopCamera() {
            if (this.scannerInstance) {
                this.scannerInstance.stop().catch(() => {}).finally(() => {
                    this.scannerInstance = null;
                });
            }
        },

        lookupManual() {
            if (!this.manualCode.trim()) return;
            this.handleDecoded(this.manualCode.trim());
        },

        handleDecoded(code) {
            const cleanCode = code.toUpperCase().trim();
            // Match by part_number (case-insensitive) or by ID
            const found = this.parts.find(p =>
                (p.part_number && p.part_number.toUpperCase().trim() === cleanCode) ||
                String(p.id) === cleanCode
            );

            if (found) {
                this.playBeep();
                this.selectedPartId = String(found.id);
                this.scannedPart = found;
                this.closeScanner();

                // Focus quantity input
                this.$nextTick(() => {
                    const qty = document.getElementById('job-card-part-qty');
                    if (qty) {
                        qty.value = 1;
                        qty.focus();
                        qty.select();
                    }
                });
            } else {
                const status = document.getElementById('jobcard-scanner-status');
                if (status) {
                    status.innerHTML = `<span class="text-red-500 font-semibold">Part "${code}" not found in stock.</span>`;
                }
            }
        },

        playBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.12);
            } catch(e) {}
        }
    };
}
</script>
@endsection