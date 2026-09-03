@extends('layouts.app')
@section('page-title', 'Appointment Detail')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <a href="/admin/appointments" class="text-sm text-blue-600 hover:underline">← Back to Appointments</a>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-6 pb-4 border-b">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-gray-800">Appointment #{{ $appointment->id }}</h2>
                    @if($appointment->is_walkin)
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                        Walk-in
                    </span>
                    @endif
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium mt-1 inline-block
                    {{ $appointment->status === 'completed'  ? 'bg-green-100 text-green-700' :
                      ($appointment->status === 'confirmed'  ? 'bg-blue-100 text-blue-700' :
                      ($appointment->status === 'cancelled'  ? 'bg-red-100 text-red-700' :
                      'bg-yellow-100 text-yellow-700')) }}">
                    {{ ucfirst($appointment->status) }}
                </span>
            </div>
            <p class="text-sm text-gray-400">{{ $appointment->created_at->format('d M Y') }}</p>
        </div>

        {{-- Customer Info --}}
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Customer</h3>
            @if($appointment->is_walkin)
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i data-lucide="user" class="w-6 h-6 text-blue-400"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">{{ $appointment->walkin_name ?? 'Walk-in Customer' }}</p>
                    <p class="text-sm text-gray-500">{{ $appointment->walkin_contact ?? 'No contact number' }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Walk-in</span>
                </div>
            </div>
            @else
            <div class="flex items-center gap-3">
                <img src="{{ $appointment->user->avatar_url }}"
                    class="w-12 h-12 rounded-full object-cover border-2 border-blue-400">
                <div>
                    <p class="font-semibold text-gray-800">{{ $appointment->user->name ?? '—' }}</p>
                    <p class="text-sm text-gray-500">{{ $appointment->user->email ?? '—' }}</p>
                    <p class="text-sm text-gray-500">{{ $appointment->user->contact_no ?? 'No contact number' }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ ($appointment->user->role ?? '') === 'corporate' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">
                        {{ ucfirst($appointment->user->role ?? '') }}
                    </span>
                </div>
            </div>
            @endif
        </div>

        {{-- Vehicle Info --}}
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Vehicle</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-400">Plate Number</p>
                    <p class="font-semibold text-gray-800">{{ $appointment->vehicle->plate_number ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-400">Brand & Model</p>
                    <p class="font-semibold text-gray-800">
                        {{ $appointment->vehicle->brand ?? '—' }} {{ $appointment->vehicle->model ?? '' }}
                    </p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-400">Year</p>
                    <p class="font-semibold text-gray-800">{{ $appointment->vehicle->year ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-400">Mileage</p>
                    <p class="font-semibold text-gray-800">{{ number_format($appointment->vehicle->mileage ?? 0) }} km</p>
                </div>
            </div>
        </div>

        {{-- Service Info --}}
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Service Details</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-400">Service Type</p>
                    <p class="font-semibold text-gray-800">{{ $appointment->service_type }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-400">Date & Time</p>
                    <p class="font-semibold text-gray-800">
                        {{ \Carbon\Carbon::parse($appointment->date)->format('d M Y') }}
                        at {{ $appointment->time }}
                    </p>
                </div>
            </div>
            @if($appointment->notes)
            <div class="bg-gray-50 rounded p-3 mt-3">
                <p class="text-xs text-gray-400">Notes</p>
                <p class="text-sm text-gray-700 mt-1">{{ $appointment->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Linked Job Card (if any) --}}
        @if($appointment->jobCard)
        <div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-semibold text-purple-800">Job Card #{{ $appointment->jobCard->id }}</p>
                    <p class="text-xs text-purple-600 mt-0.5">
                        Stage: {{ ucfirst(str_replace('_', ' ', $appointment->jobCard->current_stage)) }}
                        &bull; RM {{ number_format($appointment->jobCard->total_cost, 2) }}
                    </p>
                </div>
                @if(auth()->user()->role === 'admin')
                <a href="/admin/job-cards/{{ $appointment->jobCard->id }}"
                    class="text-sm bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700">
                    View Job Card
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Feedback (customer/corporate, completed jobs only) --}}
        @if($appointment->jobCard && $appointment->jobCard->current_stage === 'completed' && in_array(auth()->user()->role, ['individual','corporate']))
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            @php $feedback = $appointment->jobCard->feedback; @endphp
            @if($feedback)
            <p class="text-sm font-semibold text-yellow-800 mb-1">Your Rating</p>
            <div class="flex items-center gap-1 mb-1">
                @for($i=1;$i<=5;$i++)
                <i data-lucide="star" class="w-4 h-4 {{ $i <= $feedback->rating ? 'text-yellow-500' : 'text-gray-300' }}"></i>
                @endfor
            </div>
            @if($feedback->comment)
            <p class="text-sm text-gray-600 italic">"{{ $feedback->comment }}"</p>
            @endif
            @else
            <p class="text-sm font-semibold text-yellow-800 mb-2">How was your service?</p>
            <form method="POST" action="/feedback/{{ $appointment->jobCard->id }}" x-data="{ rating: 0 }">
                @csrf
                <input type="hidden" name="rating" x-model="rating">
                <div class="flex items-center gap-1 mb-3">
                    <template x-for="i in 5" :key="i">
                        <button type="button" @click="rating = i">
                            <i data-lucide="star" class="w-6 h-6" :class="i <= rating ? 'text-yellow-500' : 'text-gray-300'"></i>
                        </button>
                    </template>
                </div>
                <textarea name="comment" rows="2" placeholder="Optional comments..."
                    class="w-full border rounded px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-yellow-500"></textarea>
                <button type="submit" x-bind:disabled="rating === 0"
                    class="bg-yellow-500 text-white px-4 py-2 rounded text-sm hover:bg-yellow-600 disabled:opacity-40 disabled:cursor-not-allowed">
                    Submit Rating
                </button>
            </form>
            @endif
        </div>
        @endif

        {{-- Actions --}}
        @if(auth()->user()->role === 'admin')
        <div class="flex gap-3 pt-4 border-t flex-wrap">
            @if($appointment->status === 'pending')
                @php $hasViewed = session('viewed_appointment_' . $appointment->id, false); @endphp

                {{-- Viewed confirmation notice --}}
                <div class="w-full mb-2 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                    You have reviewed this appointment. The Confirm button is now unlocked on the appointments list.
                </div>

                <form method="POST" action="/admin/appointments/{{ $appointment->id }}/confirm">
                    @csrf @method('PATCH')
                    <button class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        ✓ Confirm Appointment
                    </button>
                </form>
                <form method="POST" action="/admin/appointments/{{ $appointment->id }}/cancel">
                    @csrf @method('PATCH')
                    <button class="bg-red-500 text-white px-5 py-2 rounded hover:bg-red-600 text-sm font-medium">
                        ✗ Cancel
                    </button>
                </form>
            @elseif($appointment->status === 'confirmed')
                @if(!$appointment->jobCard)
                <a href="/admin/job-cards/create?appointment_id={{ $appointment->id }}"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                    + Create Job Card
                </a>
                @endif
                <form method="POST" action="/admin/appointments/{{ $appointment->id }}/complete">
                    @csrf @method('PATCH')
                    <button class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 text-sm font-medium">
                        ✓ Mark Completed
                    </button>
                </form>
                <form method="POST" action="/admin/appointments/{{ $appointment->id }}/cancel">
                    @csrf @method('PATCH')
                    <button class="bg-red-100 text-red-700 px-5 py-2 rounded hover:bg-red-200 text-sm font-medium">
                        Cancel
                    </button>
                </form>
            @else
            <p class="text-sm text-gray-400">
                This appointment has been <strong>{{ $appointment->status }}</strong>.
            </p>
            @endif
        </div>
        @endif
    </div>

</div>
@endsection