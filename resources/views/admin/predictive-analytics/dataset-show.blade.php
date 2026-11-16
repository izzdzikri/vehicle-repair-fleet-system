@extends('layouts.app')
@section('page-title', 'Dataset: ' . $dataset->name)

@push('head')
<style>
    .badge-low    { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    .badge-medium { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
    .badge-high   { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    {{-- ── Breadcrumb & Action Header ──────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-gray-500 mb-2">
                    <a href="{{ route('admin.predictive-analytics.index', ['tab' => 'data']) }}" class="hover:text-indigo-600 transition flex items-center gap-1">
                        <i data-lucide="database" class="w-3.5 h-3.5 text-indigo-500"></i>
                        <span>ML Data Hub</span>
                    </a>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-400"></i>
                    <span class="text-gray-800 font-semibold truncate max-w-xs">{{ $dataset->name }}</span>
                </nav>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">
                        {{ $dataset->name }}
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border
                        @if($dataset->source_type === 'synthetic') badge-low
                        @elseif($dataset->source_type === 'kaggle_benchmark') bg-sky-100 text-sky-700 border-sky-200
                        @elseif($dataset->source_type === 'system_live') bg-violet-100 text-violet-700 border-violet-200
                        @else badge-medium @endif">
                        {{ $dataset->source_label }}
                    </span>
                    <span class="text-xs bg-gray-100 text-gray-700 font-bold px-2.5 py-0.5 rounded-full border border-gray-200">
                        {{ number_format($stats['total_rows']) }} Total Rows
                    </span>
                    <span class="text-xs bg-gray-100 text-gray-700 font-bold px-2.5 py-0.5 rounded-full border border-gray-200">
                        {{ count($headers) }} Features
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Uploaded {{ $dataset->created_at->format('M d, Y · H:i') }} ({{ $dataset->created_at->diffForHumans() }})
                    by <span class="font-semibold text-gray-700">{{ $dataset->uploader?->name ?? 'System Admin' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('admin.predictive-analytics.datasets.download', $dataset) }}"
                   class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 text-xs font-bold px-3.5 py-2.5 rounded-xl flex items-center gap-1.5 transition shadow-2xs">
                    <i data-lucide="download" class="w-4 h-4 text-gray-500"></i>
                    <span>Export CSV</span>
                </a>

                <a href="{{ route('admin.predictive-analytics.index', ['tab' => 'train', 'dataset_id' => $dataset->id]) }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-1.5 transition shadow-xs">
                    <i data-lucide="cpu" class="w-4 h-4 text-indigo-200"></i>
                    <span>Train Models with this Data →</span>
                </a>

                <a href="{{ route('admin.predictive-analytics.index', ['tab' => 'data']) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold px-3 py-2.5 rounded-xl transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ── Dataset Metrics KPI Strip ────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        {{-- Total Records --}}
        <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Records</span>
                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="database" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="text-2xl font-black text-gray-900">{{ number_format($stats['total_rows']) }}</div>
            <p class="text-xs text-gray-400 mt-0.5">Sample vehicle records</p>
        </div>

        {{-- High Urgency Count --}}
        <div class="bg-white rounded-xl border border-rose-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">High Urgency</span>
                <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <div class="text-2xl font-black text-rose-700">{{ number_format($stats['high_count']) }}</div>
                <span class="text-xs font-bold text-rose-600">
                    {{ $stats['total_rows'] > 0 ? round(($stats['high_count'] / $stats['total_rows']) * 100, 1) : 0 }}%
                </span>
            </div>
            <p class="text-xs text-rose-500 mt-0.5">Immediate intervention</p>
        </div>

        {{-- Medium Urgency Count --}}
        <div class="bg-white rounded-xl border border-amber-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Medium Urgency</span>
                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <div class="text-2xl font-black text-amber-700">{{ number_format($stats['medium_count']) }}</div>
                <span class="text-xs font-bold text-amber-600">
                    {{ $stats['total_rows'] > 0 ? round(($stats['medium_count'] / $stats['total_rows']) * 100, 1) : 0 }}%
                </span>
            </div>
            <p class="text-xs text-amber-600 mt-0.5">14-day preventative check</p>
        </div>

        {{-- Low Urgency Count --}}
        <div class="bg-white rounded-xl border border-emerald-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Low / Normal</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <div class="text-2xl font-black text-emerald-700">{{ number_format($stats['low_count']) }}</div>
                <span class="text-xs font-bold text-emerald-600">
                    {{ $stats['total_rows'] > 0 ? round(($stats['low_count'] / $stats['total_rows']) * 100, 1) : 0 }}%
                </span>
            </div>
            <p class="text-xs text-emerald-600 mt-0.5">Optimal health status</p>
        </div>

        {{-- Fleet Averages --}}
        <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-2xs col-span-2 sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Fleet Averages</span>
                <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="text-sm font-bold text-gray-900">
                {{ number_format($stats['avg_mileage']) }} <span class="text-xs font-normal text-gray-500">km mileage</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ $stats['avg_days'] }} days since service avg
            </div>
        </div>
    </div>

    {{-- ── Search & Filter Controls ────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4">
        <form action="{{ route('admin.predictive-analytics.datasets.show', $dataset) }}" method="GET" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-1 flex-wrap sm:flex-nowrap">
                {{-- Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search plate, vehicle ID, service type..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-300 outline-none">
                </div>

                {{-- Urgency Filter --}}
                <div class="w-36 shrink-0">
                    <select name="urgency" onchange="this.form.submit()"
                            class="w-full py-2 px-3 text-xs rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-300 outline-none">
                        <option value="">All Urgency Levels</option>
                        <option value="HIGH" {{ $urgency === 'HIGH' ? 'selected' : '' }}>🚨 HIGH Urgency</option>
                        <option value="MEDIUM" {{ $urgency === 'MEDIUM' ? 'selected' : '' }}>⚠️ MEDIUM Warning</option>
                        <option value="LOW" {{ $urgency === 'LOW' ? 'selected' : '' }}>✅ LOW Routine</option>
                    </select>
                </div>

                {{-- Fuel Filter --}}
                <div class="w-32 shrink-0">
                    <select name="fuel" onchange="this.form.submit()"
                            class="w-full py-2 px-3 text-xs rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-300 outline-none">
                        <option value="">All Fuel Types</option>
                        <option value="petrol" {{ $fuel === 'petrol' ? 'selected' : '' }}>Petrol</option>
                        <option value="diesel" {{ $fuel === 'diesel' ? 'selected' : '' }}>Diesel</option>
                        <option value="hybrid" {{ $fuel === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        <option value="electric" {{ $fuel === 'electric' ? 'selected' : '' }}>Electric</option>
                    </select>
                </div>

                {{-- Owner Filter --}}
                <div class="w-32 shrink-0">
                    <select name="owner" onchange="this.form.submit()"
                            class="w-full py-2 px-3 text-xs rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-300 outline-none">
                        <option value="">All Owners</option>
                        <option value="corporate" {{ $owner === 'corporate' ? 'selected' : '' }}>Corporate Fleet</option>
                        <option value="individual" {{ $owner === 'individual' ? 'selected' : '' }}>Individual Client</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                <button type="submit"
                        class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                    Filter
                </button>

                @if($search || $urgency || $fuel || $owner)
                <a href="{{ route('admin.predictive-analytics.datasets.show', $dataset) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold px-3 py-2 rounded-xl transition"
                   title="Clear filters">
                    Reset
                </a>
                @endif

                {{-- Rows Per Page --}}
                <div class="flex items-center gap-1.5 text-xs text-gray-500 pl-2 border-l border-gray-200">
                    <label class="whitespace-nowrap">Show:</label>
                    <select name="per_page" onchange="this.form.submit()"
                            class="py-1 px-2 text-xs rounded-lg border border-gray-200 focus:ring-1 focus:ring-indigo-300 outline-none">
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Full Tabular Dataset Grid ────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                <i data-lucide="table-properties" class="w-4 h-4 text-indigo-600"></i>
                Dataset Records Table
            </h3>
            <span class="text-xs text-gray-500 font-medium">
                Showing {{ $paginated->firstItem() ?? 0 }} to {{ $paginated->lastItem() ?? 0 }} of {{ number_format($paginated->total()) }} records
            </span>
        </div>

        @if($paginated->isEmpty())
        <div class="py-16 text-center text-gray-400">
            <i data-lucide="filter-x" class="w-10 h-10 mx-auto mb-2 opacity-30"></i>
            <p class="text-sm font-medium">No records match your search / filter criteria.</p>
            <a href="{{ route('admin.predictive-analytics.datasets.show', $dataset) }}"
               class="inline-block mt-3 text-xs text-indigo-600 font-bold hover:underline">
                Clear all filters
            </a>
        </div>
        @else
        <div class="overflow-x-auto max-h-[650px] relative">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-900 text-white font-bold uppercase tracking-wider sticky top-0 z-10 shadow-xs">
                    <tr>
                        <th class="px-3.5 py-3 text-center w-12">#</th>
                        <th class="px-3 py-3">Vehicle ID</th>
                        <th class="px-3 py-3">Plate No</th>
                        <th class="px-3.5 py-3">Service Type</th>
                        <th class="px-3 py-3 text-center">Urgency Target</th>
                        <th class="px-3 py-3 text-right">Km Since Service</th>
                        <th class="px-3 py-3 text-right">Days Since</th>
                        <th class="px-3 py-3 text-right">Interval Km</th>
                        <th class="px-3 py-3 text-right">Interval Mo.</th>
                        <th class="px-3 py-3 text-right">Daily Km</th>
                        <th class="px-3 py-3 text-right">Terrain Factor</th>
                        <th class="px-3 py-3 text-right">Total Mileage</th>
                        <th class="px-3 py-3 text-center">Vehicle Age</th>
                        <th class="px-3 py-3 text-center">Fuel</th>
                        <th class="px-3 py-3 text-center">Owner</th>
                        <th class="px-3 py-3 text-center">Scan Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono">
                    @foreach($paginated as $row)
                    @php
                        $u = strtoupper($row['urgency_label'] ?? '');
                        $badgeClass = match ($u) {
                            'HIGH'   => 'badge-high',
                            'MEDIUM' => 'badge-medium',
                            default  => 'badge-low',
                        };
                    @endphp
                    <tr class="hover:bg-indigo-50/40 transition">
                        <td class="px-3.5 py-2.5 text-center text-gray-400 font-sans text-2xs">{{ $row['_row_id'] }}</td>
                        <td class="px-3 py-2.5 font-bold text-gray-900">{{ $row['vehicle_id'] ?? '—' }}</td>
                        <td class="px-3 py-2.5 font-bold text-indigo-700 bg-indigo-50/40">{{ $row['plate_number'] ?? '—' }}</td>
                        <td class="px-3.5 py-2.5 font-sans font-medium text-gray-800">{{ $row['service_type'] ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded-full text-2xs font-bold border font-sans {{ $badgeClass }}">
                                {{ $u ?: 'LOW' }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-right font-medium {{ (float)($row['km_since_service'] ?? 0) > (float)($row['interval_km'] ?? 5000) ? 'text-rose-600 font-bold' : 'text-gray-700' }}">
                            {{ number_format((float)($row['km_since_service'] ?? 0)) }} km
                        </td>
                        <td class="px-3 py-2.5 text-right text-gray-700">
                            {{ number_format((float)($row['days_since_service'] ?? 0)) }} d
                        </td>
                        <td class="px-3 py-2.5 text-right text-gray-500">
                            {{ number_format((float)($row['interval_km'] ?? 0)) }} km
                        </td>
                        <td class="px-3 py-2.5 text-right text-gray-500">
                            {{ $row['interval_months'] ?? '—' }} m
                        </td>
                        <td class="px-3 py-2.5 text-right text-gray-700">
                            {{ number_format((float)($row['daily_km'] ?? 0), 1) }}
                        </td>
                        <td class="px-3 py-2.5 text-right font-sans text-2xs">
                            <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 font-bold">
                                {{ number_format((float)($row['terrain_severity'] ?? 1.0), 2) }}x
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-right text-gray-900 font-medium">
                            {{ number_format((float)($row['total_mileage'] ?? 0)) }} km
                        </td>
                        <td class="px-3 py-2.5 text-center text-gray-700">
                            {{ $row['vehicle_age'] ?? '—' }}y
                        </td>
                        <td class="px-3 py-2.5 text-center font-sans">
                            <span class="px-1.5 py-0.5 rounded text-2xs font-bold uppercase
                                @if(($row['fuel_type'] ?? '') === 'diesel') bg-amber-100 text-amber-800
                                @elseif(($row['fuel_type'] ?? '') === 'hybrid') bg-emerald-100 text-emerald-800
                                @elseif(($row['fuel_type'] ?? '') === 'electric') bg-sky-100 text-sky-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $row['fuel_type'] ?? 'petrol' }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-center font-sans">
                            <span class="px-1.5 py-0.5 rounded text-2xs font-bold capitalize
                                {{ ($row['owner_type'] ?? '') === 'corporate' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $row['owner_type'] ?? 'individual' }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-center text-gray-500 font-sans text-2xs">
                            {{ $row['scan_date'] ?? $row['year'] ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
            <span class="text-xs text-gray-500 font-medium">
                Page {{ $paginated->currentPage() }} of {{ $paginated->lastPage() }} ({{ number_format($paginated->total()) }} total records)
            </span>
            <div>
                {{ $paginated->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
