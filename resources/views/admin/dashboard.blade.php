@extends('layouts.app')
@section('page-title', 'Admin Dashboard')

@section('content')

{{-- Stat Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="car" class="w-6 h-6 text-blue-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 mb-1">Total Vehicles</p>
        <p class="text-3xl font-bold text-blue-600">{{ $totalVehicles }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="users" class="w-6 h-6 text-purple-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 mb-1">Total Users</p>
        <p class="text-3xl font-bold text-purple-600">{{ $totalUsers }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="clipboard-list" class="w-6 h-6 text-yellow-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 mb-1">Pending Jobs</p>
        <p class="text-3xl font-bold text-yellow-500">{{ $pendingJobs }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="package" class="w-6 h-6 text-red-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 mb-1">Low Stock</p>
        <p class="text-3xl font-bold text-red-500">{{ $lowStock }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="calendar" class="w-6 h-6 text-green-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 mb-1">Today's Apts</p>
        <p class="text-3xl font-bold text-green-600">{{ $todayAppointments }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="clock" class="w-6 h-6 text-orange-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 mb-1">Pending Apts</p>
        <p class="text-3xl font-bold text-orange-500">{{ $pendingAppts }}</p>
        @if($pendingAppts > 0)
        <a href="/admin/appointments?status=pending" class="text-xs text-blue-600 hover:underline">Review →</a>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="alarm-clock" class="w-6 h-6 {{ $staleJobs > 0 ? 'text-red-500' : 'text-gray-400' }} mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 mb-1">Needs Status Update</p>
        <p class="text-3xl font-bold {{ $staleJobs > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $staleJobs }}</p>
        @if($staleJobs > 0)
        <a href="/admin/job-cards/schedule" class="text-xs text-blue-600 hover:underline">Check in →</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Recent Job Cards --}}
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Recent Job Cards</h2>
            <a href="/admin/job-cards" class="text-sm text-blue-600 hover:underline">View all →</a>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2">ID</th>
                    <th class="pb-2">Vehicle</th>
                    <th class="pb-2">Staff</th>
                    <th class="pb-2">Stage</th>
                    <th class="pb-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentJobs as $job)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2">#{{ $job->id }}</td>
                    <td class="py-2">{{ $job->vehicle->plate_number ?? '—' }}</td>
                    <td class="py-2">{{ $job->staff->name ?? '—' }}</td>
                    <td class="py-2">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $job->current_stage === 'completed'  ? 'bg-green-100 text-green-700' :
                              ($job->current_stage === 'repairing'  ? 'bg-blue-100 text-blue-700' :
                              'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                        </span>
                        @if($job->is_stale)
                        <span class="text-xs text-red-500" title="No update in {{ $job->hours_since_update }}h">⏰</span>
                        @endif
                    </td>
                    <td class="py-2">
                        <a href="/admin/job-cards/{{ $job->id }}" class="text-blue-600 hover:underline text-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-4 text-center text-gray-400">No job cards yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Staff Workload --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Staff Workload</h2>
        @forelse($staffWorkload as $s)
        <div class="mb-3">
            <div class="flex justify-between items-center mb-1">
                <span class="text-sm font-medium text-gray-700">{{ $s->name }}</span>
                <span class="text-xs {{ $s->active_jobs >= 5 ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                    {{ $s->active_jobs }} jobs
                </span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="h-2 rounded-full {{ $s->active_jobs >= 5 ? 'bg-red-500' : ($s->active_jobs >= 3 ? 'bg-yellow-400' : 'bg-green-400') }}"
                    style="width: {{ min(100, $s->active_jobs * 20) }}%"></div>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm">No staff accounts yet.</p>
        @endforelse
    </div>

</div>

{{-- Quick Actions --}}
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Quick Actions</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="/admin/appointments/queue" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="clock" class="w-5 h-5 text-blue-500"></i>
            <span class="text-xs font-medium text-gray-600">Today's Queue</span>
        </a>
        <a href="/admin/job-cards/schedule" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="list-ordered" class="w-5 h-5 text-purple-500"></i>
            <span class="text-xs font-medium text-gray-600">Job Schedule</span>
        </a>
        <a href="/admin/invoices" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="receipt" class="w-5 h-5 text-green-600"></i>
            <span class="text-xs font-medium text-gray-600">Invoices</span>
        </a>
        <a href="/admin/purchase-orders" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="shopping-cart" class="w-5 h-5 text-orange-500"></i>
            <span class="text-xs font-medium text-gray-600">Purchase Orders</span>
        </a>
        <a href="/admin/suppliers" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="truck" class="w-5 h-5 text-cyan-600"></i>
            <span class="text-xs font-medium text-gray-600">Suppliers</span>
        </a>
        <a href="/admin/staff-management/attendance" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="user-cog" class="w-5 h-5 text-red-500"></i>
            <span class="text-xs font-medium text-gray-600">Staff Mgmt</span>
        </a>
    </div>
</div>

@endsection