@extends('layouts.app')
@section('page-title', 'Vehicles')

@section('content')

@php
    $base = auth()->user()->role === 'admin' ? '/admin' :
           (auth()->user()->role === 'corporate' ? '/client' : '/customer');
@endphp

<div class="flex justify-between items-center mb-4 gap-4 flex-wrap">
    <h2 class="text-lg font-semibold text-gray-700">
        Vehicles
        <span class="text-sm font-normal text-gray-400 ml-2">{{ $vehicles->count() }} total</span>
    </h2>
    <div class="flex gap-2 flex-wrap">
        @if(auth()->user()->role === 'admin')
        <form method="GET" action="/admin/vehicles" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search plate, brand, model..."
                class="border rounded px-3 py-2 text-sm w-64">
            <button type="submit"
                class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Search
            </button>
        </form>
        @endif
        <a href="{{ $base }}/vehicles/create"
            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
            + Add Vehicle
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Plate No</th>
                <th class="px-4 py-3">Brand & Model</th>
                <th class="px-4 py-3">Year</th>
                <th class="px-4 py-3">Mileage</th>
                @if(auth()->user()->role === 'admin')
                <th class="px-4 py-3">Owner</th>
                @endif
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehicles as $v)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3">
                    <a href="{{ $base }}/vehicles/{{ $v->id }}"
                        class="font-bold text-blue-700 hover:underline">
                        {{ $v->plate_number }}
                    </a>
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $v->brand }} {{ $v->model }}</p>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $v->year }}</td>
                <td class="px-4 py-3 text-gray-500">{{ number_format($v->mileage) }} km</td>
                @if(auth()->user()->role === 'admin')
                <td class="px-4 py-3">
                    <p class="font-medium text-sm">{{ $v->owner->name ?? '—' }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ ($v->owner->role ?? '') === 'corporate' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">
                        {{ ucfirst($v->owner->role ?? '') }}
                    </span>
                </td>
                @endif
                <td class="px-4 py-3">
                    <a href="{{ $base }}/vehicles/{{ $v->id }}"
                        class="text-blue-600 hover:underline text-xs font-medium mr-2">View</a>
                    <a href="{{ $base }}/vehicles/{{ $v->id }}/edit"
                        class="text-gray-500 hover:underline text-xs mr-2">Edit</a>
                    <form method="POST" action="{{ $base }}/vehicles/{{ $v->id }}"
                        class="inline" onsubmit="return confirm('Delete this vehicle?')">
                        @csrf @method('DELETE')
                        <button class="text-red-500 hover:text-red-700 text-xs">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-8 text-center text-gray-400">No vehicles registered yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div></div>
@endsection