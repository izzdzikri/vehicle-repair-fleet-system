@extends('layouts.app')
@section('page-title', 'My Attendance')

@section('content')

@php
    $actionPrefix = auth()->user()->role === 'coordinator' ? '/coordinator' : '/staff';
@endphp

{{-- Subnavigation Tabs for Staff & Coordinator --}}
<div class="flex gap-2 mb-6 flex-wrap">
    <a href="{{ $actionPrefix }}/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs">My Attendance</a>
    <a href="{{ $actionPrefix }}/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">My Leave Requests</a>
    <a href="{{ $actionPrefix }}/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">My Salary & Payslips</a>
</div>

{{-- Today's Attendance Quick Clock-in Card --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6"
    x-data="{
        currentTime: '{{ now()->format('h:i:s A') }}',
        init() {
            setInterval(() => {
                const now = new Date();
                this.currentTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            }, 1000);
        }
    }">
    
    <div class="flex items-center justify-between mb-5 flex-wrap gap-2 pb-4 border-b border-gray-100">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-gray-900">Today's Shift Attendance</h2>
                <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full" x-text="currentTime"></span>
            </div>
            <p class="text-xs font-medium text-gray-500 mt-0.5">{{ now()->format('l, d F Y') }} &bull; Workshop Standard Hours: 08:30 AM – 05:30 PM</p>
        </div>
        <span class="text-xs font-bold px-3 py-1 rounded-full border
            {{ $today && $today->clock_in ? ($today->clock_out ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200') : 'bg-amber-50 text-amber-700 border-amber-200' }}">
            {{ $today && $today->clock_in ? ($today->clock_out ? 'Shift Completed (Clocked Out)' : 'Currently on Duty') : 'Not Yet Clocked In' }}
        </span>
    </div>

    <div class="flex items-center justify-between gap-6 flex-wrap">
        <div class="flex items-center gap-6 sm:gap-8 flex-wrap">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Clock In</p>
                <p class="text-2xl font-extrabold text-gray-900 font-mono mt-1">
                    {{ $today && $today->clock_in ? \Carbon\Carbon::parse($today->clock_in)->format('h:i A') : '— : —' }}
                </p>
            </div>
            <div class="h-10 w-px bg-gray-200 hidden sm:block"></div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Clock Out</p>
                <p class="text-2xl font-extrabold text-gray-900 font-mono mt-1">
                    {{ $today && $today->clock_out ? \Carbon\Carbon::parse($today->clock_out)->format('h:i A') : '— : —' }}
                </p>
            </div>
            <div class="h-10 w-px bg-gray-200 hidden sm:block"></div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Today's Duration</p>
                <p class="text-2xl font-extrabold text-blue-600 font-mono mt-1">
                    {{ $todayHours ?? '0h 00m' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 ml-auto">
            <form method="POST" action="{{ $actionPrefix }}/staff-management/attendance/clock-in">
                @csrf
                <button type="submit" {{ $today && $today->clock_in ? 'disabled' : '' }}
                    class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed shadow-xs transition flex items-center gap-2 active:scale-95">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Clock In Shift
                </button>
            </form>
            <form method="POST" action="{{ $actionPrefix }}/staff-management/attendance/clock-out">
                @csrf
                <button type="submit" {{ !$today || !$today->clock_in || $today->clock_out ? 'disabled' : '' }}
                    class="bg-rose-600 text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed shadow-xs transition flex items-center gap-2 active:scale-95">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Clock Out Shift
                </button>
            </form>
        </div>
    </div>
</div>

{{-- History Table --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
        <div>
            <h2 class="text-base font-bold text-gray-900 tracking-tight">Personal Attendance Log (Last 30 Days)</h2>
            <p class="text-xs text-gray-500 mt-0.5">Historical verification of your workshop shifts and recorded duty hours</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
            {{ count($history) }} shifts recorded
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50/80 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 px-5">Date</th>
                    <th class="py-3.5 px-5">Clock In</th>
                    <th class="py-3.5 px-5">Clock Out</th>
                    <th class="py-3.5 px-5">Total Duration</th>
                    <th class="py-3.5 px-5">Status</th>
                    <th class="py-3.5 px-5">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($history as $h)
                <tr class="hover:bg-gray-50/60 transition">
                    <td class="py-4 px-5 font-bold text-sm text-gray-900">
                        {{ $h->date ? $h->date->format('d M Y (D)') : '—' }}
                    </td>
                    <td class="py-4 px-5 font-mono text-xs font-semibold text-gray-700">
                        {{ $h->clock_in ? \Carbon\Carbon::parse($h->clock_in)->format('h:i A') : '—' }}
                    </td>
                    <td class="py-4 px-5 font-mono text-xs font-semibold text-gray-700">
                        {{ $h->clock_out ? \Carbon\Carbon::parse($h->clock_out)->format('h:i A') : '—' }}
                    </td>
                    <td class="py-4 px-5 text-xs font-bold text-blue-700">
                        {{ $h->duration }}
                    </td>
                    <td class="py-4 px-5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                            {{ $h->status === 'present'  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                              ($h->status === 'leave'    ? 'bg-blue-50 text-blue-700 border-blue-200' :
                              ($h->status === 'half_day' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                              ($h->status === 'absent'   ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-gray-50 text-gray-500 border-gray-200'))) }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $h->status === 'present' ? 'bg-emerald-500' : ($h->status === 'leave' ? 'bg-blue-500' : ($h->status === 'half_day' ? 'bg-amber-500' : ($h->status === 'absent' ? 'bg-rose-500' : 'bg-gray-400'))) }}"></span>
                            {{ ucfirst(str_replace('_',' ',$h->status)) }}
                        </span>
                    </td>
                    <td class="py-4 px-5 text-xs text-gray-500 max-w-48 truncate" title="{{ $h->notes ?? '' }}">
                        {{ $h->notes ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center">
                        <x-empty-state icon="clock" title="No attendance logs found" subtitle="Your clock-in and clock-out history will appear here once recorded." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
