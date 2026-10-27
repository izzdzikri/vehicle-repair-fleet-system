@extends('layouts.app')
@section('page-title', 'Workshop Intelligence & Analytics')

@section('content')

{{-- Header & Controls --}}
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center p-1.5 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">
                <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
            </span>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Workshop Intelligence & Analytics</h2>
        </div>
        <p class="text-sm text-gray-500 mt-1">Operational throughput, financial ledger, bay utilization, and fleet account performance</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        {{-- Time Range Filters --}}
        <div class="inline-flex p-1 bg-gray-100 rounded-xl border border-gray-200 text-xs font-semibold">
            <a href="{{ route('admin.reports', ['period' => '30days']) }}"
               class="px-3 py-1.5 rounded-lg transition {{ $period === '30days' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                30 Days
            </a>
            <a href="{{ route('admin.reports', ['period' => '90days']) }}"
               class="px-3 py-1.5 rounded-lg transition {{ $period === '90days' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                90 Days
            </a>
            <a href="{{ route('admin.reports', ['period' => '6months']) }}"
               class="px-3 py-1.5 rounded-lg transition {{ $period === '6months' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                6 Months
            </a>
            <a href="{{ route('admin.reports', ['period' => 'ytd']) }}"
               class="px-3 py-1.5 rounded-lg transition {{ $period === 'ytd' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                YTD 2026
            </a>
            <a href="{{ route('admin.reports', ['period' => 'all']) }}"
               class="px-3 py-1.5 rounded-lg transition {{ $period === 'all' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                All Time
            </a>
        </div>

        {{-- Export Dropdown --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button @click="open = !open"
                    type="button"
                    class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Export CSV</span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80" :class="{ 'rotate-180': open }"></i>
            </button>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 text-left"
                 style="display: none;">
                <div class="px-3 py-1.5 border-b border-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    Workshop Data Exports (UTF-8 Excel)
                </div>

                <a href="{{ route('admin.reports.export', ['type' => 'summary', 'period' => $period]) }}"
                   class="flex items-start gap-3 px-4 py-2.5 hover:bg-gray-50 transition group">
                    <span class="p-2 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-100 transition mt-0.5">
                        <i data-lucide="file-bar-chart" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Executive Summary CSV</div>
                        <div class="text-[11px] text-gray-500">KPIs, monthly revenue trends, category share</div>
                    </div>
                </a>

                <a href="{{ route('admin.reports.export', ['type' => 'invoices', 'period' => $period]) }}"
                   class="flex items-start gap-3 px-4 py-2.5 hover:bg-gray-50 transition group">
                    <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 transition mt-0.5">
                        <i data-lucide="receipt" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Invoices & Financial Ledger CSV</div>
                        <div class="text-[11px] text-gray-500">Invoice #, tax, paid, balance, status & methods</div>
                    </div>
                </a>

                <a href="{{ route('admin.reports.export', ['type' => 'jobs', 'period' => $period]) }}"
                   class="flex items-start gap-3 px-4 py-2.5 hover:bg-gray-50 transition group">
                    <span class="p-2 rounded-lg bg-purple-50 text-purple-600 group-hover:bg-purple-100 transition mt-0.5">
                        <i data-lucide="wrench" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Work Orders & Job Cards CSV</div>
                        <div class="text-[11px] text-gray-500">Vehicles, lead tech, stages, parts/labour breakdown</div>
                    </div>
                </a>

                <a href="{{ route('admin.reports.export', ['type' => 'inventory']) }}"
                   class="flex items-start gap-3 px-4 py-2.5 hover:bg-gray-50 transition group">
                    <span class="p-2 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100 transition mt-0.5">
                        <i data-lucide="package" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Spare Parts Inventory & Valuation CSV</div>
                        <div class="text-[11px] text-gray-500">SKUs, on-hand counts, thresholds & total valuation</div>
                    </div>
                </a>

                <a href="{{ route('admin.reports.export', ['type' => 'fleet', 'period' => $period]) }}"
                   class="flex items-start gap-3 px-4 py-2.5 hover:bg-gray-50 transition group">
                    <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100 transition mt-0.5">
                        <i data-lucide="truck" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Fleet Maintenance History CSV</div>
                        <div class="text-[11px] text-gray-500">Service dates, fleet client spend, mileage & costs</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Top KPI Cards Grid --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    {{-- Gross Revenue --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Gross Revenue</span>
            <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg"><i data-lucide="dollar-sign" class="w-4 h-4"></i></span>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($totalRevenue, 2) }}</div>
            <p class="text-[11px] text-gray-500 mt-1 flex items-center gap-1">
                <span class="font-semibold text-emerald-600">RM {{ number_format($allTimeRevenue, 2) }}</span> all-time
            </p>
        </div>
    </div>

    {{-- Collection Rate --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Collection Rate</span>
            <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="check-check" class="w-4 h-4"></i></span>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $collectionRate }}%</div>
            <p class="text-[11px] text-gray-500 mt-1">
                RM {{ number_format($totalCollected, 0) }} / RM {{ number_format($totalInvoiced, 0) }}
            </p>
        </div>
    </div>

    {{-- Receivables Outstanding --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Outstanding</span>
            <span class="p-1.5 {{ $totalOutstanding > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400' }} rounded-lg">
                <i data-lucide="clock" class="w-4 h-4"></i>
            </span>
        </div>
        <div>
            <div class="text-2xl font-extrabold {{ $totalOutstanding > 0 ? 'text-amber-600' : 'text-gray-900' }} tracking-tight">
                RM {{ number_format($totalOutstanding, 2) }}
            </div>
            <p class="text-[11px] text-gray-500 mt-1">
                {{ $unpaidInvoicesCount }} unpaid &bull; {{ $partialInvoicesCount }} partial
            </p>
        </div>
    </div>

    {{-- Completed Orders --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Completed Jobs</span>
            <span class="p-1.5 bg-purple-50 text-purple-600 rounded-lg"><i data-lucide="wrench" class="w-4 h-4"></i></span>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $completedJobs }}</div>
            <p class="text-[11px] text-gray-500 mt-1">
                <span class="font-semibold text-purple-700">{{ $allTimeCompleted }}</span> total delivered
            </p>
        </div>
    </div>

    {{-- Average Ticket Size --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Average Ticket</span>
            <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg"><i data-lucide="tag" class="w-4 h-4"></i></span>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($avgJobValue, 2) }}</div>
            <p class="text-[11px] text-gray-500 mt-1">Avg cost per repair order</p>
        </div>
    </div>

    {{-- Turnaround Time --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Avg Turnaround</span>
            <span class="p-1.5 bg-cyan-50 text-cyan-600 rounded-lg"><i data-lucide="timer" class="w-4 h-4"></i></span>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $avgTurnaroundHours }} hrs</div>
            <p class="text-[11px] text-gray-500 mt-1">Intake to QC completion</p>
        </div>
    </div>
</div>

{{-- Main Visualizations (Row 1) --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    {{-- Monthly Revenue Trend (2 cols) --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-gray-900 tracking-tight">Monthly Revenue Inflow & Workload</h3>
                <p class="text-xs text-gray-500 mt-0.5">Historical monthly billed workshop revenue (RM) and completed job cards</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5 font-semibold text-emerald-700">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Billed Revenue (RM)
                </span>
                <span class="flex items-center gap-1.5 font-semibold text-blue-700">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Work Orders
                </span>
            </div>
        </div>
        <div class="relative h-64 w-full">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Revenue by Service Category (1 col) --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
        <div class="mb-4">
            <h3 class="text-base font-bold text-gray-900 tracking-tight">Revenue by Service Category</h3>
            <p class="text-xs text-gray-500 mt-0.5">Share of total completed work order revenue</p>
        </div>

        <div class="space-y-3.5 my-auto">
            @forelse($categoryBreakdown as $cat)
            <div>
                <div class="flex justify-between items-center text-xs mb-1">
                    <span class="font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" style="background-color: {{ match($cat->category) {
                            'Engine' => '#3B82F6',
                            'Brakes' => '#EF4444',
                            'Suspension' => '#8B5CF6',
                            'Cooling' => '#06B6D4',
                            'Electrical' => '#F59E0B',
                            'Transmission' => '#10B981',
                            'Tyres' => '#64748B',
                            default => '#6B7280',
                        } }}"></span>
                        {{ $cat->category }}
                    </span>
                    <span class="text-gray-500 font-medium">
                        {{ $cat->jobs_count }} jobs &bull; <strong class="text-gray-900 font-bold">RM {{ number_format($cat->total_revenue, 2) }}</strong> ({{ $cat->percentage }}%)
                    </span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="h-2 rounded-full transition-all duration-500"
                         style="width: {{ $cat->percentage }}%; background-color: {{ match($cat->category) {
                            'Engine' => '#3B82F6',
                            'Brakes' => '#EF4444',
                            'Suspension' => '#8B5CF6',
                            'Cooling' => '#06B6D4',
                            'Electrical' => '#F59E0B',
                            'Transmission' => '#10B981',
                            'Tyres' => '#64748B',
                            default => '#6B7280',
                         } }}"></div>
                </div>
            </div>
            @empty
            <div class="text-center py-6 text-gray-400 text-xs">No completed category data in this period.</div>
            @endforelse
        </div>

        <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 mt-2">
            <span>Serviced Categories</span>
            <span class="font-bold text-gray-900">{{ $categoryBreakdown->count() }} active categories</span>
        </div>
    </div>
</div>

{{-- Secondary Visualizations (Row 2: Pipeline, Channels, Inventory) --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    {{-- Workshop Bay Pipeline --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-gray-900 tracking-tight">Active Workshop Pipeline</h3>
                <p class="text-xs text-gray-500 mt-0.5">Live work orders across workflow stages</p>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                {{ array_sum(array_slice($stageCounts, 0, 5)) }} In Bay
            </span>
        </div>
        <div class="relative h-48 w-full mb-3">
            <canvas id="stageChart"></canvas>
        </div>
        <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-100 text-center">
            <div class="p-2 rounded-lg bg-gray-50">
                <p class="text-[10px] font-bold uppercase text-gray-400">Intake / Diag</p>
                <p class="text-sm font-extrabold text-blue-600">{{ ($stageCounts['received'] ?? 0) + ($stageCounts['diagnosing'] ?? 0) }}</p>
            </div>
            <div class="p-2 rounded-lg bg-gray-50">
                <p class="text-[10px] font-bold uppercase text-gray-400">Under Repair</p>
                <p class="text-sm font-extrabold text-purple-600">{{ ($stageCounts['waiting_parts'] ?? 0) + ($stageCounts['repairing'] ?? 0) }}</p>
            </div>
            <div class="p-2 rounded-lg bg-gray-50">
                <p class="text-[10px] font-bold uppercase text-gray-400">QC / Delivered</p>
                <p class="text-sm font-extrabold text-emerald-600">{{ ($stageCounts['quality_check'] ?? 0) + ($stageCounts['completed'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Intake Channels & Lifecycle --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 tracking-tight">Intake & Bookings</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Walk-in vs pre-booked appointment split</p>
                </div>
            </div>
            <div class="relative h-44 w-full mb-3">
                <canvas id="walkinChart"></canvas>
            </div>
        </div>

        <div class="space-y-2.5 pt-3 border-t border-gray-100 text-xs">
            <div class="flex justify-between items-center">
                <span class="flex items-center gap-1.5 text-blue-600 font-semibold">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Walk-in Check-in
                </span>
                <span class="font-bold text-gray-900">{{ $walkinCount }} bookings</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center gap-1.5 text-purple-600 font-semibold">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Online Pre-booked
                </span>
                <span class="font-bold text-gray-900">{{ $bookedCount }} bookings</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-gray-50 text-[11px] text-gray-500">
                <span>Fleet vs Retail Vehicles</span>
                <span class="font-bold text-indigo-700">{{ $fleetVehiclesCount }} Fleet / {{ $individualVehiclesCount }} Retail</span>
            </div>
        </div>
    </div>

    {{-- Low Stock Warning & Inventory Valuation --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
        <div>
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 tracking-tight">Stock Warning Parts</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Items requiring warehouse reorder</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                    {{ $lowStockParts->count() }} items
                </span>
            </div>

            <div class="divide-y divide-gray-100 max-h-56 overflow-y-auto pr-1">
                @forelse($lowStockParts as $part)
                <div class="flex justify-between items-center py-2.5 text-xs">
                    <div>
                        <p class="font-bold text-gray-900">{{ $part->name }}</p>
                        <p class="text-gray-500 text-[11px] mt-0.5 font-mono">{{ $part->part_number }} &bull; min: {{ $part->min_stock }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-bold {{ $part->stock <= 0 ? 'text-rose-600 bg-rose-50 border border-rose-200' : 'text-amber-700 bg-amber-50 border border-amber-200' }} px-2 py-0.5 rounded text-[11px]">
                            {{ $part->stock }} in stock
                        </span>
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-emerald-700 text-xs font-semibold">
                    <i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-1 text-emerald-500"></i>
                    All parts are sufficiently stocked.
                </div>
                @endforelse
            </div>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
            <span>Total Inventory Value</span>
            <span class="font-bold text-gray-900">RM {{ number_format($totalInventoryVal, 2) }}</span>
        </div>
    </div>
</div>

{{-- Deep Dive Tables: Technician Leaderboard & Top Fleet Accounts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Technician Productivity Leaderboard --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-gray-900 tracking-tight">Technician Productivity Leaderboard</h3>
                <p class="text-xs text-gray-500 mt-0.5">Performance rankings across workshop technical staff</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-700">
                {{ $technicianLeaderboard->count() }} Mechanics
            </span>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($technicianLeaderboard as $index => $tech)
            <div class="py-3 flex items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs {{ $index === 0 ? 'bg-amber-100 text-amber-700' : ($index === 1 ? 'bg-slate-200 text-slate-700' : 'bg-gray-100 text-gray-500') }}">
                        {{ $index + 1 }}
                    </span>
                    <div>
                        <p class="font-bold text-sm text-gray-900">{{ $tech->name }}</p>
                        <p class="text-[11px] text-gray-500">{{ $tech->staff_role ?? 'Mechanic' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 text-right">
                    <div>
                        <p class="font-bold text-gray-900">{{ $tech->completed_count }} jobs</p>
                        <p class="text-[11px] text-blue-600 font-semibold">{{ $tech->active_count }} in progress</p>
                    </div>
                    <div class="min-w-20">
                        <p class="font-extrabold text-emerald-700 text-sm">RM {{ number_format($tech->revenue_generated ?? 0, 2) }}</p>
                        <p class="text-[10px] text-gray-400 uppercase">Revenue</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="py-6 text-center text-gray-400 text-xs">No technician records found.</div>
            @endforelse
        </div>
    </div>

    {{-- Corporate Fleet Client Performance --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-gray-900 tracking-tight">Corporate Fleet Client Performance</h3>
                <p class="text-xs text-gray-500 mt-0.5">Key fleet contracts, maintenance volume, and total expenditure</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ $topFleetClients->count() }} Accounts
            </span>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($topFleetClients as $fleet)
            <div class="py-3 flex items-center justify-between gap-3 text-xs">
                <div>
                    <p class="font-bold text-sm text-gray-900">{{ $fleet->name }}</p>
                    <p class="text-[11px] text-gray-500 flex items-center gap-2 mt-0.5">
                        <span>PIC: {{ $fleet->person_in_charge }}</span>
                        <span>&bull;</span>
                        <span class="font-mono text-gray-400">{{ $fleet->phone }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-4 text-right">
                    <div>
                        <p class="font-bold text-gray-900">{{ $fleet->vehicles_count }} vehicles</p>
                        <p class="text-[11px] text-purple-600 font-semibold">{{ $fleet->jobs_count }} serviced</p>
                    </div>
                    <div class="min-w-24">
                        <p class="font-extrabold text-indigo-900 text-sm">RM {{ number_format($fleet->total_spend, 2) }}</p>
                        <p class="text-[10px] text-gray-400 uppercase">Total Spend</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="py-6 text-center text-gray-400 text-xs">No fleet accounts registered.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Recently Completed Repair Orders --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-base font-bold text-gray-900 tracking-tight">Recently Completed Repair Orders</h3>
            <p class="text-xs text-gray-500 mt-0.5">Finalized workshop repair records with vehicle, mechanic, and billing details</p>
        </div>
        <a href="{{ route('admin.reports.export', ['type' => 'jobs', 'period' => $period]) }}"
           class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition flex items-center gap-1.5">
            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export Full Jobs Ledger
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <th class="py-3 px-3">Job ID</th>
                    <th class="py-3 px-3">Vehicle Plate</th>
                    <th class="py-3 px-3">Client / Owner</th>
                    <th class="py-3 px-3">Service Type</th>
                    <th class="py-3 px-3">Lead Mechanic</th>
                    <th class="py-3 px-3">Total Amount</th>
                    <th class="py-3 px-3">Payment</th>
                    <th class="py-3 px-3 text-right">Completed On</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($recentCompleted as $job)
                <tr class="hover:bg-gray-50/60 transition text-sm">
                    <td class="py-3 px-3 font-mono text-xs font-bold text-gray-500">
                        <a href="/admin/job-cards/{{ $job->id }}" class="hover:text-blue-600 underline decoration-dotted">
                            #JC-{{ str_pad($job->id, 4, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td class="py-3 px-3">
                        <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                            {{ $job->vehicle->plate_number ?? '—' }}
                        </span>
                        <span class="block text-[11px] text-gray-500 mt-0.5">{{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}</span>
                    </td>
                    <td class="py-3 px-3">
                        <p class="font-semibold text-xs text-gray-900">{{ $job->vehicle?->owner?->company?->name ?? $job->vehicle?->owner?->name ?? 'Walk-in' }}</p>
                        <p class="text-[10px] text-gray-400">{{ $job->vehicle?->isFleet() ? 'Corporate Fleet' : 'Retail Customer' }}</p>
                    </td>
                    <td class="py-3 px-3">
                        <span class="font-semibold text-xs text-gray-800">{{ $job->jobType->name ?? '—' }}</span>
                        <span class="block text-[10px] text-gray-400">{{ $job->jobType->category ?? '' }}</span>
                    </td>
                    <td class="py-3 px-3 text-xs text-gray-700 font-medium">{{ $job->staff->name ?? '—' }}</td>
                    <td class="py-3 px-3 font-bold text-gray-900 text-xs">
                        RM {{ number_format($job->total_cost, 2) }}
                    </td>
                    <td class="py-3 px-3">
                        @if($job->invoice)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($job->invoice->status) {
                                'paid' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                'partial' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                default => 'bg-rose-50 text-rose-700 border border-rose-200',
                            } }}">
                                {{ $job->invoice->status }}
                            </span>
                        @else
                            <span class="text-gray-400 text-[10px]">No invoice</span>
                        @endif
                    </td>
                    <td class="py-3 px-3 text-right text-xs font-medium text-gray-500">
                        {{ $job->completed_at ? $job->completed_at->format('d M Y') : $job->updated_at->format('d M Y') }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="py-8 text-center text-gray-400 text-xs">No completed jobs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Chart.js Initialization --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Monthly Revenue & Orders Dual-Axis Chart
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        new Chart(revCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($months) !!},
                datasets: [
                    {
                        label: 'Revenue (RM)',
                        data: {!! json_encode($revenue) !!},
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#10B981',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Work Orders',
                        data: {!! json_encode($monthlyJobs) !!},
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        fill: false,
                        tension: 0.35,
                        pointBackgroundColor: '#3B82F6',
                        pointHoverRadius: 5,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return ' Revenue: RM ' + Number(context.raw).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return ' Work Orders: ' + context.raw + ' jobs';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return 'RM ' + val; }
                        },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: {
                            stepSize: 1,
                            callback: function(val) { return val + ' jobs'; }
                        }
                    }
                }
            }
        });
    }

    // 2. Active Stage Distribution Bar Chart
    const stageCtx = document.getElementById('stageChart');
    if (stageCtx) {
        new Chart(stageCtx, {
            type: 'bar',
            data: {
                labels: ['Received', 'Diagnosing', 'Waiting Parts', 'Repairing', 'QC', 'Completed'],
                datasets: [{
                    label: 'Job Cards',
                    data: [
                        {{ $stageCounts['received'] ?? 0 }},
                        {{ $stageCounts['diagnosing'] ?? 0 }},
                        {{ $stageCounts['waiting_parts'] ?? 0 }},
                        {{ $stageCounts['repairing'] ?? 0 }},
                        {{ $stageCounts['quality_check'] ?? 0 }},
                        {{ $stageCounts['completed'] ?? 0 }}
                    ],
                    backgroundColor: ['#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#10B981'],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    }
                }
            }
        });
    }

    // 3. Walk-in vs Booked Intake Channels Donut Chart
    const walkinCtx = document.getElementById('walkinChart');
    if (walkinCtx) {
        new Chart(walkinCtx, {
            type: 'doughnut',
            data: {
                labels: ['Walk-in Check-in', 'Online Pre-booked'],
                datasets: [{
                    data: [{{ $walkinCount }}, {{ $bookedCount }}],
                    backgroundColor: ['#3B82F6', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@endsection
