@extends('layouts.app')
@section('page-title', 'Staff Performance')

@section('content')

@if(session('success'))
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">{{ session('success') }}</div>
@endif

<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Attendance</a>
    <a href="/admin/staff-management/leave" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Leave Requests</a>
    <a href="/admin/staff-management/performance" class="px-4 py-2 rounded text-sm font-medium bg-blue-600 text-white">Performance</a>
    <a href="/admin/staff-management/salary" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Salary</a>
</div>

@if(auth()->user()->role === 'admin')
<form method="POST" action="/admin/staff-management/performance/email"
    class="bg-white rounded-lg shadow p-4 mb-4 flex items-center gap-4 flex-wrap">
    @csrf
    <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" name="notify_staff" value="1" class="rounded border-gray-300 text-blue-600">
        Also email each staff member their own results
    </label>
    <button type="submit" class="ml-auto bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex items-center gap-2">
        <i data-lucide="mail" class="w-4 h-4"></i> Email Report to Admin/HR
    </button>
</form>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">Staff Performance Report</h2>
        <p class="text-xs text-gray-400 mt-1">Based on completed job cards and recorded attendance, all time.</p>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Staff</th>
                <th class="px-4 py-3">Completed Jobs</th>
                <th class="px-4 py-3">On-Time</th>
                <th class="px-4 py-3">Overdue</th>
                <th class="px-4 py-3">Avg Turnaround</th>
                <th class="px-4 py-3">Attendance Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report as $r)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $r->staff->name }}</td>
                <td class="px-4 py-3">{{ $r->total_jobs }}</td>
                <td class="px-4 py-3 text-green-600 font-medium">{{ $r->on_time }}</td>
                <td class="px-4 py-3 text-red-500 font-medium">{{ $r->overdue }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $r->avg_hours !== null ? $r->avg_hours . ' hrs' : '—' }}</td>
                <td class="px-4 py-3">
                    @if($r->attendance_rate !== null)
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $r->attendance_rate >= 90 ? 'bg-green-100 text-green-700' :
                          ($r->attendance_rate >= 75 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                        {{ $r->attendance_rate }}%
                    </span>
                    @else
                    <span class="text-gray-400 text-xs">No data</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-8 text-center text-gray-400">No staff found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection