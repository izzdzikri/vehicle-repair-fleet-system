@extends('layouts.app')
@section('page-title', 'My Attendance')

@section('content')

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Today — {{ now()->format('d M Y') }}</h2>
    <div class="flex items-center gap-4 flex-wrap">
        <div class="text-sm">
            <p class="text-gray-400">Clock In</p>
            <p class="font-semibold text-gray-800">{{ $today->clock_in ?? '—' }}</p>
        </div>
        <div class="text-sm">
            <p class="text-gray-400">Clock Out</p>
            <p class="font-semibold text-gray-800">{{ $today->clock_out ?? '—' }}</p>
        </div>
        <div class="flex gap-2 ml-auto">
            <form method="POST" action="/staff/staff-management/attendance/clock-in">
                @csrf
                <button type="submit" {{ $today && $today->clock_in ? 'disabled' : '' }}
                    class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    Clock In
                </button>
            </form>
            <form method="POST" action="/staff/staff-management/attendance/clock-out">
                @csrf
                <button type="submit" {{ !$today || !$today->clock_in || $today->clock_out ? 'disabled' : '' }}
                    class="bg-red-500 text-white px-4 py-2 rounded text-sm hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed">
                    Clock Out
                </button>
            </form>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">Last 14 Days</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Clock In</th>
                <th class="px-4 py-3">Clock Out</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $h)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3">{{ $h->date->format('d M Y') }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $h->clock_in ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $h->clock_out ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $h->status === 'present' ? 'bg-green-100 text-green-700' :
                          ($h->status === 'leave' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ ucfirst(str_replace('_',' ',$h->status)) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-8 text-center text-gray-400">No attendance records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection