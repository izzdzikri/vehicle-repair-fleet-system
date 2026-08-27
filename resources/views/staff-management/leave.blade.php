@extends('layouts.app')
@section('page-title', 'Leave Requests')

@section('content')

@if(session('success'))
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">{{ session('success') }}</div>
@endif

@if(auth()->user()->role === 'admin')
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Attendance</a>
    <a href="/admin/staff-management/leave" class="px-4 py-2 rounded text-sm font-medium bg-blue-600 text-white">Leave Requests</a>
    <a href="/admin/staff-management/performance" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Performance</a>
    <a href="/admin/staff-management/salary" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Salary</a>
</div>
@endif

@if(auth()->user()->role === 'staff')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Request Leave</h2>
    <form method="POST" action="/staff/staff-management/leave" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" name="start_date"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" name="end_date"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <input type="text" name="reason" placeholder="e.g. Medical appointment"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700">
                Submit Request
            </button>
        </div>
    </form>
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">
            {{ auth()->user()->role === 'admin' ? 'All Leave Requests' : 'My Leave Requests' }}
            <span class="text-sm font-normal text-gray-400 ml-1">{{ $requests->count() }} total</span>
        </h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                @if(auth()->user()->role === 'admin')<th class="px-4 py-3">Staff</th>@endif
                <th class="px-4 py-3">Dates</th>
                <th class="px-4 py-3">Reason</th>
                <th class="px-4 py-3">Status</th>
                @if(auth()->user()->role === 'admin')<th class="px-4 py-3">Action</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $r)
            <tr class="border-b hover:bg-gray-50">
                @if(auth()->user()->role === 'admin')
                <td class="px-4 py-3 font-medium">{{ $r->staff->name ?? '—' }}</td>
                @endif
                <td class="px-4 py-3 text-gray-500">
                    {{ $r->start_date->format('d M Y') }} – {{ $r->end_date->format('d M Y') }}
                </td>
                <td class="px-4 py-3">{{ $r->reason }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $r->status === 'approved' ? 'bg-green-100 text-green-700' :
                          ($r->status === 'rejected' ? 'bg-red-100 text-red-700' :
                          'bg-yellow-100 text-yellow-700') }}">
                        {{ ucfirst($r->status) }}
                    </span>
                </td>
                @if(auth()->user()->role === 'admin')
                <td class="px-4 py-3">
                    @if($r->status === 'pending')
                    <div class="flex gap-1">
                        <form method="POST" action="/admin/staff-management/leave/{{ $r->id }}/approve">
                            @csrf @method('PATCH')
                            <button class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200">Approve</button>
                        </form>
                        <form method="POST" action="/admin/staff-management/leave/{{ $r->id }}/reject">
                            @csrf @method('PATCH')
                            <button class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">Reject</button>
                        </form>
                    </div>
                    @else
                    <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                @endif
            </tr>
            @empty
            <tr><td colspan="5" class="py-8 text-center text-gray-400">No leave requests.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection