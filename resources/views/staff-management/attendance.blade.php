@extends('layouts.app')
@section('page-title', 'Staff Attendance')

@section('content')

@if(session('success'))
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">{{ session('success') }}</div>
@endif

<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded text-sm font-medium bg-blue-600 text-white">Attendance</a>
    <a href="/admin/staff-management/leave" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Leave Requests</a>
    <a href="/admin/staff-management/performance" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Performance</a>
</div>

<form method="GET" action="/admin/staff-management/attendance" class="mb-4 flex gap-3 items-end">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
        <input type="date" name="date" value="{{ $date }}"
            class="border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            onchange="this.form.submit()">
    </div>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">
            Attendance — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
        </h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Staff</th>
                <th class="px-4 py-3">Clock In</th>
                <th class="px-4 py-3">Clock Out</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $row->staff->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $row->record->clock_in ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $row->record->clock_out ?? '—' }}</td>
                <td class="px-4 py-3">
                    @php $status = $row->record->status ?? 'no record'; @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $status === 'present' ? 'bg-green-100 text-green-700' :
                          ($status === 'leave' ? 'bg-blue-100 text-blue-700' :
                          ($status === 'half_day' ? 'bg-yellow-100 text-yellow-700' :
                          ($status === 'absent' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-400'))) }}">
                        {{ ucfirst(str_replace('_',' ',$status)) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-8 text-center text-gray-400">No staff found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection