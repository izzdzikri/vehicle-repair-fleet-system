@extends('layouts.app')
@section('page-title', 'Add Vehicle')

@section('content')
<div class="max-w-xl mx-auto">

    @php
        $base = auth()->user()->role === 'admin' ? '/admin' :
               (auth()->user()->role === 'corporate' ? '/client' : '/customer');
    @endphp
    <a href="{{ $base }}/vehicles"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs mb-4 transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Vehicles
    </a>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-7">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                <i data-lucide="car" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Register New Vehicle</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">Add vehicle information to workshop fleet inventory</p>
            </div>
        </div>

        <form method="POST" action="{{ $base }}/vehicles" class="space-y-4">
            @csrf

            {{-- Admin can assign to any customer (Corporate Fleet vs Individual) --}}
            @if(auth()->user()->role === 'admin' && isset($owners) && $owners)
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-800 mb-1">
                    Assign Vehicle Ownership <span class="text-rose-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-2.5">
                    Categorize this vehicle under a <strong>Corporate Fleet</strong> or an <strong>Individual Customer</strong>:
                </p>
                <select name="user_id"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-white" required>
                    <option value="">Choose registered owner / company...</option>

                    @php
                        $corporateOwners = $owners->filter(fn($u) => $u->role === 'corporate');
                        $individualOwners = $owners->filter(fn($u) => $u->role === 'individual');
                    @endphp

                    @if($corporateOwners->count())
                    <optgroup label="🏢 Corporate Fleets (Company Accounts)">
                        @foreach($corporateOwners as $o)
                        <option value="{{ $o->id }}" {{ old('user_id') == $o->id ? 'selected' : '' }}>
                            {{ $o->company->name ?? 'Company Account' }} — PIC: {{ $o->name }} ({{ $o->email }})
                        </option>
                        @endforeach
                    </optgroup>
                    @endif

                    @if($individualOwners->count())
                    <optgroup label="👤 Individual Private Owners">
                        @foreach($individualOwners as $o)
                        <option value="{{ $o->id }}" {{ old('user_id') == $o->id ? 'selected' : '' }}>
                            {{ $o->name }} — {{ $o->contact_no ?? $o->email }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endif
                </select>
            </div>
            @endif

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Plate Number *</label>
                <input type="text" name="plate_number"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono font-bold uppercase focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g. WXY 1234" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Brand / Make *</label>
                    <input type="text" name="brand"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. Toyota, Proton, Honda" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Model *</label>
                    <input type="text" name="model"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. Hilux, Saga, City" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Year of Manufacture *</label>
                    <input type="number" name="year" min="1980" max="{{ date('Y') + 1 }}"
                        value="{{ date('Y') }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Current Mileage (km)</label>
                    <input type="number" name="mileage" step="0.01" value="0"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="pt-3">
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-xs transition flex items-center justify-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Save Vehicle Record
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
