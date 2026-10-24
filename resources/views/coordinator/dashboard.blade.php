@extends('layouts.app')
@section('page-title', 'Repair Progress Dashboard')

@section('content')

@include('partials.greeting')

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Active Jobs</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $totalActive }}</p>
            <a href="/coordinator/job-cards" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline inline-block mt-2">View all &rarr;</a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Today's Queue</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="calendar" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $todayAppointments }}</p>
            <a href="/coordinator/appointments/queue" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 hover:underline inline-block mt-2">Bay queue &rarr;</a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Low Stock</span>
            <div class="w-8 h-8 rounded-lg {{ $lowStockCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center">
                <i data-lucide="package" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-gray-900' }} tracking-tight">{{ $lowStockCount }}</p>
            <a href="/coordinator/inventory" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline inline-block mt-2">Inventory &rarr;</a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Needs Check-in</span>
            <div class="w-8 h-8 rounded-lg {{ $staleCount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center">
                <i data-lucide="alarm-clock" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold {{ $staleCount > 0 ? 'text-rose-600' : 'text-gray-400' }} tracking-tight">{{ $staleCount }}</p>
            @if($staleCount > 0)
            <a href="/coordinator/job-cards?filter=stale" class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline inline-block mt-2">Review jobs &rarr;</a>
            @else
            <span class="text-xs text-gray-400 inline-block mt-2">All up to date</span>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between col-span-2 md:col-span-1">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Overdue Jobs</span>
            <div class="w-8 h-8 rounded-lg {{ $overdueCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold {{ $overdueCount > 0 ? 'text-amber-600' : 'text-gray-400' }} tracking-tight">{{ $overdueCount }}</p>
            @if($overdueCount > 0)
            <a href="/coordinator/job-cards?filter=overdue" class="text-xs font-semibold text-amber-600 hover:text-amber-800 hover:underline inline-block mt-2">Review &rarr;</a>
            @else
            <span class="text-xs text-gray-400 inline-block mt-2">On schedule</span>
            @endif
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6">
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
        <div>
            <h3 class="text-base font-bold text-gray-900 tracking-tight">Workshop Operations Shortcuts</h3>
            <p class="text-xs text-gray-500 mt-0.5">Rapid dispatch and floor intake actions</p>
        </div>
        <span class="text-xs font-semibold text-gray-400">Coordinator Desk</span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="/coordinator/appointments/queue" class="group flex items-center gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-emerald-400 hover:bg-emerald-50/20 text-gray-700 transition">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white flex items-center justify-center transition shrink-0">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 group-hover:text-emerald-700">Today's Queue</p>
                <p class="text-xs text-gray-500">Monitor bay arrivals</p>
            </div>
        </a>
        <a href="/coordinator/appointments/walkin" class="group flex items-center gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-blue-400 hover:bg-blue-50/20 text-gray-700 transition">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition shrink-0">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 group-hover:text-blue-700">Walk-in Intake</p>
                <p class="text-xs text-gray-500">Fast customer entry</p>
            </div>
        </a>
        <a href="/coordinator/job-cards" class="group flex items-center gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-purple-400 hover:bg-purple-50/20 text-gray-700 transition">
            <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white flex items-center justify-center transition shrink-0">
                <i data-lucide="clipboard-check" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 group-hover:text-purple-700">Job Cards</p>
                <p class="text-xs text-gray-500">Workshop floor orders</p>
            </div>
        </a>
        <a href="/coordinator/inventory" class="group flex items-center gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-amber-400 hover:bg-amber-50/20 text-gray-700 transition">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white flex items-center justify-center transition shrink-0">
                <i data-lucide="scan-barcode" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 group-hover:text-amber-700">Inventory & Barcodes</p>
                <p class="text-xs text-gray-500">Check stock & scan</p>
            </div>
        </a>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
        <div>
            <h2 class="text-base font-bold text-gray-900 tracking-tight">Most Urgent Active Work Orders</h2>
            <p class="text-xs text-gray-500 mt-0.5">Prioritized by deadline and inactive check-in status</p>
        </div>
        <a href="/coordinator/job-cards" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">View all active jobs &rarr;</a>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                <th class="px-5 py-3.5">Vehicle</th>
                <th class="px-5 py-3.5">Assigned Staff</th>
                <th class="px-5 py-3.5">Current Stage</th>
                <th class="px-5 py-3.5">Target Deadline</th>
                <th class="px-5 py-3.5">Check-in Status</th>
                <th class="px-5 py-3.5 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($urgentJobs as $job)
            @php
                $overdue = $job->estimated_completion && $job->estimated_completion->isPast();
            @endphp
            <tr class="hover:bg-gray-50/60 transition {{ $job->is_stale ? 'bg-rose-50/30' : ($overdue ? 'bg-amber-50/30' : '') }}">
                <td class="px-5 py-4">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                        {{ $job->vehicle->plate_number ?? '—' }}
                    </span>
                    <p class="text-xs text-gray-500 mt-1">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</p>
                </td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-800">{{ $job->staff->name ?? 'Unassigned' }}</td>
                <td class="px-5 py-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                        {{ $job->current_stage === 'repairing' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                          ($job->current_stage === 'quality_check' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                          ($job->current_stage === 'waiting_parts' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                          'bg-amber-50 text-amber-700 border-amber-200')) }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $job->current_stage === 'repairing' ? 'bg-blue-500' : ($job->current_stage === 'quality_check' ? 'bg-purple-500' : ($job->current_stage === 'waiting_parts' ? 'bg-rose-500' : 'bg-amber-500')) }}"></span>
                        {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                    </span>
                </td>
                <td class="px-5 py-4 text-xs font-bold {{ $overdue ? 'text-rose-600' : 'text-gray-900' }}">
                    {{ $job->estimated_completion ? $job->estimated_completion->format('d M, H:i') : '—' }}
                </td>
                <td class="px-5 py-4">
                    @if($job->is_stale)
                    <span class="inline-flex items-center gap-1 text-xs bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 rounded-full font-bold">
                        <i data-lucide="alarm-clock" class="w-3.5 h-3.5 text-rose-500 animate-pulse"></i> {{ $job->hours_since_update }}h inactive
                    </span>
                    @else
                    <span class="text-xs font-medium text-gray-500">Updated {{ $job->hours_since_update }}h ago</span>
                    @endif
                </td>
                <td class="px-5 py-4 text-right">
                    <a href="/coordinator/job-cards/{{ $job->id }}" class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition">Open &rarr;</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-12 text-center">
                <x-empty-state icon="check-circle-2" title="No active jobs right now" subtitle="Everything on the shop floor is caught up." />
            </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection