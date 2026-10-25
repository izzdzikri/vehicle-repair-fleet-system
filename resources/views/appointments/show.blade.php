@extends('layouts.app')
@section('page-title', 'Appointment Detail')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    @php
        $backUrl = match(auth()->user()->role) {
            'coordinator' => '/coordinator/appointments',
            'corporate'   => '/client/appointments',
            'individual'  => '/customer/appointments',
            default       => '/admin/appointments',
        };
    @endphp
    <div>
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Appointments
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8">
        <div class="flex justify-between items-start mb-6 pb-5 border-b border-gray-100 flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Appointment #{{ $appointment->id }}</h2>
                    @if($appointment->is_walkin)
                    <span class="text-xs font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-0.5 rounded-full">
                        Walk-in
                    </span>
                    @endif
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold border
                        {{ $appointment->status === 'completed'  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          ($appointment->status === 'confirmed'  ? 'bg-blue-50 text-blue-700 border-blue-200' :
                          ($appointment->status === 'cancelled'  ? 'bg-rose-50 text-rose-700 border-rose-200' :
                          'bg-amber-50 text-amber-700 border-amber-200')) }}">
                        <span class="w-1.5 h-1.5 rounded-full
                            {{ $appointment->status === 'completed' ? 'bg-emerald-500' :
                              ($appointment->status === 'confirmed' ? 'bg-blue-500' :
                              ($appointment->status === 'cancelled' ? 'bg-rose-500' : 'bg-amber-500')) }}"></span>
                        {{ ucfirst($appointment->status) }}
                    </span>
                    <span class="text-xs text-gray-400">&bull; Created on {{ $appointment->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Target Service Date</span>
                <span class="text-base font-bold text-gray-900">{{ \Carbon\Carbon::parse($appointment->date)->format('d M Y') }}</span>
                <span class="text-xs font-medium text-gray-500 block">at {{ $appointment->time }}</span>
            </div>
        </div>

        {{-- Customer Info --}}
        <div class="mb-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer Information</h3>
            @if($appointment->is_walkin)
            <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center text-purple-700 shrink-0">
                    <i data-lucide="user" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="font-bold text-base text-gray-900">{{ $appointment->walkin_name ?? 'Walk-in Customer' }}</p>
                    <p class="text-sm font-medium text-gray-500 mt-0.5">{{ $appointment->walkin_contact ?? 'No phone provided' }}</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                <img src="{{ $appointment->user->avatar_url }}"
                    class="w-12 h-12 rounded-xl object-cover border border-gray-200">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-bold text-base text-gray-900">{{ $appointment->user->name ?? '—' }}</p>
                        @if(($appointment->user->role ?? '') === 'corporate' && $appointment->user->company)
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded border uppercase bg-amber-50 text-amber-800 border-amber-200 inline-flex items-center gap-1">
                                <i data-lucide="building-2" class="w-3 h-3 text-amber-600"></i> Fleet: {{ $appointment->user->company->name }}
                            </span>
                        @else
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded border uppercase bg-emerald-50 text-emerald-800 border-emerald-200 inline-flex items-center gap-1">
                                <i data-lucide="user" class="w-3 h-3 text-emerald-600"></i> Individual
                            </span>
                        @endif
                    </div>
                    <p class="text-sm font-medium text-gray-600 mt-0.5">{{ $appointment->user->email ?? '—' }} &bull; {{ $appointment->user->contact_no ?? 'No phone' }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Vehicle Info --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Vehicle Specifications</h3>
                @if($appointment->vehicle)
                    @if($appointment->vehicle->isFleet())
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">
                        <i data-lucide="building-2" class="w-3 h-3 text-amber-600"></i> Corporate Fleet &bull; {{ $appointment->vehicle->company->name ?? 'Company Account' }}
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <i data-lucide="user" class="w-3 h-3 text-emerald-600"></i> Individual Private Vehicle
                    </span>
                    @endif
                @endif
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3.5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Plate Number</p>
                    <p class="font-mono text-sm font-bold text-gray-900 mt-1">{{ $appointment->vehicle->plate_number ?? '—' }}</p>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3.5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Make & Model</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">
                        {{ $appointment->vehicle->brand ?? '—' }} {{ $appointment->vehicle->model ?? '' }}
                    </p>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3.5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Model Year</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $appointment->vehicle->year ?? '—' }}</p>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3.5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Current Mileage</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ number_format($appointment->vehicle->mileage ?? 0) }} km</p>
                </div>
            </div>
        </div>

        {{-- Service Info --}}
        <div class="mb-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Service Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Service</p>
                    <p class="text-base font-bold text-gray-900 mt-1">{{ $appointment->service_type }}</p>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment Time</p>
                    <p class="text-base font-bold text-gray-900 mt-1">
                        {{ \Carbon\Carbon::parse($appointment->date)->format('l, d M Y') }} at {{ $appointment->time }}
                    </p>
                </div>
            </div>
            @if($appointment->notes)
            <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-4 mt-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer Issue Notes / Symptoms</p>
                <p class="text-sm text-gray-700 mt-1.5 leading-relaxed">{{ $appointment->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Linked Job Card (if any) --}}
        @if($appointment->jobCard)
        <div class="mb-6 p-4 bg-purple-50/60 border border-purple-200/80 rounded-xl">
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-purple-900">Job Card #{{ $appointment->jobCard->id }}</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                            {{ ucfirst(str_replace('_', ' ', $appointment->jobCard->current_stage)) }}
                        </span>
                    </div>
                    <p class="text-xs font-medium text-purple-700 mt-1">
                        Total Order: RM {{ number_format($appointment->jobCard->total_cost, 2) }} &bull; Assigned to {{ $appointment->jobCard->staff->name ?? 'Unassigned' }}
                    </p>
                </div>
                @if(auth()->user()->role === 'admin')
                <a href="/admin/job-cards/{{ $appointment->jobCard->id }}"
                    class="text-xs font-semibold bg-purple-700 text-white px-3.5 py-2 rounded-lg hover:bg-purple-800 transition shadow-xs flex items-center gap-1.5">
                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i> Open Job Card
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Feedback (customer/corporate, completed jobs only) --}}
        @if($appointment->jobCard && $appointment->jobCard->current_stage === 'completed' && in_array(auth()->user()->role, ['individual','corporate']))
        <div class="mb-6 p-5 bg-amber-50/70 border border-amber-200/80 rounded-xl">
            @php $feedback = $appointment->jobCard->feedback; @endphp
            @if($feedback)
            <p class="text-sm font-bold text-amber-900 mb-2">Customer Service Rating</p>
            <div class="flex items-center gap-1 mb-2">
                @for($i=1;$i<=5;$i++)
                <i data-lucide="star" class="w-4 h-4 {{ $i <= $feedback->rating ? 'text-amber-500 fill-amber-500' : 'text-gray-300' }}"></i>
                @endfor
            </div>
            @if($feedback->comment)
            <p class="text-sm text-gray-700 italic bg-white p-3 rounded-lg border border-amber-100">"{{ $feedback->comment }}"</p>
            @endif
            @else
            <p class="text-sm font-bold text-amber-900 mb-2">How was your service experience?</p>
            <form method="POST" action="/feedback/{{ $appointment->jobCard->id }}" x-data="{ rating: 0 }">
                @csrf
                <input type="hidden" name="rating" x-model="rating">
                <div class="flex items-center gap-1.5 mb-3">
                    <template x-for="i in 5" :key="i">
                        <button type="button" @click="rating = i" class="hover:scale-110 transition">
                            <i data-lucide="star" class="w-6 h-6" :class="i <= rating ? 'text-amber-500 fill-amber-500' : 'text-gray-300'"></i>
                        </button>
                    </template>
                </div>
                <textarea name="comment" rows="2" placeholder="Tell us about the repair quality or experience..."
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm mb-3 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"></textarea>
                <button type="submit" x-bind:disabled="rating === 0"
                    class="bg-amber-500 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-xs">
                    Submit Feedback
                </button>
            </form>
            @endif
        </div>
        @endif

        {{-- Actions (Admin & Coordinator) --}}
        @if(in_array(auth()->user()->role, ['admin', 'coordinator']))
        @php
            $actionPrefix = auth()->user()->role === 'coordinator' ? '/coordinator' : '/admin';
        @endphp
        <div class="pt-5 border-t border-gray-100">
            @if($appointment->status === 'pending')
                @php $hasViewed = session('viewed_appointment_' . $appointment->id, false); @endphp

                {{-- Viewed confirmation notice --}}
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-medium text-emerald-800 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span>You have reviewed this appointment record. Confirming now reserves the shop bay.</span>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <form method="POST" action="{{ $actionPrefix }}/appointments/{{ $appointment->id }}/confirm">
                        @csrf @method('PATCH')
                        <button class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 text-sm font-bold transition shadow-xs flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4"></i> Confirm Appointment
                        </button>
                    </form>
                    <form method="POST" action="{{ $actionPrefix }}/appointments/{{ $appointment->id }}/cancel">
                        @csrf @method('PATCH')
                        <button class="bg-white border border-gray-200 text-rose-600 px-5 py-2.5 rounded-xl hover:bg-rose-50 text-sm font-semibold transition shadow-xs flex items-center gap-2">
                            <i data-lucide="x" class="w-4 h-4"></i> Cancel Booking
                        </button>
                    </form>
                </div>
            @elseif($appointment->status === 'confirmed')
                <div class="flex items-center gap-3 flex-wrap">
                    @if(!$appointment->jobCard && auth()->user()->role === 'admin')
                    <a href="/admin/job-cards/create?appointment_id={{ $appointment->id }}"
                        class="bg-purple-600 text-white px-5 py-2.5 rounded-xl hover:bg-purple-700 text-sm font-semibold transition shadow-xs flex items-center gap-2">
                        <i data-lucide="clipboard-plus" class="w-4 h-4"></i> Create Job Card
                    </a>
                    @endif
                    <form method="POST" action="{{ $actionPrefix }}/appointments/{{ $appointment->id }}/complete">
                        @csrf @method('PATCH')
                        <button class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl hover:bg-emerald-700 text-sm font-semibold transition shadow-xs flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Mark Completed
                        </button>
                    </form>
                    <form method="POST" action="{{ $actionPrefix }}/appointments/{{ $appointment->id }}/cancel">
                        @csrf @method('PATCH')
                        <button class="bg-white border border-gray-200 text-gray-600 px-4 py-2.5 rounded-xl hover:bg-rose-50 hover:text-rose-600 text-sm font-semibold transition">
                            Cancel
                        </button>
                    </form>
                </div>
            @else
            <p class="text-sm font-medium text-gray-500">
                This appointment record is marked as <strong class="text-gray-900">{{ $appointment->status }}</strong>.
            </p>
            @endif
        </div>
        @endif

        {{-- Informational Notice for Customers & Corporate Clients --}}
        @if(in_array(auth()->user()->role, ['individual', 'corporate']) && $appointment->status === 'pending')
        <div class="pt-5 border-t border-gray-100">
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs font-medium text-amber-800 flex items-start gap-3">
                <i data-lucide="clock" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                <div>
                    <strong class="font-bold block text-sm text-amber-900 mb-0.5">Appointment Pending Workshop Review</strong>
                    Your service booking has been submitted. Our workshop team will confirm your bay reservation shortly. You will be notified via email and in-app once confirmed.
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection