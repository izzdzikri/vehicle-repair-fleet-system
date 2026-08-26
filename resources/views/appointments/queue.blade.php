@extends('layouts.app')
@section('page-title', "Today's Service Queue")

@section('content')

<div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
    ℹ Priority rule: booked appointments are served in their scheduled time order first;
    walk-ins are queued after, in the order they arrived.
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-700">
            {{ now()->format('l, d F Y') }}
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $appointments->count() }} in queue</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Time / Arrived</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Vehicle</th>
                <th class="px-4 py-3">Service</th>
                <th class="px-4 py-3">Job Card</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $i => $apt)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-bold text-gray-400">{{ $i + 1 }}</td>
                <td class="px-4 py-3">
                    @if($apt->is_walkin)
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium">Walk-in</span>
                    @else
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Booked</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500">
                    {{ $apt->is_walkin ? $apt->created_at->format('H:i') . ' (arrival)' : $apt->time }}
                </td>
                <td class="px-4 py-3">
                    {{ $apt->is_walkin ? ($apt->walkin_name ?? 'Walk-in') : ($apt->user->name ?? '—') }}
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $apt->vehicle->plate_number ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $apt->vehicle->brand ?? '' }} {{ $apt->vehicle->model ?? '' }}</p>
                </td>
                <td class="px-4 py-3">{{ $apt->service_type }}</td>
                <td class="px-4 py-3">
                    @if($apt->jobCard)
                    <span class="text-xs text-purple-600">
                        #{{ $apt->jobCard->id }} — {{ $apt->jobCard->staff->name ?? 'Unassigned' }}
                    </span>
                    @else
                    <span class="text-xs text-gray-400">Not started</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="py-8 text-center text-gray-400">No confirmed appointments for today.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection