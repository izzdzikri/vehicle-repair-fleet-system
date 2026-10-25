@extends('layouts.app')
@section('page-title', 'Prediction Proofreading & Validation')

@section('content')

<div x-data="{
    activeTab: 'audit',
    showAdjustModal: false,
    selectedAlert: null,
    overrideDate: '',
    overrideUrgency: 'medium',
    adminNotes: '',
    openAdjust(alert) {
        this.selectedAlert = alert;
        this.overrideDate = alert.effective_due_date ? alert.effective_due_date.substring(0, 10) : '';
        this.overrideUrgency = alert.effective_urgency || alert.urgency || 'medium';
        this.adminNotes = alert.admin_notes || '';
        this.showAdjustModal = true;
    }
}">

    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-purple-50 text-purple-700 border border-purple-200">
                    <i data-lucide="check-check" class="w-5 h-5"></i>
                </span>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Prediction Proofreading & Audit</h2>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Viva Evaluation Ready
                </span>
            </div>
            <p class="text-sm font-medium text-gray-500 mt-1">
                Inspect heuristic derivations, verify prediction reliability, review empirical backtesting, and connect demand forecasts to parts procurement.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <form method="POST" action="/admin/maintenance/predict">
                @csrf
                <button type="submit"
                    class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-800 flex items-center gap-2 shadow-xs transition">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Re-scan Predictions Now
                </button>
            </form>
        </div>
    </div>

    @if(session('error'))
    <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 flex-shrink-0"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if(empty($hasVerificationColumns))
    <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-3 shadow-xs">
        <div class="p-2 rounded-lg bg-amber-100 text-amber-800 flex-shrink-0">
            <i data-lucide="database" class="w-5 h-5"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-sm font-bold text-amber-900">Database Schema Migration Pending</h3>
            <p class="text-xs text-amber-700 mt-1">
                To activate persistent human verification timestamps, auditor signatures, and parameter adjustments in MySQL, run the pending migration in your terminal:
            </p>
            <div class="mt-2.5 flex items-center gap-2">
                <code class="px-3 py-1.5 rounded-lg bg-amber-100/90 font-mono text-xs font-bold text-amber-950 border border-amber-300">
                    php artisan migrate
                </code>
                <span class="text-[11px] text-amber-600 font-medium">Safe to run &bull; Creates verification audit columns</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Executive Telemetry & Backtest Scorecards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Active Predictions</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $stats['total_predictions'] }}</p>
            <div class="flex items-center gap-1.5 mt-1 text-xs">
                <span class="font-bold text-amber-600">{{ $stats['pending_count'] }} pending</span>
                <span class="text-gray-400">&bull;</span>
                <span class="font-bold text-emerald-600">{{ $stats['verified_count'] }} verified</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Backtested MAE Error</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="target" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-emerald-700 tracking-tight">
                {{ $backtest['current']['mae'] !== null ? '±' . $backtest['current']['mae'] : 'N/A' }} <span class="text-sm font-bold text-gray-500">days</span>
            </p>
            <span class="text-xs font-medium text-gray-500 mt-1 inline-block">
                {{ $backtest['current']['within_14'] ?? 0 }}% accurate within ±14 days
            </span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">60-Day Service Demand</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $forecast['horizon_counts']['60'] }}</p>
            <span class="text-xs font-medium text-purple-700 mt-1 inline-block font-bold">
                {{ $forecast['horizon_counts']['30'] }} due within 30 days
            </span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 font-bold">Off-Season Cost Savings</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="trending-down" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">
                RM {{ number_format($offSeasonSavings['total_savings'] ?? 0, 2) }}
            </p>
            <span class="text-xs font-bold text-emerald-700 mt-1 inline-block">
                Save {{ $offSeasonSavings['overall_savings_pct'] ?? 0 }}% via advance procurement
            </span>
        </div>
    </div>

    {{-- Main Workspace Navigation Tabs --}}
    <div class="flex items-center gap-2 mb-6 border-b border-gray-200 pb-3 flex-wrap">
        <button @click="activeTab = 'audit'"
            :class="activeTab === 'audit' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="clipboard-check" class="w-4 h-4"></i> Prediction Verification Ledger
        </button>
        <button @click="activeTab = 'forecast'"
            :class="activeTab === 'forecast' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="package-search" class="w-4 h-4"></i> Demand &amp; Off-Season Savings
            @if(isset($offSeasonSavings) && $offSeasonSavings['total_savings'] > 0)
            <span class="bg-emerald-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">
                Save RM {{ number_format($offSeasonSavings['total_savings'], 0) }}
            </span>
            @endif
        </button>
        <button @click="activeTab = 'backtest'"
            :class="activeTab === 'backtest' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Backtest Evaluation & Methodology
        </button>
    </div>

    {{-- TAB 1: PREDICTION VERIFICATION & PROOFREADING LEDGER --}}
    <div x-show="activeTab === 'audit'">
        {{-- Filter and search toolbar --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 mb-4 flex justify-between items-center flex-wrap gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <a href="/admin/predictions/proofread?filter=all"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ $statusFilter === 'all' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    All Predictions ({{ $stats['total_predictions'] }})
                </a>
                <a href="/admin/predictions/proofread?filter=pending"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    Needs Verification ({{ $stats['pending_count'] }})
                </a>
                <a href="/admin/predictions/proofread?filter=verified"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ $statusFilter === 'verified' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    Verified ({{ $stats['verified_count'] }})
                </a>
                <a href="/admin/predictions/proofread?filter=overridden"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ $statusFilter === 'overridden' ? 'bg-purple-600 text-white border-purple-600 shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    Adjusted / Overridden ({{ $stats['overridden_count'] }})
                </a>
            </div>

            <form method="GET" action="/admin/predictions/proofread" class="flex items-center gap-2">
                <input type="hidden" name="filter" value="{{ $statusFilter }}">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search plate or model..."
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold transition">
                    Search
                </button>
            </form>
        </div>

        {{-- Verification Table --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[950px]">
                    <thead class="bg-gray-50/75 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 px-4">Vehicle & Ownership</th>
                            <th class="py-3 px-4">Service Required</th>
                            <th class="py-3 px-4">Heuristic Derivation</th>
                            <th class="py-3 px-4">Predicted Due Date</th>
                            <th class="py-3 px-4">Verification State</th>
                            <th class="py-3 px-4 text-right">Human Audit Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($alerts as $alert)
                        @php
                            $v = $alert->vehicle;
                            $owner = $v?->owner;
                            $isFleet = $owner && $owner->role === 'corporate';
                            $company = $isFleet ? $owner->company : null;
                            $isOverridden = !empty($alert->overridden_due_date) || !empty($alert->overridden_urgency);
                            $effectiveDate = $alert->effective_due_date;
                            $effectiveUrgency = $alert->effective_urgency;
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition {{ $alert->is_verified ? 'bg-emerald-50/15' : '' }}">
                            {{-- Vehicle & Ownership Details --}}
                            <td class="py-3.5 px-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold bg-gray-100 text-gray-900 px-2 py-0.5 rounded border border-gray-200">
                                            {{ $v->plate_number ?? '—' }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-900">{{ $v->brand ?? '' }} {{ $v->model ?? '' }}</span>
                                    </div>
                                    <div>
                                        @if($isFleet)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">
                                            <i data-lucide="building" class="w-3 h-3"></i> Fleet: {{ $company->name ?? 'Corporate' }} (PIC: {{ $owner->name }})
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                            <i data-lucide="user" class="w-3 h-3"></i> Individual: {{ $owner->name ?? 'Customer' }}
                                        </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-gray-400 font-mono">Mileage: {{ number_format($v->mileage ?? 0) }} km</p>
                                </div>
                            </td>

                            {{-- Service Type --}}
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-gray-900 block">{{ $alert->jobType->name ?? $alert->alert_type }}</span>
                                <span class="text-xs text-gray-500 font-medium">Category: {{ $alert->jobType->category ?? 'General' }}</span>
                                @if($alert->jobType && $alert->jobType->isIntervalTracked())
                                <span class="block text-[11px] font-mono text-purple-700 mt-0.5">
                                    Cycle: {{ $alert->jobType->interval_label }}
                                </span>
                                @endif
                            </td>

                            {{-- Heuristic Derivation --}}
                            <td class="py-3.5 px-4">
                                <p class="text-xs text-gray-700 font-medium leading-relaxed max-w-xs">
                                    {{ $alert->recommendation }}
                                </p>
                            </td>

                            {{-- Predicted Due Date & Urgency --}}
                            <td class="py-3.5 px-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs font-bold text-gray-900">
                                            {{ $effectiveDate ? \Carbon\Carbon::parse($effectiveDate)->format('d M Y') : '—' }}
                                        </span>
                                        @if($isOverridden)
                                        <span class="text-[10px] bg-purple-100 text-purple-800 px-1.5 py-0.2 rounded font-bold">
                                            Adjusted
                                        </span>
                                        @endif
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold inline-block
                                        {{ $effectiveUrgency === 'high' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                                          ($effectiveUrgency === 'medium' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                                          'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                                        {{ ucfirst($effectiveUrgency) }} Urgency
                                    </span>
                                </div>
                            </td>

                            {{-- Verification State --}}
                            <td class="py-3.5 px-4">
                                @if($alert->is_verified)
                                <div class="space-y-0.5">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                                        <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i> Verified
                                    </span>
                                    <p class="text-[11px] text-gray-500 font-medium">
                                        by {{ $alert->verifier->name ?? 'Admin' }}
                                    </p>
                                    @if($alert->admin_notes)
                                    <p class="text-[11px] text-gray-600 italic max-w-xs">"{{ $alert->admin_notes }}"</p>
                                    @endif
                                </div>
                                @else
                                <div>
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                                        <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i> Pending Audit
                                    </span>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Unreviewed by human</p>
                                </div>
                                @endif
                            </td>

                            {{-- Action Buttons --}}
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(!$alert->is_verified)
                                    <form method="POST" action="/admin/predictions/{{ $alert->id }}/verify">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-xs transition"
                                            title="Confirm this prediction is accurate">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Verify
                                        </button>
                                    </form>
                                    @endif
                                    <button type="button"
                                        @click="openAdjust({{ json_encode([
                                            'id' => $alert->id,
                                            'plate' => $v->plate_number ?? '—',
                                            'job_name' => $alert->jobType->name ?? $alert->alert_type,
                                            'effective_due_date' => $effectiveDate ? \Carbon\Carbon::parse($effectiveDate)->format('Y-m-d') : '',
                                            'effective_urgency' => $effectiveUrgency,
                                            'urgency' => $alert->urgency,
                                            'admin_notes' => $alert->admin_notes ?? '',
                                        ]) }})"
                                        class="inline-flex items-center gap-1 border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition"
                                        title="Adjust parameters or override date">
                                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i> Adjust
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400">
                                <i data-lucide="shield-check" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                <p class="text-sm font-bold text-gray-700">No predictions match the selected filter</p>
                                <p class="text-xs text-gray-400 mt-0.5">All active predictions are reviewed or none fall inside this category.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($alerts->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                {{ $alerts->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- TAB 2: FLEET DEMAND & PROCUREMENT FORECAST --}}
    <div x-show="activeTab === 'forecast'" style="display:none">
        {{-- Horizon Breakdown Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-1">Immediate (Next 30 Days)</span>
                <p class="text-3xl font-extrabold text-rose-600">{{ $forecast['horizon_counts']['30'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Services requiring immediate workshop scheduling</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-1">Medium Term (Next 60 Days)</span>
                <p class="text-3xl font-extrabold text-amber-600">{{ $forecast['horizon_counts']['60'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Cumulative services expected in two months</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-1">Quarterly Horizon (Next 90 Days)</span>
                <p class="text-3xl font-extrabold text-blue-600">{{ $forecast['horizon_counts']['90'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Full quarterly maintenance demand forecast</p>
            </div>
        </div>

        {{-- OFF-SEASON SUPPLIER PRICING & DEMAND PREDICTION (ADMIN ONLY) --}}
        @if(isset($offSeasonSavings))
        <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-2xl border border-indigo-500/30 shadow-xl p-6 sm:p-8 mb-6 text-white relative overflow-hidden">
            {{-- Background decorative glows --}}
            <div class="absolute -right-20 -top-20 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex justify-between items-start flex-wrap gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Admin Off-Season Pricing Engine
                            </span>
                            <span class="text-xs font-bold text-indigo-300/80 px-2.5 py-1 rounded-full bg-white/5 border border-white/10">
                                {{ $offSeasonSavings['seasonal_cycle']['name'] }}
                            </span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight mt-2.5">
                            Predictive Parts Demand & Off-Season Cost Savings
                        </h3>
                        <p class="text-xs sm:text-sm text-indigo-100/80 mt-1 max-w-2xl leading-relaxed">
                            {{ $offSeasonSavings['seasonal_cycle']['description'] }} Pre-ordering replenishment ahead locks in early-bird supplier discounts (12%–20%) before seasonal spot price spikes.
                        </p>
                    </div>

                    <div class="text-right bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-200 block">Total Projected Savings</span>
                        <p class="text-3xl font-black text-emerald-400 font-mono mt-0.5">
                            RM {{ number_format($offSeasonSavings['total_savings'], 2) }}
                        </p>
                        <span class="text-xs font-bold text-emerald-300">
                            Save {{ $offSeasonSavings['overall_savings_pct'] }}% vs Spot Rush Orders
                        </span>
                    </div>
                </div>

                {{-- Metric Cards --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-6">
                    <div class="bg-white/5 rounded-xl p-3.5 border border-white/10">
                        <span class="text-[11px] font-semibold text-indigo-200 block">Off-Season Window Ends</span>
                        <p class="text-base font-extrabold text-white mt-1">{{ $offSeasonSavings['seasonal_cycle']['off_season_end'] }}</p>
                        <span class="text-[10px] text-amber-300 font-medium mt-0.5 block">Order prior to lock in rate</span>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3.5 border border-white/10">
                        <span class="text-[11px] font-semibold text-indigo-200 block">Peak Demand Surge</span>
                        <p class="text-base font-extrabold text-white mt-1">{{ $offSeasonSavings['seasonal_cycle']['peak_rush_start'] }}</p>
                        <span class="text-[10px] text-rose-300 font-medium mt-0.5 block">Expected rush period</span>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3.5 border border-white/10">
                        <span class="text-[11px] font-semibold text-indigo-200 block">Advance Off-Season Cost</span>
                        <p class="text-base font-extrabold text-emerald-300 font-mono mt-1">RM {{ number_format($offSeasonSavings['total_off_season_spend'], 2) }}</p>
                        <span class="text-[10px] text-indigo-300 font-medium mt-0.5 block">Locked-in bulk rate</span>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3.5 border border-white/10">
                        <span class="text-[11px] font-semibold text-indigo-200 block">Emergency Spot Cost</span>
                        <p class="text-base font-extrabold text-rose-300 font-mono mt-1">RM {{ number_format($offSeasonSavings['total_spot_spend'], 2) }}</p>
                        <span class="text-[10px] text-rose-300/80 font-medium mt-0.5 block">If bought on-demand at peak</span>
                    </div>
                </div>

                {{-- Analyzed Parts Savings Table --}}
                <div class="overflow-x-auto rounded-xl border border-white/15 bg-slate-900/60">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-white/10 border-b border-white/10 text-indigo-200 uppercase tracking-wider font-bold text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Component & Specification</th>
                                <th class="py-3 px-4">Seasonal Influx Driver</th>
                                <th class="py-3 px-4 text-center">Shelf Stock</th>
                                <th class="py-3 px-4 text-center">Predicted Need</th>
                                <th class="py-3 px-4 text-right">Regular Spot</th>
                                <th class="py-3 px-4 text-right">Off-Season Rate</th>
                                <th class="py-3 px-4 text-right">Potential Savings</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($offSeasonSavings['analyzed_parts'] as $item)
                            <tr class="hover:bg-white/5 transition">
                                <td class="py-3 px-4">
                                    <p class="font-bold text-white text-xs">{{ $item['part']->name }}</p>
                                    <span class="font-mono text-[11px] text-indigo-300 block">{{ $item['part']->part_number }} &bull; {{ $item['part']->brand ?? 'OEM' }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-block px-2 py-0.5 rounded bg-white/10 text-indigo-200 text-[11px] font-medium">
                                        {{ $item['seasonal_driver'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-gray-300">{{ $item['current_stock'] }}</td>
                                <td class="py-3 px-4 text-center font-bold text-blue-400 font-mono">{{ $item['procure_qty'] }} units</td>
                                <td class="py-3 px-4 text-right font-mono text-gray-400">RM {{ number_format($item['unit_spot_price'], 2) }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-300">
                                    RM {{ number_format($item['unit_off_season'], 2) }}
                                    <span class="text-[10px] text-emerald-400 block">-{{ $item['discount_percent'] }}%</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-mono font-black text-emerald-400 text-xs block">
                                        RM {{ number_format($item['savings'], 2) }}
                                    </span>
                                    <span class="text-[10px] text-indigo-300">Save {{ $item['savings_percent'] }}%</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form method="POST" action="/admin/predictions/draft-po">
                                        @csrf
                                        <input type="hidden" name="spare_part_id" value="{{ $item['part']->id }}">
                                        <input type="hidden" name="quantity" value="{{ $item['procure_qty'] }}">
                                        <input type="hidden" name="unit_cost" value="{{ $item['unit_off_season'] }}">
                                        <input type="hidden" name="is_off_season" value="1">
                                        <button type="submit"
                                            class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-1.5 rounded-lg text-xs shadow-xs transition inline-flex items-center gap-1.5"
                                            title="Auto-draft Purchase Order with locked-in off-season discount">
                                            <i data-lucide="tag" class="w-3.5 h-3.5"></i> Lock In &amp; Draft PO
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Inventory Shortfall & Procurement Auto-Draft Section --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6">
            <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Predictive Spare Parts Shortfall Analysis</h3>
                    <p class="text-xs font-medium text-gray-500 mt-0.5">
                        Identifies parts required by upcoming services where workshop inventory is insufficient.
                    </p>
                </div>
                <span class="text-xs font-bold px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full">
                    {{ count($forecast['shortfalls']) }} SKU Shortage(s)
                </span>
            </div>

            @if(count($forecast['shortfalls']) > 0)
            <div class="overflow-x-auto border border-gray-200/80 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/75 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 px-4">Part Description</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4 text-center">In Stock</th>
                            <th class="py-3 px-4 text-center">Predicted Demand</th>
                            <th class="py-3 px-4 text-center">Shortfall</th>
                            <th class="py-3 px-4 text-right">Est. Unit Price</th>
                            <th class="py-3 px-4 text-right">Total Reorder Cost</th>
                            <th class="py-3 px-4 text-right">Procurement Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($forecast['shortfalls'] as $item)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 font-bold text-gray-900">
                                {{ $item['part']->name }}
                                <span class="font-mono text-xs font-semibold text-gray-400 block">{{ $item['part']->part_number }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-gray-600">{{ $item['part']->category }}</td>
                            <td class="py-3 px-4 text-center font-bold text-gray-800">{{ $item['available'] }}</td>
                            <td class="py-3 px-4 text-center font-bold text-blue-700">{{ $item['required'] }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800">
                                    -{{ $item['shortage'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-gray-700">RM {{ number_format($item['unit_cost'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-rose-700">RM {{ number_format($item['total_cost'], 2) }}</td>
                            <td class="py-3 px-4 text-right">
                                <form method="POST" action="/admin/predictions/draft-po">
                                    @csrf
                                    <input type="hidden" name="spare_part_id" value="{{ $item['part']->id }}">
                                    <input type="hidden" name="quantity" value="{{ $item['shortage'] }}">
                                    <button type="submit"
                                        class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i> Auto-Draft PO
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-6 bg-emerald-50/60 border border-emerald-200 rounded-xl text-center">
                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-600 mx-auto mb-2"></i>
                <p class="text-sm font-bold text-emerald-900">Sufficient Parts Inventory</p>
                <p class="text-xs text-emerald-700 mt-0.5">All spare parts needed for forecasted services over the next 60 days are adequately stocked.</p>
            </div>
            @endif
        </div>

        {{-- Client Spend Projections for Budgeting --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Corporate Client Maintenance Budget Forecast</h3>
            <p class="text-xs font-medium text-gray-500 mb-4">
                Estimated maintenance expenditure per client organization for upcoming 60-day service cycle.
            </p>
            <div class="overflow-x-auto border border-gray-200/80 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/75 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 px-4">Client / Organization</th>
                            <th class="py-3 px-4">Account Type</th>
                            <th class="py-3 px-4 text-center">Services Due</th>
                            <th class="py-3 px-4 text-right">Projected Spend (Labor + Baseline)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($forecast['company_spend'] as $cs)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 font-bold text-gray-900">{{ $cs['name'] }}</td>
                            <td class="py-3 px-4">
                                @if($cs['is_corporate'])
                                <span class="text-xs font-bold px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-full">
                                    Corporate Fleet
                                </span>
                                @else
                                <span class="text-xs font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full">
                                    Individual
                                </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-gray-800">{{ $cs['count'] }} job(s)</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-700 font-mono">RM {{ number_format($cs['total_cost'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 3: BACKTEST EVALUATION & METHODOLOGY --}}
    <div x-show="activeTab === 'backtest'" style="display:none">
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6">
            <div class="flex items-center gap-2 mb-3">
                <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600 border border-blue-200">
                    <i data-lucide="flask-conical" class="w-4 h-4"></i>
                </span>
                <h3 class="text-lg font-bold text-gray-900">Backtesting Model Evaluation & Error Scorecard</h3>
            </div>
            <p class="text-xs text-gray-600 leading-relaxed mb-6">
                Rather than treating the predictive engine as an untested heuristic, the system runs historical backtests against real consecutive service records across the fleet.
                It projects service $N+1$ from service $N$ and measures the exact error in days ($\text{Error} = \text{Predicted} - \text{Actual}$).
            </p>

            {{-- Model Comparison Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="p-5 bg-emerald-50/50 border border-emerald-200 rounded-xl space-y-3">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-sm text-emerald-900">Current Adaptive Model (Multi-Tier + Recency)</h4>
                        <span class="text-xs bg-emerald-600 text-white font-bold px-2 py-0.5 rounded">Active Engine</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-white p-3 rounded-lg border border-emerald-100">
                            <span class="text-gray-500 font-medium block">Mean Absolute Error (MAE):</span>
                            <span class="text-xl font-extrabold text-emerald-700 font-mono mt-0.5 block">
                                ±{{ $backtest['current']['mae'] ?? 'N/A' }} days
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-emerald-100">
                            <span class="text-gray-500 font-medium block">Evaluation Test Pairs:</span>
                            <span class="text-xl font-extrabold text-gray-800 font-mono mt-0.5 block">
                                {{ $backtest['current']['n'] ?? 0 }} service pairs
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-emerald-100">
                            <span class="text-gray-500 font-medium block">Accurate within ±7 Days:</span>
                            <span class="text-base font-bold text-emerald-700 font-mono mt-0.5 block">
                                {{ $backtest['current']['within_7'] ?? 0 }}%
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-emerald-100">
                            <span class="text-gray-500 font-medium block">Accurate within ±14 Days:</span>
                            <span class="text-base font-bold text-emerald-700 font-mono mt-0.5 block">
                                {{ $backtest['current']['within_14'] ?? 0 }}%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-gray-50 border border-gray-200 rounded-xl space-y-3">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-sm text-gray-800">Static Baseline Model (Flat 40 km/day)</h4>
                        <span class="text-xs bg-gray-200 text-gray-700 font-bold px-2 py-0.5 rounded">Control Baseline</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <span class="text-gray-500 font-medium block">Mean Absolute Error (MAE):</span>
                            <span class="text-xl font-extrabold text-gray-700 font-mono mt-0.5 block">
                                ±{{ $backtest['baseline']['mae'] ?? 'N/A' }} days
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <span class="text-gray-500 font-medium block">Evaluation Test Pairs:</span>
                            <span class="text-xl font-extrabold text-gray-800 font-mono mt-0.5 block">
                                {{ $backtest['baseline']['n'] ?? 0 }} service pairs
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <span class="text-gray-500 font-medium block">Accurate within ±7 Days:</span>
                            <span class="text-base font-bold text-gray-700 font-mono mt-0.5 block">
                                {{ $backtest['baseline']['within_7'] ?? 0 }}%
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <span class="text-gray-500 font-medium block">Accurate within ±14 Days:</span>
                            <span class="text-base font-bold text-gray-700 font-mono mt-0.5 block">
                                {{ $backtest['baseline']['within_14'] ?? 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Breakdown by Job Type --}}
            @if(!empty($backtest['by_type']))
            <h4 class="font-bold text-sm text-gray-900 mb-3">Accuracy Breakdown by Service Job Type</h4>
            <div class="overflow-x-auto border border-gray-200/80 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/75 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 px-4">Service Type</th>
                            <th class="py-3 px-4 text-center">Historical Pairs</th>
                            <th class="py-3 px-4 text-right">MAE Error</th>
                            <th class="py-3 px-4 text-right">Prediction Bias</th>
                            <th class="py-3 px-4 text-right">Within ±7 Days</th>
                            <th class="py-3 px-4 text-right">Within ±14 Days</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($backtest['by_type'] as $typeName => $perf)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 font-bold text-gray-900">{{ $typeName }}</td>
                            <td class="py-3 px-4 text-center font-mono">{{ $perf['n'] }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">±{{ $perf['mae'] ?? '—' }} d</td>
                            <td class="py-3 px-4 text-right font-mono text-gray-600">{{ $perf['bias'] > 0 ? '+'.$perf['bias'] : $perf['bias'] }} d</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-gray-800">{{ $perf['within_7'] ?? 0 }}%</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-gray-800">{{ $perf['within_14'] ?? 0 }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- Adjust / Override Modal --}}
    <div x-show="showAdjustModal" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md p-6" @click.outside="showAdjustModal = false">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Adjust & Human-Verify Prediction</h3>
                    <p class="text-xs text-gray-500" x-text="selectedAlert ? selectedAlert.plate + ' — ' + selectedAlert.job_name : ''"></p>
                </div>
                <button @click="showAdjustModal = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="'/admin/predictions/' + (selectedAlert ? selectedAlert.id : '') + '/adjust'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Override Target Due Date</label>
                    <input type="date" name="overridden_due_date" x-model="overrideDate"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p class="text-[11px] text-gray-400 mt-1">Leave as-is to keep calculated date</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Override Urgency</label>
                    <select name="overridden_urgency" x-model="overrideUrgency"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="low">Low Urgency (Routine)</option>
                        <option value="medium">Medium Urgency (Due Soon)</option>
                        <option value="high">High Urgency (Action Required)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Audit Note / Proofreading Justification *</label>
                    <textarea name="admin_notes" rows="3" x-model="adminNotes"
                        placeholder="State reason for overriding (e.g. Verified with driver regarding upcoming 1,500km interstate logistics trip)..."
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAdjustModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-slate-900 text-white px-5 py-2 rounded-lg hover:bg-slate-800 text-xs font-bold shadow-xs transition">
                        Save & Verify
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
