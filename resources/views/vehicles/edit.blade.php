@extends('layouts.app')
@section('page-title', 'Edit Vehicle')

@section('content')
<div class="max-w-lg mx-auto">

    @php
        $base = auth()->user()->role === 'admin' ? '/admin' :
               (auth()->user()->role === 'corporate' ? '/client' : '/customer');
    @endphp
    <a href="{{ $base }}/vehicles/{{ $vehicle->id }}" class="text-sm text-blue-600 hover:underline">← Back</a>

    <div class="bg-white rounded-lg shadow p-6 mt-4">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit — {{ $vehicle->plate_number }}</h2>

        <form method="POST" action="{{ $base }}/vehicles/{{ $vehicle->id }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ $vehicle->brand }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" value="{{ $vehicle->model }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <input type="number" name="year" value="{{ $vehicle->year }}"
                        min="1990" max="2030"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mileage (km)</label>
                    <input type="number" name="mileage" value="{{ $vehicle->mileage }}"
                        step="0.01"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Save Changes
            </button>
        </form>
    </div>
</div>
@endsection