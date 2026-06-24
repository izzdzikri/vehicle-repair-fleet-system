@extends('layouts.app')
@section('page-title', 'Create Job Card')

@section('content')
<div class="max-w-2xl mx-auto">
    <a href="/admin/appointments" class="text-sm text-blue-600 hover:underline">← Back to Appointments</a>

    <div class="bg-white rounded-lg shadow p-6 mt-4">
        <h2 class="text-lg font-semibold text-gray-700 mb-6">Create New Job Card</h2>

        @if($appointments->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4 text-sm text-yellow-700">
            No confirmed appointments available.
            <a href="/admin/appointments" class="underline font-medium">Go confirm an appointment first →</a>
        </div>
        @else

        <form method="POST" action="/admin/job-cards" class="space-y-5"
            x-data="{
                selectedJobType: '',
                allStaff: {{ $staff->map(fn($s) => [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'active_jobs' => $s->jobCards()->where('current_stage','!=','completed')->count(),
                    'specialties' => array_map('intval', $s->specialties ?? []),
                ])->values()->toJson() }},

                get filteredStaff() {
                    if (!this.selectedJobType) return this.allStaff;
                    const jid = parseInt(this.selectedJobType);
                    const matched = this.allStaff.filter(s =>
                        s.specialties.length === 0 || s.specialties.includes(jid)
                    );
                    return matched.length > 0 ? matched : this.allStaff;
                },

                get hasSpecialtyMatch() {
                    if (!this.selectedJobType) return true;
                    const jid = parseInt(this.selectedJobType);
                    const matched = this.allStaff.filter(s =>
                        s.specialties.length > 0 && s.specialties.includes(jid)
                    );
                    return matched.length > 0;
                },

                get estimatedTime() {
                    if (!this.selectedJobType) return '';
                    const jt = this.allJobTypes.find(j => j.id === parseInt(this.selectedJobType));
                    if (!jt) return '';
                    const hrs  = Math.floor(jt.minutes / 60);
                    const mins = jt.minutes % 60;
                    return hrs > 0
                        ? (mins > 0 ? hrs+'h '+mins+'m' : hrs+'h')
                        : mins+'m';
                },

                get basePrice() {
                    if (!this.selectedJobType) return '';
                    const jt = this.allJobTypes.find(j => j.id === parseInt(this.selectedJobType));
                    return jt ? 'RM '+parseFloat(jt.price).toFixed(2) : '';
                },

                allJobTypes: {{ $jobTypes->map(fn($j) => [
                    'id'      => $j->id,
                    'name'    => $j->name,
                    'minutes' => $j->estimated_minutes,
                    'price'   => $j->base_price,
                ])->values()->toJson() }}
            }">
            @csrf

            {{-- Appointment --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Appointment <span class="text-red-500">*</span>
                </label>
                <select name="appointment_id"
                    class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>
                    <option value="">Select confirmed appointment</option>
                    @foreach($appointments as $apt)
                    <option value="{{ $apt->id }}"
                        {{ request('appointment_id') == $apt->id ? 'selected' : '' }}>
                        #{{ $apt->id }}
                        — {{ $apt->vehicle->plate_number ?? '?' }}
                        | {{ $apt->service_type }}
                        | {{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }} {{ $apt->time }}
                        | {{ $apt->is_walkin ? 'Walk-in: '.$apt->walkin_name : ($apt->user->name ?? '?') }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Job Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                <select name="job_type_id"
                    x-model="selectedJobType"
                    class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Select job type (optional)</option>
                    @foreach($jobTypes->groupBy('category') as $cat => $types)
                    <optgroup label="{{ $cat }}">
                        @foreach($types as $jt)
                        <option value="{{ $jt->id }}">
                            {{ $jt->name }}
                            @php
                                $mins = $jt->estimated_minutes;
                                $hrs  = floor($mins / 60);
                                $rem  = $mins % 60;
                                $time = $hrs > 0 ? $hrs.'h '.($rem > 0 ? $rem.'m' : '') : $rem.'m';
                            @endphp
                            — ~{{ $time }} — RM {{ number_format($jt->base_price, 2) }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>

                {{-- Job type info pill --}}
                <div x-show="selectedJobType" class="mt-2 flex gap-2 flex-wrap">
                    <span class="text-xs bg-blue-50 border border-blue-200 text-blue-700 px-2 py-1 rounded-full">
                        ⏱ Est: <span x-text="estimatedTime"></span>
                    </span>
                    <span class="text-xs bg-green-50 border border-green-200 text-green-700 px-2 py-1 rounded-full">
                        Base labour: <span x-text="basePrice"></span>
                    </span>
                </div>
            </div>

            {{-- Assign Staff --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Assign to Staff <span class="text-red-500">*</span>
                </label>

                @if($staff->isEmpty())
                <div class="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-600">
                    No staff accounts found.
                    <a href="/admin/users" class="underline">Add a staff user first →</a>
                </div>
                @else

                <select name="staff_id"
                    class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>
                    <option value="">Select staff member</option>
                    <template x-for="s in filteredStaff" :key="s.id">
                        <option :value="s.id"
                            x-text="s.name + ' (' + s.active_jobs + ' active job' + (s.active_jobs !== 1 ? 's' : '') + ')'">
                        </option>
                    </template>
                </select>

                {{-- Staff filter status --}}
                <div class="mt-1.5 space-y-0.5">
                    <p class="text-xs text-blue-500"
                        x-show="selectedJobType && hasSpecialtyMatch">
                        ✓ Showing <span x-text="filteredStaff.length"></span>
                        staff matched to this job type's specialty
                    </p>
                    <p class="text-xs text-orange-500"
                        x-show="selectedJobType && !hasSpecialtyMatch">
                        ⚠ No staff specialised in this job type — showing all
                        <span x-text="filteredStaff.length"></span> staff
                    </p>
                    <p class="text-xs text-gray-400"
                        x-show="!selectedJobType">
                        Select a job type above to filter staff by specialty
                    </p>
                </div>

                @endif
            </div>

            {{-- Initial Diagnosis --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Initial Diagnosis
                    <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <textarea name="diagnosis" rows="3"
                    class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="e.g. Engine noise on startup, oil leak detected..."></textarea>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded hover:bg-blue-700 text-sm font-semibold">
                Create & Assign Job Card
            </button>
        </form>

        @endif
    </div>
</div>
@endsection