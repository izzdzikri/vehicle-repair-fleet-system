@extends('layouts.app')
@section('page-title', 'Reports & Analytics')

@section('content')

{{-- Top Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Revenue</p>
        <p class="text-2xl font-bold text-green-600">RM {{ number_format($totalRevenue, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Completed Jobs</p>
        <p class="text-2xl font-bold text-blue-600">{{ $completedJobs }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Vehicles</p>
        <p class="text-2xl font-bold text-purple-600">{{ $totalVehicles }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Customers</p>
        <p class="text-2xl font-bold text-orange-500">{{ $totalCustomers }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Monthly Revenue Chart --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Monthly Revenue (Last 6 Months)</h2>
        <canvas id="revenueChart" height="120"></canvas>
    </div>

    {{-- Job Cards by Stage --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Job Cards by Stage</h2>
        <canvas id="stageChart" height="120"></canvas>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Appointments Full Lifecycle --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Appointment Lifecycle</h2>
        <canvas id="apptChart" height="180"></canvas>
        <div class="mt-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-yellow-600">● Pending</span>
                <span class="font-semibold">{{ $apptPending }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-blue-600">● Confirmed</span>
                <span class="font-semibold">{{ $apptConfirmed }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-green-600">● Completed</span>
                <span class="font-semibold">{{ $apptCompleted }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-red-500">● Cancelled</span>
                <span class="font-semibold">{{ $apptCancelled }}</span>
            </div>
            <div class="border-t pt-2 mt-2">
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Walk-in</span>
                    <span class="font-semibold text-blue-600">{{ $walkinCount }}</span>
                </div>
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Booked online</span>
                    <span class="font-semibold">{{ $bookedCount }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Walk-in vs Booked doughnut --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Walk-in vs Booked</h2>
        <canvas id="walkinChart" height="180"></canvas>
        <div class="mt-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-blue-600">● Walk-in</span>
                <span class="font-semibold">{{ $walkinCount }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-purple-600">● Booked</span>
                <span class="font-semibold">{{ $bookedCount }}</span>
            </div>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            Low Stock Parts
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $lowStockParts->count() }} items</span>
        </h2>
        @forelse($lowStockParts as $part)
        <div class="flex justify-between items-center border-b py-2 text-sm">
            <div>
                <p class="font-medium">{{ $part->name }}</p>
                <p class="text-xs text-gray-400">{{ $part->brand ?? '' }} — {{ $part->part_number }}</p>
            </div>
            <div class="text-right">
                <p class="font-bold {{ $part->stock <= 0 ? 'text-red-600' : 'text-orange-500' }}">
                    {{ $part->stock }} left
                </p>
                <p class="text-xs text-gray-400">min: {{ $part->min_stock }}</p>
            </div>
        </div>
        @empty
        <p class="text-green-600 text-sm mt-2">✓ All parts are sufficiently stocked.</p>
        @endforelse
    </div>

</div>

{{-- Recent Completed Jobs --}}
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Recent Completed Jobs</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-2 pr-4">Job ID</th>
                <th class="pb-2 pr-4">Vehicle</th>
                <th class="pb-2 pr-4">Job Type</th>
                <th class="pb-2 pr-4">Staff</th>
                <th class="pb-2 pr-4">Cost</th>
                <th class="pb-2">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentCompleted as $job)
            <tr class="border-b hover:bg-gray-50">
                <td class="py-2 pr-4">#{{ $job->id }}</td>
                <td class="py-2 pr-4">{{ $job->vehicle->plate_number ?? '—' }}</td>
                <td class="py-2 pr-4">{{ $job->jobType->name ?? '—' }}</td>
                <td class="py-2 pr-4">{{ $job->staff->name ?? '—' }}</td>
                <td class="py-2 pr-4 font-medium text-green-700">RM {{ number_format($job->total_cost, 2) }}</td>
                <td class="py-2 text-gray-400">{{ $job->updated_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-4 text-center text-gray-400">No completed jobs yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
// Monthly Revenue
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($months) !!},
        datasets: [{
            label: 'Revenue (RM)',
            data: {!! json_encode($revenue) !!},
            borderColor: '#10B981',
            backgroundColor: 'rgba(16,185,129,0.1)',
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Job Cards by Stage
new Chart(document.getElementById('stageChart'), {
    type: 'bar',
    data: {
        labels: ['Received','Diagnosing','Waiting Parts','Repairing','QC','Completed'],
        datasets: [{
            label: 'Job Cards',
            data: {!! json_encode($stageCounts) !!},
            backgroundColor: ['#3B82F6','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#10B981'],
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Appointment Lifecycle (4 segments now: pending, confirmed, completed, cancelled)
new Chart(document.getElementById('apptChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending','Confirmed','Completed','Cancelled'],
        datasets: [{
            data: [{{ $apptPending }}, {{ $apptConfirmed }}, {{ $apptCompleted }}, {{ $apptCancelled }}],
            backgroundColor: ['#F59E0B','#3B82F6','#10B981','#EF4444'],
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } }
    }
});

// Walk-in vs Booked
new Chart(document.getElementById('walkinChart'), {
    type: 'doughnut',
    data: {
        labels: ['Walk-in','Booked'],
        datasets: [{
            data: [{{ $walkinCount }}, {{ $bookedCount }}],
            backgroundColor: ['#3B82F6','#8B5CF6'],
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } }
    }
});
</script>

@endsection
