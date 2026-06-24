@extends('layouts.app')
@section('page-title', 'Walk-in Appointment')

@section('content')
<div class="max-w-2xl mx-auto">

    <a href="{{ auth()->user()->role === 'admin' ? '/admin/appointments' : '/staff/dashboard' }}"
        class="text-sm text-blue-600 hover:underline">← Back to Appointments</a>

    <div class="bg-white rounded-lg shadow p-6 mt-4">
        <div class="flex items-center gap-2 mb-6">
            <i data-lucide="user-plus" class="w-5 h-5 text-blue-500"></i>
            <h2 class="text-lg font-semibold text-gray-700">New Walk-in Appointment</h2>
            <span class="ml-auto text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium">
                Auto-confirmed on save
            </span>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
            action="{{ auth()->user()->role === 'admin' ? '/admin/appointments/walkin' : '/staff/appointments/walkin' }}">
            @csrf

            {{-- Customer Info --}}
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Customer</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Customer Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="walkin_name" value="{{ old('walkin_name') }}"
                        placeholder="e.g. Ahmad bin Razak"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" name="walkin_contact" value="{{ old('walkin_contact') }}"
                        placeholder="e.g. 011-23456789"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Vehicle Info --}}
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Vehicle</h3>

            {{-- Plate lookup section --}}
            <div class="mb-4" x-data="{ found: false, searched: false, plate: '' }">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Plate Number <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <input type="text" name="plate_number" id="plate_number"
                        value="{{ old('plate_number') }}"
                        placeholder="e.g. VKK 3456"
                        class="flex-1 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase"
                        required>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    If the plate is already registered, the existing vehicle record will be used.
                    Otherwise fill in the details below to create a new record.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand') }}"
                        placeholder="e.g. Proton"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" value="{{ old('model') }}"
                        placeholder="e.g. Saga"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <input type="number" name="year" value="{{ old('year') }}"
                        placeholder="e.g. 2019" min="1970" max="{{ date('Y') + 1 }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Service Info --}}
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Service</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Service Type <span class="text-red-500">*</span>
                    </label>
                    <select name="service_type"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="">Select service</option>
                        <option value="Oil Change" {{ old('service_type') === 'Oil Change' ? 'selected' : '' }}>Oil Change</option>
                        <option value="Tyre Replacement" {{ old('service_type') === 'Tyre Replacement' ? 'selected' : '' }}>Tyre Replacement</option>
                        <option value="Brake Service" {{ old('service_type') === 'Brake Service' ? 'selected' : '' }}>Brake Service</option>
                        <option value="Engine Diagnostics" {{ old('service_type') === 'Engine Diagnostics' ? 'selected' : '' }}>Engine Diagnostics</option>
                        <option value="Full Service" {{ old('service_type') === 'Full Service' ? 'selected' : '' }}>Full Service</option>
                        <option value="Battery Check" {{ old('service_type') === 'Battery Check' ? 'selected' : '' }}>Battery Check</option>
                        <option value="Air Filter" {{ old('service_type') === 'Air Filter' ? 'selected' : '' }}>Air Filter</option>
                        <option value="Other" {{ old('service_type') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="time" value="{{ old('time', '09:00') }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea name="notes" rows="3"
                    placeholder="Any additional notes from customer..."
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                    Create Walk-in Appointment
                </button>
                <a href="{{ auth()->user()->role === 'admin' ? '/admin/appointments' : '/staff/dashboard' }}"
                    class="px-6 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>
@endsection
