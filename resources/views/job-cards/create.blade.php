@extends('layouts.app')
@section('page-title', 'Create Job Card')

@section('content')
<div class="max-w-2xl mx-auto">
    <a href="/admin/appointments"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs mb-4 transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Appointments
    </a>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-7">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                <i data-lucide="wrench" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Create & Assign Job Card</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">Initialize repair order, assign technician, and link parts</p>
            </div>
        </div>

        @if($appointments->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-sm font-medium text-amber-900 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <i data-lucide="alert-circle" class="w-5 h-5 text-amber-600"></i>
                <span>No confirmed appointments available for job card dispatch.</span>
            </div>
            <a href="/admin/appointments" class="text-xs font-bold text-amber-800 hover:underline">
                Review Appointments &rarr;
            </a>
        </div>
        @else

        <form method="POST" action="/admin/job-cards" class="space-y-4"
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
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                    Confirmed Appointment <span class="text-rose-500">*</span>
                </label>
                <select name="appointment_id"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                    required>
                    <option value="">Select confirmed appointment...</option>
                    @foreach($appointments as $apt)
                    <option value="{{ $apt->id }}"
                        {{ request('appointment_id') == $apt->id ? 'selected' : '' }}>
                        #{{ $apt->id }} — {{ $apt->vehicle->plate_number ?? '?' }}
                        | {{ $apt->service_type }}
                        | {{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }} {{ $apt->time }}
                        | {{ $apt->is_walkin ? 'Walk-in: '.$apt->walkin_name : ($apt->user->name ?? '?') }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Job Type --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Job Type & Standard Rate</label>
                <select name="job_type_id"
                    x-model="selectedJobType"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                    <option value="">Select standard job type (optional)...</option>
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
                <div x-show="selectedJobType" class="mt-2.5 flex gap-2 flex-wrap" style="display:none">
                    <span class="text-xs bg-blue-50 border border-blue-200 text-blue-700 px-2.5 py-1 rounded-md font-bold">
                        ⏱ Est: <span x-text="estimatedTime"></span>
                    </span>
                    <span class="text-xs bg-emerald-50 border border-emerald-200 text-emerald-700 px-2.5 py-1 rounded-md font-bold">
                        Base Labour: <span x-text="basePrice"></span>
                    </span>
                </div>
            </div>

            {{-- Assign Staff --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                    Assign Technician / Mechanic <span class="text-rose-500">*</span>
                </label>

                @if($staff->isEmpty())
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm text-rose-700 font-medium">
                    No active staff accounts found in database.
                    <a href="/admin/users" class="underline font-bold ml-1">Create a staff account first &rarr;</a>
                </div>
                @else

                <select name="staff_id"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                    required>
                    <option value="">Select technician...</option>
                    <template x-for="s in filteredStaff" :key="s.id">
                        <option :value="s.id"
                            x-text="s.name + ' (' + s.active_jobs + ' active job' + (s.active_jobs !== 1 ? 's' : '') + ')'">
                        </option>
                    </template>
                </select>

                {{-- Staff filter status --}}
                <div class="mt-2 text-xs">
                    <p class="text-blue-600 font-semibold"
                        x-show="selectedJobType && hasSpecialtyMatch">
                        ✓ Showing <span x-text="filteredStaff.length"></span> technician(s) matching this job specialty
                    </p>
                    <p class="text-amber-600 font-semibold"
                        x-show="selectedJobType && !hasSpecialtyMatch">
                        ⚠ No technicians specialized in this task — showing all <span x-text="filteredStaff.length"></span> workshop staff
                    </p>
                    <p class="text-gray-400 font-medium"
                        x-show="!selectedJobType">
                        Select a job type above to filter mechanics by specialty
                    </p>
                </div>

                @endif
            </div>

            {{-- Initial Diagnosis --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                    Initial Diagnosis / Work Instructions
                    <span class="font-normal text-gray-400 normal-case">(optional)</span>
                </label>
                <textarea name="diagnosis" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="e.g. Engine ticking on idle, inspect brake pads and rotor wear..."></textarea>
            </div>

            <div class="pt-3">
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-xs transition flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Dispatch & Create Job Card
                </button>
            </div>
        </form>

        @endif
    </div>
</div>
@endsection
