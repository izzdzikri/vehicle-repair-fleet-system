@extends('layouts.app')
@section('page-title', 'Edit Vehicle')

@section('content')
<div class="max-w-xl mx-auto">

    @php
        $base = auth()->user()->role === 'admin' ? '/admin' :
               (auth()->user()->role === 'corporate' ? '/client' : '/customer');
    @endphp
    <a href="{{ $base }}/vehicles/{{ $vehicle->id }}"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs mb-4 transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Vehicle Details
    </a>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-7">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                    <i data-lucide="edit-3" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Edit Vehicle Specs</h2>
                    <p class="text-xs font-medium text-gray-500 mt-0.5">Update vehicle registration details and mileage</p>
                </div>
            </div>
            <span class="font-mono text-xs font-bold bg-gray-100 text-gray-900 px-2.5 py-1 rounded-md border border-gray-200">
                {{ $vehicle->plate_number }}
            </span>
        </div>

        {{-- Current Ownership Summary --}}
        <div class="mb-5 p-3.5 rounded-xl border {{ $vehicle->isFleet() ? 'bg-amber-50/60 border-amber-200' : 'bg-emerald-50/60 border-emerald-200' }}">
            <div class="flex items-center justify-between flex-wrap gap-2 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-bold uppercase tracking-wider text-[11px] px-2 py-0.5 rounded {{ $vehicle->isFleet() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $vehicle->isFleet() ? '🏢 Corporate Fleet' : '👤 Individual Private' }}
                    </span>
                    @if($vehicle->isFleet() && $vehicle->company)
                        <span class="font-bold text-gray-900">{{ $vehicle->company->name }}</span>
                        <span class="text-gray-500">&bull; PIC: {{ $vehicle->owner->name ?? '—' }}</span>
                    @else
                        <span class="font-bold text-gray-900">{{ $vehicle->owner->name ?? 'Unassigned' }}</span>
                    @endif
                </div>
                <span class="text-gray-500 font-medium">{{ $vehicle->owner->contact_no ?? $vehicle->owner->email ?? 'No contact' }}</span>
            </div>
        </div>

        <form method="POST" action="{{ $base }}/vehicles/{{ $vehicle->id }}" class="space-y-4">
            @csrf @method('PUT')

            {{-- Admin Ownership Transfer / Reassignment --}}
            @if(auth()->user()->role === 'admin' && isset($owners) && $owners)
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-800 mb-1">
                    Reassign Registered Owner / Fleet Company
                </label>
                <p class="text-xs text-gray-500 mb-2">
                    You can switch this vehicle between corporate fleet companies or individual customer accounts:
                </p>
                <select name="user_id"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                    @php
                        $corporateOwners = $owners->filter(fn($u) => $u->role === 'corporate');
                        $individualOwners = $owners->filter(fn($u) => $u->role === 'individual');
                    @endphp

                    @if($corporateOwners->count())
                    <optgroup label="🏢 Corporate Fleets (Company Accounts)">
                        @foreach($corporateOwners as $o)
                        <option value="{{ $o->id }}" {{ old('user_id', $vehicle->user_id) == $o->id ? 'selected' : '' }}>
                            {{ $o->company->name ?? 'Company Account' }} — PIC: {{ $o->name }} ({{ $o->email }})
                        </option>
                        @endforeach
                    </optgroup>
                    @endif

                    @if($individualOwners->count())
                    <optgroup label="👤 Individual Private Owners">
                        @foreach($individualOwners as $o)
                        <option value="{{ $o->id }}" {{ old('user_id', $vehicle->user_id) == $o->id ? 'selected' : '' }}>
                            {{ $o->name }} — {{ $o->contact_no ?? $o->email }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endif
                </select>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Brand / Make *</label>
                    <input type="text" name="brand" value="{{ old('brand', $vehicle->brand) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Model *</label>
                    <input type="text" name="model" value="{{ old('model', $vehicle->model) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Year *</label>
                    <input type="number" name="year" value="{{ old('year', $vehicle->year) }}"
                        min="1980" max="{{ date('Y') + 1 }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Current Mileage (km)</label>
                    <input type="number" name="mileage" value="{{ old('mileage', $vehicle->mileage) }}"
                        step="0.01"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="pt-3">
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-xs transition flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
