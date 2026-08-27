@extends('layouts.app')
@section('page-title', 'Appointments')

@section('content')

<div class="flex justify-between items-center mb-4 flex-wrap gap-3">
    <h2 class="text-lg font-semibold text-gray-700">
        Appointments
        <span class="text-sm font-normal text-gray-400 ml-2">{{ $statusCounts['all'] }} total</span>
    </h2>
    <div class="flex gap-2">
        @if(auth()->user()->role === 'admin')
        <a href="/admin/appointments/walkin"
            class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 flex items-center gap-1">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Walk-in
        </a>
        @elseif(auth()->user()->role === 'staff')
        <a href="/staff/appointments/walkin"
            class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 flex items-center gap-1">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Walk-in
        </a>
        @else
        <a href="{{ request()->segment(1) === 'customer' ? '/customer/appointments/create' : '/client/appointments/create' }}"
            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
            + Book Appointment
        </a>
        @endif
    </div>
</div>

{{-- Search --}}
<form method="GET" class="mb-4 flex gap-2">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <input type="text" name="search" value="{{ $search }}"
        placeholder="Search by customer, plate number, or service..."
        class="flex-1 max-w-md border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Search</button>
    @if($search)
    <a href="{{ url()->current() }}{{ request('status') ? '?status='.request('status') : '' }}"
        class="px-4 py-2 rounded text-sm border text-gray-600 hover:bg-gray-50">Clear</a>
    @endif
</form>

{{-- Filter tabs — admin sees full lifecycle --}}
@if(auth()->user()->role === 'admin')
@php $qs = $search ? '&search='.urlencode($search) : ''; @endphp
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/appointments?1=1{{ $qs }}"
        class="px-4 py-2 rounded text-sm font-medium {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50' }}">
        All ({{ $statusCounts['all'] }})
    </a>
    <a href="/admin/appointments?status=pending{{ $qs }}"
        class="px-4 py-2 rounded text-sm font-medium {{ request('status') === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50' }}">
        Pending ({{ $statusCounts['pending'] }})
    </a>
    <a href="/admin/appointments?status=confirmed{{ $qs }}"
        class="px-4 py-2 rounded text-sm font-medium {{ request('status') === 'confirmed' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50' }}">
        Confirmed ({{ $statusCounts['confirmed'] }})
    </a>
    <a href="/admin/appointments?status=completed{{ $qs }}"
        class="px-4 py-2 rounded text-sm font-medium {{ request('status') === 'completed' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50' }}">
        Completed ({{ $statusCounts['completed'] }})
    </a>
    <a href="/admin/appointments?status=cancelled{{ $qs }}"
        class="px-4 py-2 rounded text-sm font-medium {{ request('status') === 'cancelled' ? 'bg-red-500 text-white' : 'bg-white text-gray-600 border hover:bg-gray-50' }}">
        Cancelled ({{ $statusCounts['cancelled'] }})
    </a>
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Date & Time</th>
                <th class="px-4 py-3">Service</th>
                <th class="px-4 py-3">Vehicle</th>
                @if(auth()->user()->role === 'admin')
                <th class="px-4 py-3">Customer</th>
                @endif
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $apt)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ \Carbon\Carbon::parse($apt->date)->format('d M Y') }}</p>
                    <p class="text-xs text-gray-400">{{ $apt->time }}</p>
                </td>
                <td class="px-4 py-3">{{ $apt->service_type }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $apt->vehicle->plate_number ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $apt->vehicle->brand ?? '' }} {{ $apt->vehicle->model ?? '' }}</p>
                </td>
                @if(auth()->user()->role === 'admin')
                <td class="px-4 py-3">
                    @if($apt->is_walkin)
                        <p class="font-medium">{{ $apt->walkin_name ?? 'Walk-in' }}</p>
                        <p class="text-xs text-gray-400">{{ $apt->walkin_contact ?? '—' }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Walk-in</span>
                    @else
                        <p class="font-medium">{{ $apt->user->name ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $apt->user->contact_no ?? $apt->user->email ?? '' }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ ($apt->user->role ?? '') === 'corporate' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">
                            {{ ucfirst($apt->user->role ?? '') }}
                        </span>
                    @endif
                </td>
                @endif
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $apt->status === 'completed'  ? 'bg-green-100 text-green-700' :
                          ($apt->status === 'confirmed'  ? 'bg-blue-100 text-blue-700' :
                          ($apt->status === 'cancelled'  ? 'bg-red-100 text-red-700' :
                          'bg-yellow-100 text-yellow-700')) }}">
                        {{ ucfirst($apt->status) }}
                    </span>
                </td>

                {{-- ACTION COLUMN – clean version for admin --}}
                <td class="px-4 py-3">
                    @if(auth()->user()->role === 'admin')
                        <div class="flex gap-1 flex-wrap items-center">

                            <a href="/admin/appointments/{{ $apt->id }}"
                                class="text-xs border border-gray-300 text-gray-600 px-2 py-1 rounded hover:bg-gray-50">
                                View
                            </a>

                            @if($apt->status === 'pending')
                                @php $hasViewed = session('viewed_appointment_' . $apt->id, false); @endphp

                                @if($hasViewed)
                                <form method="POST" action="/admin/appointments/{{ $apt->id }}/confirm">
                                    @csrf @method('PATCH')
                                    <button class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 font-medium">
                                        ✓ Confirm
                                    </button>
                                </form>
                                @else
                                <a href="/admin/appointments/{{ $apt->id }}"
                                    class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded border border-gray-300 hover:bg-gray-200"
                                    title="View appointment details first to unlock confirm">
                                    👁 View to Confirm
                                </a>
                                @endif

                                <form method="POST" action="/admin/appointments/{{ $apt->id }}/cancel">
                                    @csrf @method('PATCH')
                                    <button class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">
                                        Cancel
                                    </button>
                                </form>

                            @elseif($apt->status === 'confirmed')
                                @if(!$apt->jobCard)
                                <a href="/admin/job-cards/create?appointment_id={{ $apt->id }}"
                                    class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded hover:bg-purple-200">
                                    + Job Card
                                </a>
                                @else
                                <a href="/admin/job-cards/{{ $apt->jobCard->id }}"
                                    class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded hover:bg-purple-200">
                                    Job #{{ $apt->jobCard->id }}
                                </a>
                                @endif

                                <form method="POST" action="/admin/appointments/{{ $apt->id }}/complete">
                                    @csrf @method('PATCH')
                                    <button class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200">
                                        Complete
                                    </button>
                                </form>

                                <form method="POST" action="/admin/appointments/{{ $apt->id }}/cancel">
                                    @csrf @method('PATCH')
                                    <button class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">
                                        Cancel
                                    </button>
                                </form>

                            @elseif($apt->status === 'completed' || $apt->status === 'cancelled')
                                <span class="text-xs text-gray-400">—</span>

                            @endif
                        </div>
                    @else
                        <span class="text-xs text-gray-400">{{ $apt->notes ?? '—' }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-8 text-center text-gray-400">No appointments found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($appointments->hasPages())
    <div class="px-4 py-3 border-t">
        {{ $appointments->links() }}
    </div>
    @endif
</div>
@endsection