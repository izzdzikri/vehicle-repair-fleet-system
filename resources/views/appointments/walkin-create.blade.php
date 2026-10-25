@extends('layouts.app')
@section('page-title', 'Walk-in Appointment')

@section('content')
<div class="max-w-2xl mx-auto">

    @php
        $backLink = match(auth()->user()->role) {
            'coordinator' => '/coordinator/appointments',
            'staff'       => '/staff/appointments/queue',
            default       => '/admin/appointments',
        };
        $postAction = match(auth()->user()->role) {
            'coordinator' => '/coordinator/appointments/walkin',
            'staff'       => '/staff/appointments/walkin',
            default       => '/admin/appointments/walkin',
        };
    @endphp
    <a href="{{ $backLink }}"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs mb-4 transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Appointments
    </a>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-7">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">New Walk-in Appointment</h2>
                    <p class="text-xs font-medium text-gray-500 mt-0.5">Register walk-in client and schedule immediate bay queue</p>
                </div>
            </div>
            <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full font-bold">
                Auto-confirmed on save
            </span>
        </div>

        @if($errors->any())
        <div class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ $postAction }}">
            @csrf

            {{-- Customer Info --}}
            <div class="mb-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Customer Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="walkin_name" value="{{ old('walkin_name', request('walkin_name')) }}"
                            placeholder="e.g. Ahmad bin Razak"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Contact Number</label>
                        <input type="text" name="walkin_contact" value="{{ old('walkin_contact', request('walkin_contact')) }}"
                            placeholder="e.g. 011-23456789"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            {{-- Vehicle Info --}}
            <div class="mb-6 pt-5 border-t border-gray-100">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Vehicle Details</h3>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Plate Number <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="plate_number" id="plate_number"
                        value="{{ old('plate_number', request('plate_number')) }}"
                        placeholder="e.g. VKK 3456"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase"
                        required>
                    <p class="text-xs text-gray-400 mt-1">
                        If this plate is already in the database, the existing record will be updated automatically.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Brand</label>
                        <input type="text" name="brand" value="{{ old('brand', request('brand')) }}"
                            placeholder="e.g. Proton"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Model</label>
                        <input type="text" name="model" value="{{ old('model', request('model')) }}"
                            placeholder="e.g. Saga"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Year</label>
                        <input type="number" name="year" value="{{ old('year', request('year', date('Y'))) }}"
                            placeholder="e.g. 2021" min="1970" max="{{ date('Y') + 1 }}"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            {{-- Service Info --}}
            <div class="mb-6 pt-5 border-t border-gray-100">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Service & Schedule</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Service Type <span class="text-rose-500">*</span>
                        </label>
                        @php
                            $reqWalkinType = old('service_type', request('service_type', ''));
                            $walkinTypes = ['Oil Change', 'Tyre Replacement', 'Brake Service', 'Engine Diagnostics', 'Full Service', 'Battery Check', 'Air Filter', 'Other'];
                        @endphp
                        <select name="service_type"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                            required>
                            <option value="">Select service...</option>
                            @if($reqWalkinType && !in_array($reqWalkinType, $walkinTypes))
                                <option value="{{ $reqWalkinType }}" selected>{{ $reqWalkinType }} (From Alert)</option>
                            @endif
                            @foreach($walkinTypes as $t)
                                <option value="{{ $t }}" {{ $reqWalkinType === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" value="{{ old('date', request('date', date('Y-m-d'))) }}"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Time <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" name="time" value="{{ old('time', request('time', date('H:i'))) }}"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Customer Notes (Optional)</label>
                    <textarea name="notes" rows="3"
                        placeholder="Customer complaints, specific concerns, or instructions..."
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3">
                <button type="submit"
                    class="bg-slate-900 text-white px-6 py-2.5 rounded-xl hover:bg-slate-800 text-sm font-bold shadow-xs transition flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i> Create Walk-in Appointment
                </button>
                <a href="{{ $backLink }}"
                    class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>
@endsection
