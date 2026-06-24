@extends('layouts.app')
@section('page-title', 'Add Vehicle')

@section('content')
<div class="max-w-lg mx-auto">

    @php
        $base = auth()->user()->role === 'admin' ? '/admin' :
               (auth()->user()->role === 'corporate' ? '/client' : '/customer');
    @endphp
    <a href="{{ $base }}/vehicles" class="text-sm text-blue-600 hover:underline">← Back</a>

    <div class="bg-white rounded-lg shadow p-6 mt-4">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Add Vehicle</h2>

        <form method="POST" action="{{ $base }}/vehicles" class="space-y-4">
            @csrf

            {{-- Admin can assign to any customer --}}
            @if(auth()->user()->role === 'admin' && $owners)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                <select name="user_id"
                    class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Select owner (leave blank for self)</option>
                    @foreach($owners as $o)
                    <option value="{{ $o->id }}">{{ $o->name }} — {{ ucfirst($o->role) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number</label>
                <input type="text" name="plate_number"
                    class="w-full border rounded px-3 py-2 text-sm uppercase focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g. ABC1234" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. Toyota" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. Vios" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <input type="number" name="year" min="1990" max="2030"
                        value="{{ date('Y') }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Mileage (km)</label>
                    <input type="number" name="mileage" step="0.01" value="0"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Add Vehicle
            </button>
        </form>
    </div>
</div>
@endsection