@extends('layouts.app')
@section('page-title', 'Book Appointment')

@section('content')

<div class="max-w-lg mx-auto bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Book an Appointment</h2>

    <form method="POST" action="{{ request()->segment(1) === 'customer' ? '/customer/appointments' : '/client/appointments' }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
            <select name="vehicle_id" class="w-full border rounded px-3 py-2 text-sm" required>
                <option value="">Select vehicle</option>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
            <select name="service_type" class="w-full border rounded px-3 py-2 text-sm" required>
                <option value="">Select service</option>
                <option>Oil Change</option>
                <option>Tyre Replacement</option>
                <option>Brake Service</option>
                <option>Engine Diagnostics</option>
                <option>Full Service</option>
                <option>Other</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" name="date" class="w-full border rounded px-3 py-2 text-sm" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
            <input type="time" name="time" class="w-full border rounded px-3 py-2 text-sm" required>
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
            <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 text-sm font-medium">
            Book Appointment
        </button>
    </form>
</div>
@endsection