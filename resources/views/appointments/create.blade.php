@extends('layouts.app')
@section('page-title', 'Book Appointment')

@section('content')

<div class="max-w-xl mx-auto">
    @php
        $backUrl = request()->segment(1) === 'customer' ? '/customer/dashboard' : '/client/dashboard';
    @endphp
    <a href="{{ $backUrl }}"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs mb-4 transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Dashboard
    </a>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-7">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                <i data-lucide="calendar-plus" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Book Service Appointment</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">Schedule a slot for vehicle maintenance or repair</p>
            </div>
        </div>

        <form method="POST" action="{{ request()->segment(1) === 'customer' ? '/customer/appointments' : '/client/appointments' }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Select Vehicle *</label>
                <select name="vehicle_id" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white" required>
                    <option value="">Choose vehicle...</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ (string)old('vehicle_id', request('vehicle_id')) === (string)$v->id ? 'selected' : '' }}>
                            {{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }} ({{ $v->year }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Service Type *</label>
                @php
                    $reqType = old('service_type', request('service_type', ''));
                    $commonTypes = ['Oil Change', 'Tyre Replacement', 'Brake Service', 'Engine Diagnostics', 'Full Service', 'Air Conditioning', 'Battery Replacement', 'Other'];
                @endphp
                <select name="service_type" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white" required>
                    <option value="">Select service required...</option>
                    @if($reqType && !in_array($reqType, $commonTypes))
                        <option value="{{ $reqType }}" selected>{{ $reqType }} (From Maintenance Alert)</option>
                    @endif
                    @foreach($commonTypes as $type)
                        <option value="{{ $type }}" {{ $reqType === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Preferred Date *</label>
                    <input type="date" name="date" min="{{ date('Y-m-d') }}"
                        value="{{ old('date', request('date', '')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Preferred Time *</label>
                    <input type="time" name="time"
                        value="{{ old('time', request('time', '09:00')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Additional Notes / Symptoms (Optional)</label>
                <textarea name="notes" rows="3"
                    placeholder="Describe any warning lights, abnormal noises, or specific instructions..."
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">{{ old('notes', request('notes', '')) }}</textarea>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-xs transition flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Submit Appointment Booking
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
