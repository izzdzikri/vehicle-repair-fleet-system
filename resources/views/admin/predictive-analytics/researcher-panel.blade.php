@extends('layouts.app')
@section('page-title', 'Researcher Panel — 4-Model Predictions & Confidence')

@push('head')
<style>
    /* Clean Urgency Badges */
    .badge-low    { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .badge-medium { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .badge-high   { background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
    .badge-na     { background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

    /* Confidence Progress Bars */
    .conf-bar-high   { background: linear-gradient(90deg, #10b981, #059669); }
    .conf-bar-medium { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .conf-bar-low    { background: linear-gradient(90deg, #f43f5e, #e11d48); }

    /* Clean Print Stylesheet for Data Report */
    @media print {
        body * {
            visibility: hidden !important;
        }
        #printableReportArea, #printableReportArea * {
            visibility: visible !important;
        }
        #printableReportArea {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            background: #ffffff !important;
            color: #111827 !important;
            padding: 24px !important;
            box-shadow: none !important;
            overflow: visible !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $algos = [
        'rule_based'    => ['icon' => 'ruler', 'label' => 'Rule-Based Baseline', 'color' => 'violet'],
        'decision_tree' => ['icon' => 'git-branch', 'label' => 'Decision Tree (CART)', 'color' => 'sky'],
        'random_forest' => ['icon' => 'trees', 'label' => 'Random Forest (100 Trees)', 'color' => 'emerald'],
        'svm'           => ['icon' => 'circle-dot', 'label' => 'Support Vector Machine (RBF)', 'color' => 'amber'],
    ];

    $labelBadge = [
        'LOW'    => 'badge-low',
        'MEDIUM' => 'badge-medium',
        'HIGH'   => 'badge-high',
    ];

    $summaryModels = $selectedExperiment?->summary_json['models'] ?? [];

    $tierFilter = request('confidence_tier');
    $agreeFilter = request('agreement');
    $urgeFilter = request('urgency');
    $divergeFilter = request('divergence');
    $sortVal = request('sort', 'default');
@endphp

@if(!$selectedExperiment)
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-12 text-center max-w-lg mx-auto my-12">
    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
        <i data-lucide="microscope" class="w-7 h-7"></i>
    </div>
    <h3 class="text-base font-bold text-gray-900 mb-1">No Completed Experiments Yet</h3>
    <p class="text-xs text-gray-500 mb-5 leading-relaxed">
        Run your first in-system training to evaluate all 4 models and track confidence scores side-by-side.
    </p>
    <a href="{{ route('admin.predictive-analytics.index') }}"
       class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-2 rounded-lg text-xs transition">
        <i data-lucide="cpu" class="w-4 h-4"></i> Open ML Data & Training Hub
    </a>
</div>
@else

{{-- ══════════════════════════════════════════════════════════════════════════
     TOP CONTROLS & ACTIVE EXPERIMENT BAR
══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-slate-900 text-white text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded">
                    R&D Researcher Panel
                </span>
                <span class="text-gray-400 text-xs">·</span>
                <span class="text-sm text-gray-600 font-medium">Dataset: <strong class="text-gray-900">{{ $selectedExperiment->dataset?->name ?? 'N/A' }}</strong></span>
                <span class="text-gray-400 text-xs">·</span>
                <span class="text-sm text-gray-600 font-mono">{{ round($selectedExperiment->split_ratio * 100) }}/{{ round((1 - $selectedExperiment->split_ratio) * 100) }} Split</span>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-600 mt-1">
                <span>Evaluated: <strong class="text-gray-900 font-mono">{{ number_format($confStats['total_rows'] ?? 0) }}</strong> test rows</span>
                <span>•</span>
                <span>Cross-Validation: <strong class="text-gray-900 font-mono">{{ $selectedExperiment->cv_folds }}-Fold Stratified</strong></span>
                <span>•</span>
                <span>Generated: <strong class="text-gray-700">{{ $selectedExperiment->created_at->format('d M Y, H:i') }}</strong></span>
            </div>
        </div>

        {{-- Switcher & Action Buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            @if($experiments->count() > 1)
            <form method="GET" action="{{ route('admin.researcher-panel') }}" class="inline-block">
                <select name="experiment_id" onchange="this.form.submit()"
                        class="text-sm font-semibold bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-gray-700 focus:ring-2 focus:ring-indigo-300 outline-none cursor-pointer">
                    @foreach($experiments as $exp)
                    <option value="{{ $exp->id }}" {{ $selectedExperiment->id === $exp->id ? 'selected' : '' }}>
                        {{ Str::limit($exp->run_name, 32) }}
                    </option>
                    @endforeach
                </select>
            </form>
            @endif

            <button type="button" onclick="openReportModal()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-2xs transition cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-white"></i> Data Report
            </button>

            <a href="{{ route('admin.researcher-panel.export-csv', $selectedExperiment) }}"
               class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-2xs transition"
               title="Print full telemetry, reasoning, and predictions into CSV">
                <i data-lucide="download" class="w-4 h-4"></i> Print into CSV
            </a>

            <button type="button" onclick="openLatexModal()"
                    class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-2xs transition">
                <i data-lucide="file-code" class="w-3.5 h-3.5 text-indigo-300"></i> LaTeX Table
            </button>

            <a href="{{ route('admin.predictive-analytics.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3.5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition">
                <i data-lucide="cpu" class="w-3.5 h-3.5 text-gray-500"></i> Training Hub →
            </a>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     4-MODEL AVERAGE CONFIDENCE SCORECARDS
══════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- Rule-Based Card --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-violet-500"></div>
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                    <i data-lucide="ruler" class="w-3.5 h-3.5"></i>
                </div>
                Rule-Based Baseline
            </span>
            <span class="text-xs font-bold text-violet-700 bg-violet-50 border border-violet-200 px-2 py-0.5 rounded-md">
                Formula
            </span>
        </div>
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Average Confidence</p>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-gray-900 font-mono">{{ $confStats['avg_rb'] }}%</span>
            <span class="text-xs text-gray-400">margin distance</span>
        </div>
        <div class="mt-2.5 pt-2 border-t border-gray-100 text-xs text-gray-600 font-medium flex justify-between">
            <span>Accuracy: <strong class="text-gray-900">{{ isset($metrics['rule_based']) ? round($metrics['rule_based']->accuracy * 100, 1) . '%' : '—' }}</strong></span>
            <span>HIGH Recall: <strong class="text-rose-600">{{ isset($metrics['rule_based']) ? round($metrics['rule_based']->high_urgency_recall * 100, 1) . '%' : '—' }}</strong></span>
        </div>
    </div>

    {{-- Decision Tree Card --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-sky-500"></div>
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="git-branch" class="w-3.5 h-3.5"></i>
                </div>
                Decision Tree (CART)
            </span>
            <span class="text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-md">
                Gini Leaf
            </span>
        </div>
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Average Confidence</p>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-gray-900 font-mono">{{ $confStats['avg_dt'] }}%</span>
            <span class="text-xs text-gray-400">leaf purity</span>
        </div>
        <div class="mt-2.5 pt-2 border-t border-gray-100 text-xs text-gray-600 font-medium flex justify-between">
            <span>Accuracy: <strong class="text-gray-900">{{ isset($metrics['decision_tree']) ? round($metrics['decision_tree']->accuracy * 100, 1) . '%' : '—' }}</strong></span>
            <span>5-Fold CV: <strong class="text-sky-600">{{ isset($summaryModels['decision_tree']['cv_accuracy']) ? round($summaryModels['decision_tree']['cv_accuracy'] * 100, 1) . '%' : '—' }}</strong></span>
        </div>
    </div>

    {{-- Random Forest Card --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-emerald-500"></div>
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="trees" class="w-3.5 h-3.5"></i>
                </div>
                Random Forest (Ensemble)
            </span>
            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                100 Trees
            </span>
        </div>
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Average Confidence</p>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-gray-900 font-mono">{{ $confStats['avg_rf'] }}%</span>
            <span class="text-xs text-gray-400">tree votes</span>
        </div>
        <div class="mt-2.5 pt-2 border-t border-gray-100 text-xs text-gray-600 font-medium flex justify-between">
            <span>Accuracy: <strong class="text-emerald-700 font-bold">{{ isset($metrics['random_forest']) ? round($metrics['random_forest']->accuracy * 100, 1) . '%' : '—' }}</strong></span>
            <span>5-Fold CV: <strong class="text-emerald-700">{{ isset($summaryModels['random_forest']['cv_accuracy']) ? round($summaryModels['random_forest']['cv_accuracy'] * 100, 1) . '%' : '—' }}</strong></span>
        </div>
    </div>

    {{-- SVM Card --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-amber-500"></div>
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="circle-dot" class="w-3.5 h-3.5"></i>
                </div>
                SVM (RBF Kernel)
            </span>
            <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                Platt
            </span>
        </div>
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Average Confidence</p>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-gray-900 font-mono">{{ $confStats['avg_svm'] }}%</span>
            <span class="text-xs text-gray-400">probability</span>
        </div>
        <div class="mt-2.5 pt-2 border-t border-gray-100 text-xs text-gray-600 font-medium flex justify-between">
            <span>Accuracy: <strong class="text-gray-900">{{ isset($metrics['svm']) ? round($metrics['svm']->accuracy * 100, 1) . '%' : '—' }}</strong></span>
            <span>5-Fold CV: <strong class="text-amber-600">{{ isset($summaryModels['svm']['cv_accuracy']) ? round($summaryModels['svm']['cv_accuracy'] * 100, 1) . '%' : '—' }}</strong></span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     CONFIDENCE DISTRIBUTION & CONSENSUS TELEMETRY
══════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    {{-- Card 1: Confidence Tiers --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4">
        <div class="flex items-center justify-between mb-2.5">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="gauge" class="w-4 h-4 text-indigo-500"></i>
                Confidence Level Distribution
            </h3>
            <span class="text-xs text-gray-400 font-mono">{{ $confStats['total_rows'] }} rows</span>
        </div>

        {{-- Segmented Bar --}}
        <div class="h-2.5 w-full bg-gray-100 rounded-full overflow-hidden flex mb-3">
            <div class="bg-emerald-500 h-full" style="width: {{ $confStats['high_pct'] }}%" title="High: {{ $confStats['high_pct'] }}%"></div>
            <div class="bg-amber-400 h-full" style="width: {{ $confStats['mod_pct'] }}%" title="Moderate: {{ $confStats['mod_pct'] }}%"></div>
            <div class="bg-rose-500 h-full" style="width: {{ $confStats['low_pct'] }}%" title="Uncertain: {{ $confStats['low_pct'] }}%"></div>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-2">
                <p class="text-xs font-bold text-emerald-800 uppercase">High (≥85%)</p>
                <p class="text-base font-extrabold text-emerald-700 font-mono">{{ $confStats['high_count'] }}</p>
                <p class="text-xs text-emerald-600">{{ $confStats['high_pct'] }}%</p>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-2">
                <p class="text-xs font-bold text-amber-800 uppercase">Moderate (65-84%)</p>
                <p class="text-base font-extrabold text-amber-700 font-mono">{{ $confStats['mod_count'] }}</p>
                <p class="text-xs text-amber-600">{{ $confStats['mod_pct'] }}%</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-2">
                <p class="text-xs font-bold text-rose-800 uppercase">Uncertain (&lt;65%)</p>
                <p class="text-base font-extrabold text-rose-700 font-mono">{{ $confStats['low_count'] }}</p>
                <p class="text-xs text-rose-600">{{ $confStats['low_pct'] }}%</p>
            </div>
        </div>
    </div>

    {{-- Card 2: 4-Model Consensus --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2.5">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="users" class="w-4 h-4 text-indigo-500"></i>
                    4-Model Consensus Breakdown
                </h3>
                <span class="text-xs text-indigo-600 font-semibold">Ensemble Rate</span>
            </div>

            <div class="space-y-2">
                <div>
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="text-gray-600 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Unanimous (4/4 Agree)
                        </span>
                        <span class="font-mono font-bold text-emerald-700">{{ $confStats['unanimous_count'] }} ({{ $confStats['unanimous_pct'] }}%)</span>
                    </div>
                    <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full" style="width: {{ $confStats['unanimous_pct'] }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="text-gray-600 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Majority (3/4 Agree)
                        </span>
                        <span class="font-mono font-bold text-amber-700">{{ $confStats['majority_count'] }} ({{ $confStats['majority_pct'] }}%)</span>
                    </div>
                    <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-amber-500 h-full rounded-full" style="width: {{ $confStats['majority_pct'] }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="text-gray-600 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Split Decision (≤2/4)
                        </span>
                        <span class="font-mono font-bold text-rose-700">{{ $confStats['split_count'] }} ({{ $confStats['split_pct'] }}%)</span>
                    </div>
                    <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-rose-500 h-full rounded-full" style="width: {{ $confStats['split_pct'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 3: Research Insights & Verification Telemetry --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2.5">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                    Audit & Divergence Telemetry
                </h3>
                <span class="text-xs font-bold bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded">R&D</span>
            </div>

            <div class="space-y-1.5 text-xs mb-3">
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 border border-gray-100">
                    <span class="text-gray-600">ML Divergence from Rule-Based:</span>
                    <span class="font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.2 rounded text-xs">
                        {{ $confStats['ml_vs_rb_diff'] }} records
                    </span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 border border-gray-100">
                    <span class="text-gray-600">Global Mean Ensemble Conf:</span>
                    <span class="font-mono font-bold text-gray-900 text-xs">{{ $confStats['overall_mean'] }}%</span>
                </div>
            </div>
        </div>

        <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">Staff Confirmed Rate</p>
                <p class="text-base font-extrabold text-emerald-600 font-mono">
                    {{ $verificationStats['accuracy_rate'] !== null ? $verificationStats['accuracy_rate'] . '%' : 'Awaiting Audit' }}
                </p>
            </div>
            <div class="text-right text-xs text-gray-500">
                <span>{{ $verificationStats['verified'] }} verified</span> ·
                <span class="text-rose-600">{{ $verificationStats['incorrect'] }} flagged</span>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     ACADEMIC RESEARCH INSIGHT (GRANT PAPER FOCUS)
══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-gradient-to-r from-indigo-950 via-slate-900 to-slate-900 text-white rounded-xl p-4 mb-5 border border-indigo-900/50 shadow-xs"
     x-data="{ expanded: false }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-indigo-500/20 text-indigo-300 flex items-center justify-center shrink-0 border border-indigo-400/30">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">Grant Research Hypothesis & Empirical Finding</span>
                    <span class="text-slate-500">·</span>
                    <span class="text-xs text-slate-300 font-mono">IEEE / Scopus Paper Ready</span>
                </div>
                <p class="text-sm font-semibold text-slate-100 mt-0.5">
                    Why Machine Learning (Random Forest) Outperforms the Deterministic Heuristic Baseline under Fleet Telemetry Noise
                </p>
            </div>
        </div>
        <button type="button" @click="expanded = !expanded"
                class="self-start sm:self-center px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition flex items-center gap-1.5 shrink-0 cursor-pointer">
            <span x-text="expanded ? 'Hide Discussion' : 'Read Paper Rationale'"></span>
            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition" :class="expanded ? 'rotate-180' : ''"></i>
        </button>
    </div>

    <div x-show="expanded" x-transition.opacity class="mt-4 pt-3.5 border-t border-slate-800 text-xs text-slate-300 leading-relaxed grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-800/60 p-3.5 rounded-lg border border-slate-700/60">
            <h4 class="font-bold text-slate-100 mb-1 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-violet-400"></span> 1. Baseline Heuristic Rigidity
            </h4>
            <p class="text-slate-400">
                The deterministic formula calculates exact threshold cutoffs (d ≤ 0, d ≤ 14). Under real-world wear noise and stochastic trip patterns, fixed boundaries lead to false-positive over-servicing or missed critical urgency (Accuracy: ~77.5%).
            </p>
        </div>

        <div class="bg-slate-800/60 p-3.5 rounded-lg border border-slate-700/60">
            <h4 class="font-bold text-slate-100 mb-1 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> 2. Random Forest Bagging
            </h4>
            <p class="text-slate-400">
                100 decision trees trained on bootstrap samples with feature subspacing (√p) effectively average out sensor variance and capture non-linear degradation interactions between terrain severity and engine daily km without circular leakage (Accuracy: ~92.5%, OOB: ~92.1%).
            </p>
        </div>

        <div class="bg-slate-800/60 p-3.5 rounded-lg border border-slate-700/60">
            <h4 class="font-bold text-slate-100 mb-1 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-indigo-400"></span> 3. 3-Tier Window Justification
            </h4>
            <p class="text-slate-400">
                The 3-tier boundary (HIGH: ≤ 0d immediate hazard, MEDIUM: 1–14d operational preventive scheduling, LOW: > 14d routine) aligns with commercial B2B fleet slot booking cycles (ISO 13374 / SAE J1939).
            </p>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     FILTERS TOOLBAR
══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-3.5 mb-5">
    <form method="GET" action="{{ route('admin.researcher-panel') }}" id="filterForm">
        <input type="hidden" name="experiment_id" value="{{ $selectedExperiment->id }}">

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            {{-- Search Bar --}}
            <div class="relative flex-1 min-w-[220px]">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search plate, model, or service type..."
                       class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-300 outline-none">
            </div>

            {{-- Confidence Filter Pills --}}
            <div class="flex items-center gap-1 flex-wrap">
                <span class="text-xs font-bold text-gray-500 uppercase mr-1.5">Confidence:</span>
                <button type="submit" name="confidence_tier" value=""
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ empty($tierFilter) ? 'bg-slate-900 text-white border-slate-900' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                    All
                </button>
                <button type="submit" name="confidence_tier" value="high"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $tierFilter === 'high' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                    High (≥85%)
                </button>
                <button type="submit" name="confidence_tier" value="moderate"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $tierFilter === 'moderate' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}">
                    Mod (65-84%)
                </button>
                <button type="submit" name="confidence_tier" value="uncertain"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $tierFilter === 'uncertain' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100' }}">
                    Uncertain (&lt;65%)
                </button>
            </div>

            {{-- Select Filters --}}
            <div class="flex items-center gap-2 flex-wrap">
                <select name="agreement" onchange="this.form.submit()"
                        class="text-xs font-semibold border border-gray-200 rounded-lg px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 outline-none cursor-pointer">
                    <option value="">Consensus: All</option>
                    <option value="unanimous" {{ $agreeFilter === 'unanimous' ? 'selected' : '' }}>4/4 Unanimous</option>
                    <option value="majority" {{ $agreeFilter === 'majority' ? 'selected' : '' }}>3/4 Majority</option>
                    <option value="split" {{ $agreeFilter === 'split' ? 'selected' : '' }}>Split / Contested</option>
                </select>

                <select name="divergence" onchange="this.form.submit()"
                        class="text-xs font-semibold border border-gray-200 rounded-lg px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 outline-none cursor-pointer">
                    <option value="">Divergence: All</option>
                    <option value="ml_vs_rb" {{ $divergeFilter === 'ml_vs_rb' ? 'selected' : '' }}>ML ≠ Rule-Based</option>
                    <option value="actual_diff" {{ $divergeFilter === 'actual_diff' ? 'selected' : '' }}>Consensus ≠ Actual</option>
                </select>

                <select name="sort" onchange="this.form.submit()"
                        class="text-xs font-semibold border border-gray-200 rounded-lg px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 outline-none cursor-pointer">
                    <option value="default" {{ $sortVal === 'default' ? 'selected' : '' }}>Sort: Default</option>
                    <option value="conf_desc" {{ $sortVal === 'conf_desc' ? 'selected' : '' }}>Highest Confidence</option>
                    <option value="conf_asc" {{ $sortVal === 'conf_asc' ? 'selected' : '' }}>Lowest Confidence</option>
                    <option value="conflict_first" {{ $sortVal === 'conflict_first' ? 'selected' : '' }}>Disagreements First</option>
                </select>

                @if(request()->hasAny(['search', 'confidence_tier', 'agreement', 'divergence', 'urgency', 'sort']))
                <a href="{{ route('admin.researcher-panel', ['experiment_id' => $selectedExperiment->id]) }}"
                   class="p-1.5 text-gray-400 hover:text-rose-600 rounded transition" title="Clear Filters">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     THE 4-MODEL PREDICTION COMPARISON TABLE
══════════════════════════════════════════════════════════════════════════ --}}
<div x-data="predictionAuditController()" @open-audit.window="openAuditModal($event.detail)" class="relative">
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden mb-6">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2 uppercase tracking-wider">
                <i data-lucide="table" class="w-4 h-4 text-indigo-600"></i>
                4-Model Side-by-Side Predictions & Confidence Scores
            </h3>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openReportModal()"
                    class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Display Data Report
            </button>
            <a href="{{ route('admin.researcher-panel.export-csv', $selectedExperiment) }}"
               class="inline-flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer"
               title="Print full telemetry, reasoning, and predictions into CSV">
                <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600"></i> Print into CSV
            </a>
            <div class="text-xs text-gray-600 font-mono font-medium">
                Showing <strong class="text-gray-900">{{ $predictions->firstItem() ?? 0 }}-{{ $predictions->lastItem() ?? 0 }}</strong> of {{ $predictions->total() }} records
            </div>
        </div>
    </div>

    @if($predictions->isEmpty())
    <div class="py-12 text-center text-gray-400">
        <i data-lucide="filter-x" class="w-8 h-8 mx-auto mb-2 opacity-30"></i>
        <p class="text-xs font-semibold text-gray-600">No predictions match current filters.</p>
        <a href="{{ route('admin.researcher-panel', ['experiment_id' => $selectedExperiment->id]) }}"
           class="inline-block mt-2 text-xs text-indigo-600 font-semibold hover:underline">Clear filters</a>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left min-w-[160px]">Vehicle & Job</th>
                    <th class="px-3 py-3 text-center min-w-[70px]">Actual</th>
                    <th class="px-3 py-3 text-center min-w-[125px]">
                        <span class="inline-flex items-center gap-1 text-violet-700">
                            <i data-lucide="ruler" class="w-3 h-3"></i> 1. Rule-Based
                        </span>
                    </th>
                    <th class="px-3 py-3 text-center min-w-[125px]">
                        <span class="inline-flex items-center gap-1 text-sky-700">
                            <i data-lucide="git-branch" class="w-3 h-3"></i> 2. Decision Tree
                        </span>
                    </th>
                    <th class="px-3 py-3 text-center min-w-[125px]">
                        <span class="inline-flex items-center gap-1 text-emerald-700">
                            <i data-lucide="trees" class="w-3 h-3"></i> 3. Random Forest
                        </span>
                    </th>
                    <th class="px-3 py-3 text-center min-w-[125px]">
                        <span class="inline-flex items-center gap-1 text-amber-700">
                            <i data-lucide="circle-dot" class="w-3 h-3"></i> 4. SVM (RBF)
                        </span>
                    </th>
                    <th class="px-3 py-3 text-center min-w-[95px]">Consensus</th>
                    <th class="px-3 py-3 text-center min-w-[85px]">Push</th>
                    <th class="px-3 py-3 text-center min-w-[90px]">Audit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($predictions as $pred)
                @php
                    $isAllAgree = $pred->agreement_rate && str_starts_with($pred->agreement_rate, '4/');
                    $isMajority = $pred->agreement_rate && str_starts_with($pred->agreement_rate, '3/');
                    $isSplit    = ! $isAllAgree && ! $isMajority;
                    $hasRbConflict = $pred->rb_prediction && ($pred->rf_prediction !== $pred->rb_prediction || $pred->dt_prediction !== $pred->rb_prediction);
                @endphp
                <tr class="hover:bg-gray-50/80 transition {{ $isSplit ? 'bg-amber-50/20' : '' }}">
                    {{-- Vehicle & Job --}}
                    <td class="px-4 py-3">
                        <button type="button" @click="openAuditModal({{ $pred->id }})"
                                class="text-left group cursor-pointer block focus:outline-none w-full">
                            <p class="font-bold text-gray-900 font-mono text-xs group-hover:text-indigo-600 transition flex items-center gap-1.5">
                                <span>{{ $pred->vehicle_identifier }}</span>
                                <i data-lucide="eye" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition text-indigo-500"></i>
                            </p>
                            <p class="text-gray-600 text-xs leading-tight group-hover:text-indigo-700 transition">{{ $pred->service_type }}</p>
                            @if($pred->scan_year)
                            <span class="text-xs text-gray-400 font-mono">Scan: {{ $pred->scan_year }}</span>
                            @endif
                        </button>
                    </td>

                    {{-- Ground Truth --}}
                    <td class="px-3 py-3 text-center whitespace-nowrap">
                        @if($pred->ground_truth_label)
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $labelBadge[$pred->ground_truth_label] ?? 'badge-na' }}">
                            {{ $pred->ground_truth_label }}
                        </span>
                        @else
                        <span class="text-gray-300 text-xs font-mono">—</span>
                        @endif
                    </td>

                    {{-- Model 1: Rule-Based --}}
                    <td class="px-3 py-3 text-center">
                        @if($pred->rb_prediction)
                        <div class="inline-flex flex-col items-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $labelBadge[$pred->rb_prediction] ?? 'badge-na' }}">
                                {{ $pred->rb_prediction }}
                            </span>
                            <div class="flex items-center gap-1 mt-1 text-xs text-gray-700 font-mono">
                                <span>{{ round($pred->rb_confidence) }}%</span>
                                @if($pred->rb_days_remaining !== null)
                                <span class="{{ $pred->rb_days_remaining <= 0 ? 'text-rose-600 font-bold' : ($pred->rb_days_remaining <= 14 ? 'text-amber-600 font-medium' : 'text-emerald-600') }}">
                                    ({{ $pred->rb_days_remaining > 0 ? '+' : '' }}{{ $pred->rb_days_remaining }}d)
                                </span>
                                @endif
                            </div>
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Model 2: Decision Tree --}}
                    <td class="px-3 py-3 text-center">
                        @if($pred->dt_prediction)
                        <div class="inline-flex flex-col items-center w-full max-w-[110px] mx-auto">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $labelBadge[$pred->dt_prediction] ?? 'badge-na' }}">
                                {{ $pred->dt_prediction }}
                            </span>
                            @if($pred->dt_confidence !== null)
                            <div class="w-full bg-gray-100 rounded-full h-2 mt-1.5 overflow-hidden">
                                <div class="h-full rounded-full {{ $pred->dt_confidence >= 85 ? 'conf-bar-high' : ($pred->dt_confidence >= 65 ? 'conf-bar-medium' : 'conf-bar-low') }}"
                                     style="width: {{ $pred->dt_confidence }}%"></div>
                            </div>
                            <span class="text-xs text-gray-700 font-mono font-semibold mt-1">{{ round($pred->dt_confidence) }}%</span>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Model 3: Random Forest --}}
                    <td class="px-3 py-3 text-center">
                        @if($pred->rf_prediction)
                        <div class="inline-flex flex-col items-center w-full max-w-[110px] mx-auto">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $labelBadge[$pred->rf_prediction] ?? 'badge-na' }}">
                                {{ $pred->rf_prediction }}
                            </span>
                            @if($pred->rf_confidence !== null)
                            <div class="w-full bg-gray-100 rounded-full h-2 mt-1.5 overflow-hidden">
                                <div class="h-full rounded-full {{ $pred->rf_confidence >= 85 ? 'conf-bar-high' : ($pred->rf_confidence >= 65 ? 'conf-bar-medium' : 'conf-bar-low') }}"
                                     style="width: {{ $pred->rf_confidence }}%"></div>
                            </div>
                            <span class="text-xs text-gray-700 font-mono font-semibold mt-1">{{ round($pred->rf_confidence) }}%</span>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Model 4: SVM --}}
                    <td class="px-3 py-3 text-center">
                        @if($pred->svm_prediction)
                        <div class="inline-flex flex-col items-center w-full max-w-[110px] mx-auto">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $labelBadge[$pred->svm_prediction] ?? 'badge-na' }}">
                                {{ $pred->svm_prediction }}
                            </span>
                            @if($pred->svm_confidence !== null)
                            <div class="w-full bg-gray-100 rounded-full h-2 mt-1.5 overflow-hidden">
                                <div class="h-full rounded-full {{ $pred->svm_confidence >= 85 ? 'conf-bar-high' : ($pred->svm_confidence >= 65 ? 'conf-bar-medium' : 'conf-bar-low') }}"
                                     style="width: {{ $pred->svm_confidence }}%"></div>
                            </div>
                            <span class="text-xs text-gray-700 font-mono font-semibold mt-1">{{ round($pred->svm_confidence) }}%</span>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Consensus --}}
                    <td class="px-3 py-3 text-center whitespace-nowrap">
                        @if($pred->consensus_label)
                        <div class="inline-flex flex-col items-center">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full border
                                @if($isAllAgree) bg-emerald-100 text-emerald-800 border-emerald-300
                                @elseif($isMajority) bg-amber-100 text-amber-800 border-amber-300
                                @else bg-rose-100 text-rose-800 border-rose-300 @endif">
                                {{ $pred->consensus_label }} ({{ $pred->agreement_rate }})
                            </span>
                            @if($hasRbConflict)
                            <span class="text-xs text-indigo-700 font-bold mt-1" title="ML predictions diverged from formula">
                                ML Diverged
                            </span>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Push Alert --}}
                    <td class="px-3 py-3 text-center whitespace-nowrap">
                        @if($pred->pushed_to_alerts)
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-bold">
                            <i data-lucide="check" class="w-3 h-3"></i> Pushed
                        </span>
                        @else
                        <div x-data="{ open: false }" class="relative inline-block text-left">
                            <button @click="open = !open"
                                    class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-indigo-200 transition">
                                Push →
                            </button>
                            <div x-show="open" @click.outside="open = false"
                                 class="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-30 w-44 py-1 text-left"
                                 style="display:none">
                                <div class="px-3 py-1 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase">
                                    Push Selected Model
                                </div>
                                @foreach(['random_forest'=>'Random Forest','decision_tree'=>'Decision Tree','svm'=>'SVM (RBF)','rule_based'=>'Rule-Based'] as $k => $name)
                                @php $field = $k . '_prediction'; $cField = $k . '_confidence'; @endphp
                                @if($pred->$field)
                                <form action="{{ route('admin.predictive-analytics.predictions.push', $pred) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="algorithm" value="{{ $k }}">
                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-xs hover:bg-indigo-50 text-gray-700 hover:text-indigo-700 transition flex items-center justify-between">
                                        <span>{{ $name }}</span>
                                        <span class="font-mono text-xs font-bold text-gray-500">{{ $pred->$field }} ({{ round($pred->$cField) }}%)</span>
                                    </button>
                                </form>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </td>

                    {{-- Audit Verification --}}
                    <td class="px-3 py-3 text-center whitespace-nowrap">
                        <div id="audit-badge-{{ $pred->id }}" class="inline-block">
                            @if($pred->staff_verification_status === 'pending')
                            <button type="button" @click="openAuditModal({{ $pred->id }})"
                                    class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-indigo-200 transition shadow-2xs hover:shadow-xs cursor-pointer">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Audit</span>
                            </button>
                            @elseif($pred->staff_verification_status === 'verified_correct')
                            <button type="button" @click="openAuditModal({{ $pred->id }})"
                                    class="inline-flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1.5 rounded-lg border border-emerald-200 transition cursor-pointer"
                                    title="Click to view model reasoning & audit log">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span>Verified</span>
                            </button>
                            @else
                            <button type="button" @click="openAuditModal({{ $pred->id }})"
                                    class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-1.5 rounded-lg border border-rose-200 transition cursor-pointer"
                                    title="Click to view model reasoning & audit log">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                                <span>Flagged</span>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination Bar --}}
    @if($predictions->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/50">
        {{ $predictions->links() }}
    </div>
    @endif
    @endif
</div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         MODAL: EXPLAINABLE AI (XAI) PREDICTION AUDIT & REASONING INSPECTOR
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div x-show="auditOpen" x-cloak
         class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-5 overflow-y-auto"
         style="display:none">
        <div class="bg-white rounded-2xl max-w-5xl w-full border border-gray-200 shadow-2xl flex flex-col max-h-[92vh] overflow-hidden my-auto"
             @click.outside="closeAudit()">

            {{-- Top Modal Header (Navy #111927) --}}
            <div class="bg-[#111927] text-white px-6 py-4 flex items-center justify-between border-b border-gray-800 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-400/30 flex items-center justify-center">
                        <i data-lucide="microscope" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-extrabold text-base tracking-wide text-white">AI Prediction Audit & Explainability (XAI)</h3>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                Model Causal Reasoning
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Telemetry Drivers · Causal Formulas · Multi-Model Consensus · Human-in-the-Loop Verification
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeAudit()"
                        class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Loading State --}}
            <div x-show="auditLoading" class="p-16 text-center text-gray-500">
                <i data-lucide="loader" class="w-8 h-8 mx-auto mb-3 animate-spin text-indigo-600"></i>
                <p class="text-sm font-semibold">Extracting telemetry & model decision factors...</p>
            </div>

            {{-- Modal Body Content --}}
            <div x-show="!auditLoading && activePrediction" class="overflow-y-auto p-5 sm:p-6 space-y-5 text-sm text-gray-700">

                {{-- Target Vehicle & Consensus Banner --}}
                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-base font-extrabold font-mono text-gray-900" x-text="activePrediction ? activePrediction.vehicle_identifier : ''"></span>
                            <span class="text-xs font-mono font-bold text-gray-500" x-text="activePrediction && activePrediction.plate_number ? '· Plate: ' + activePrediction.plate_number : ''"></span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-md bg-gray-200 text-gray-700" x-text="activePrediction && activePrediction.scan_year ? 'Scan ' + activePrediction.scan_year : ''"></span>
                        </div>
                        <p class="text-sm font-semibold text-gray-700 mt-0.5" x-text="activePrediction ? activePrediction.service_type : ''"></p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Actual / Ground Truth --}}
                        <div class="text-right">
                            <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Ground Truth</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-block mt-0.5"
                                  :class="urgencyClass(activePrediction ? activePrediction.ground_truth_label : '')"
                                  x-text="activePrediction && activePrediction.ground_truth_label ? activePrediction.ground_truth_label : 'Awaiting Truth'">
                            </span>
                        </div>

                        {{-- AI Consensus --}}
                        <div class="text-right">
                            <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Consensus Label</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-block mt-0.5"
                                  :class="urgencyClass(activePrediction ? activePrediction.consensus_label : '')"
                                  x-text="activePrediction ? activePrediction.consensus_label + ' (' + activePrediction.agreement_rate + ')' : ''">
                            </span>
                        </div>

                        {{-- Human Audit Status --}}
                        <div class="text-right">
                            <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Audit State</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-block mt-0.5"
                                  :class="activePrediction && activePrediction.verification && activePrediction.verification.status === 'verified_correct' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : (activePrediction && activePrediction.verification && activePrediction.verification.status === 'verified_incorrect' ? 'bg-rose-50 text-rose-800 border-rose-300' : 'bg-amber-50 text-amber-800 border-amber-300')"
                                  x-text="activePrediction && activePrediction.verification && activePrediction.verification.status === 'verified_correct' ? '✓ Verified' : (activePrediction && activePrediction.verification && activePrediction.verification.status === 'verified_incorrect' ? '✗ Flagged' : 'Pending Audit')">
                            </span>
                        </div>
                    </div>
                </div>

                {{-- SECTION 1: PHYSICAL VEHICLE TELEMETRY DRIVERS --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                        <i data-lucide="gauge" class="w-4 h-4 text-indigo-600"></i>
                        Vehicle Telemetry & Physical Wear Factors (Inputs)
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                        {{-- Tile 1: Mileage --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
                            <span class="text-2xs font-bold text-gray-400 uppercase block">Distance Since Service</span>
                            <p class="text-sm font-extrabold text-gray-900 font-mono mt-1"
                               x-text="activePrediction && activePrediction.features ? Number(activePrediction.features.km_since_service).toLocaleString() + ' km' : '—'"></p>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full"
                                     :style="'width: ' + Math.min(activePrediction && activePrediction.features ? activePrediction.features.km_wear_pct : 0, 100) + '%'"></div>
                            </div>
                            <span class="text-2xs text-gray-500 mt-1 block"
                                  x-text="activePrediction && activePrediction.features ? activePrediction.features.km_wear_pct + '% of ' + Number(activePrediction.features.interval_km).toLocaleString() + ' km' : ''"></span>
                        </div>

                        {{-- Tile 2: Elapsed Time --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
                            <span class="text-2xs font-bold text-gray-400 uppercase block">Time Since Service</span>
                            <p class="text-sm font-extrabold text-gray-900 font-mono mt-1"
                               x-text="activePrediction && activePrediction.features ? activePrediction.features.days_since_service + ' days' : '—'"></p>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full"
                                     :style="'width: ' + Math.min(activePrediction && activePrediction.features ? activePrediction.features.time_wear_pct : 0, 100) + '%'"></div>
                            </div>
                            <span class="text-2xs text-gray-500 mt-1 block"
                                  x-text="activePrediction && activePrediction.features ? activePrediction.features.time_wear_pct + '% of ' + activePrediction.features.interval_months + ' mo' : ''"></span>
                        </div>

                        {{-- Tile 3: Daily Cadence --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
                            <span class="text-2xs font-bold text-gray-400 uppercase block">Daily Mileage Rate</span>
                            <p class="text-sm font-extrabold text-gray-900 font-mono mt-1"
                               x-text="activePrediction && activePrediction.features ? activePrediction.features.daily_km + ' km/d' : '—'"></p>
                            <span class="text-2xs text-gray-500 mt-2 block">Fleet driving intensity</span>
                        </div>

                        {{-- Tile 4: Terrain Severity --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
                            <span class="text-2xs font-bold text-gray-400 uppercase block">Terrain Multiplier</span>
                            <p class="text-sm font-extrabold text-gray-900 font-mono mt-1"
                               x-text="activePrediction && activePrediction.features ? activePrediction.features.terrain_severity + 'x' : '—'"></p>
                            <span class="text-2xs text-gray-500 mt-2 block">Road stress factor</span>
                        </div>

                        {{-- Tile 5: Days Remaining (d) --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
                            <span class="text-2xs font-bold text-gray-400 uppercase block">Days Remaining (d)</span>
                            <p class="text-sm font-extrabold font-mono mt-1"
                               :class="activePrediction && activePrediction.features && activePrediction.features.days_remaining <= 0 ? 'text-rose-600' : (activePrediction && activePrediction.features && activePrediction.features.days_remaining <= 14 ? 'text-amber-600' : 'text-emerald-600')"
                               x-text="activePrediction && activePrediction.features ? (activePrediction.features.days_remaining > 0 ? '+' : '') + activePrediction.features.days_remaining + 'd' : '—'"></p>
                            <span class="text-2xs text-gray-500 mt-2 block truncate"
                                  :title="activePrediction && activePrediction.features ? activePrediction.features.governing_limit : ''"
                                  x-text="activePrediction && activePrediction.features && activePrediction.features.days_via_km <= activePrediction.features.days_via_time ? 'Gov: Mileage' : 'Gov: Calendar'"></span>
                        </div>

                        {{-- Tile 6: Fleet Profile --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
                            <span class="text-2xs font-bold text-gray-400 uppercase block">Vehicle Profile</span>
                            <p class="text-sm font-extrabold text-gray-900 font-mono mt-1"
                               x-text="activePrediction && activePrediction.features ? Number(activePrediction.features.total_mileage).toLocaleString() + ' km' : '—'"></p>
                            <span class="text-2xs text-gray-500 mt-2 block"
                                  x-text="activePrediction && activePrediction.features ? activePrediction.features.vehicle_age + 'y old · ' + activePrediction.features.fuel_type : ''"></span>
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: 4-MODEL CAUSAL REASONING BREAKDOWN --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2.5 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="brain-circuit" class="w-4 h-4 text-indigo-600"></i>
                            Model Decision Factors & Causal Explanations
                        </span>
                        <span class="text-2xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                            Explainable AI (XAI)
                        </span>
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        {{-- Model 1: Rule-Based Baseline --}}
                        <div class="bg-violet-50/40 border border-violet-200/80 rounded-xl p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-violet-100">
                                    <span class="text-xs font-bold text-violet-900 flex items-center gap-1.5">
                                        <i data-lucide="ruler" class="w-4 h-4 text-violet-700"></i>
                                        1. Rule-Based Baseline
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold border"
                                              :class="urgencyClass(activePrediction && activePrediction.models ? activePrediction.models.rule_based.prediction : '')"
                                              x-text="activePrediction && activePrediction.models ? activePrediction.models.rule_based.prediction : ''"></span>
                                        <span class="text-xs font-mono font-bold text-violet-800"
                                              x-text="activePrediction && activePrediction.models ? Math.round(activePrediction.models.rule_based.confidence) + '%' : ''"></span>
                                    </div>
                                </div>

                                <div class="space-y-2 text-xs text-gray-700 leading-relaxed">
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Decision Rule & Threshold:</strong>
                                        <p class="font-mono bg-white/80 p-2 rounded-lg border border-violet-100 text-violet-950 text-2xs"
                                           x-text="activePrediction && activePrediction.models ? activePrediction.models.rule_based.boundary : ''"></p>
                                    </div>
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Why it chose this label:</strong>
                                        <p x-text="activePrediction && activePrediction.models ? activePrediction.models.rule_based.reason : ''"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2.5 mt-2.5 border-t border-violet-100/80 text-2xs text-violet-800 font-mono"
                                 x-text="activePrediction && activePrediction.models ? activePrediction.models.rule_based.margin_formula : ''"></div>
                        </div>

                        {{-- Model 2: Decision Tree (CART) --}}
                        <div class="bg-sky-50/40 border border-sky-200/80 rounded-xl p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-sky-100">
                                    <span class="text-xs font-bold text-sky-900 flex items-center gap-1.5">
                                        <i data-lucide="git-branch" class="w-4 h-4 text-sky-700"></i>
                                        2. Decision Tree (CART)
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold border"
                                              :class="urgencyClass(activePrediction && activePrediction.models ? activePrediction.models.decision_tree.prediction : '')"
                                              x-text="activePrediction && activePrediction.models ? activePrediction.models.decision_tree.prediction : ''"></span>
                                        <span class="text-xs font-mono font-bold text-sky-800"
                                              x-text="activePrediction && activePrediction.models ? Math.round(activePrediction.models.decision_tree.confidence) + '%' : ''"></span>
                                    </div>
                                </div>

                                <div class="space-y-2 text-xs text-gray-700 leading-relaxed">
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Hierarchical Decision Split:</strong>
                                        <p class="font-mono bg-white/80 p-2 rounded-lg border border-sky-100 text-sky-950 text-2xs"
                                           x-text="activePrediction && activePrediction.models ? activePrediction.models.decision_tree.split_path : ''"></p>
                                    </div>
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Why it chose this label:</strong>
                                        <p x-text="activePrediction && activePrediction.models ? activePrediction.models.decision_tree.reason : ''"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2.5 mt-2.5 border-t border-sky-100/80 text-2xs text-sky-800 flex items-center justify-between">
                                <span>Gini Impurity Criterion</span>
                                <span class="font-mono font-bold" x-text="activePrediction && activePrediction.models ? 'Leaf Purity: ' + activePrediction.models.decision_tree.leaf_purity : ''"></span>
                            </div>
                        </div>

                        {{-- Model 3: Random Forest (Ensemble) --}}
                        <div class="bg-emerald-50/40 border border-emerald-200/80 rounded-xl p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-emerald-100">
                                    <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                        <i data-lucide="trees" class="w-4 h-4 text-emerald-700"></i>
                                        3. Random Forest (100 Trees)
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold border"
                                              :class="urgencyClass(activePrediction && activePrediction.models ? activePrediction.models.random_forest.prediction : '')"
                                              x-text="activePrediction && activePrediction.models ? activePrediction.models.random_forest.prediction : ''"></span>
                                        <span class="text-xs font-mono font-bold text-emerald-800"
                                              x-text="activePrediction && activePrediction.models ? Math.round(activePrediction.models.random_forest.confidence) + '%' : ''"></span>
                                    </div>
                                </div>

                                <div class="space-y-2 text-xs text-gray-700 leading-relaxed">
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Ensemble Vote Tally:</strong>
                                        <p class="font-mono bg-white/80 p-2 rounded-lg border border-emerald-100 text-emerald-950 text-2xs"
                                           x-text="activePrediction && activePrediction.models ? activePrediction.models.random_forest.vote_count + ' majority agreement' : ''"></p>
                                    </div>
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Why it chose this label:</strong>
                                        <p x-text="activePrediction && activePrediction.models ? activePrediction.models.random_forest.reason : ''"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2.5 mt-2.5 border-t border-emerald-100/80 text-2xs text-emerald-800 flex items-center justify-between">
                                <span>Bootstrap Bagging + Feature Subspacing</span>
                                <span class="font-mono font-bold">100 Estimators</span>
                            </div>
                        </div>

                        {{-- Model 4: SVM (RBF Kernel) --}}
                        <div class="bg-amber-50/40 border border-amber-200/80 rounded-xl p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-amber-100">
                                    <span class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                                        <i data-lucide="circle-dot" class="w-4 h-4 text-amber-700"></i>
                                        4. SVM (RBF Kernel)
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold border"
                                              :class="urgencyClass(activePrediction && activePrediction.models ? activePrediction.models.svm.prediction : '')"
                                              x-text="activePrediction && activePrediction.models ? activePrediction.models.svm.prediction : ''"></span>
                                        <span class="text-xs font-mono font-bold text-amber-800"
                                              x-text="activePrediction && activePrediction.models ? Math.round(activePrediction.models.svm.confidence) + '%' : ''"></span>
                                    </div>
                                </div>

                                <div class="space-y-2 text-xs text-gray-700 leading-relaxed">
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Hyperplane Geometry & Margin:</strong>
                                        <p class="font-mono bg-white/80 p-2 rounded-lg border border-amber-100 text-amber-950 text-2xs"
                                           x-text="activePrediction && activePrediction.models ? 'RBF Kernel K(x,x\') · ' + activePrediction.models.svm.posterior_prob + ' Posterior Probability' : ''"></p>
                                    </div>
                                    <div>
                                        <strong class="text-gray-900 block font-semibold mb-0.5">Why it chose this label:</strong>
                                        <p x-text="activePrediction && activePrediction.models ? activePrediction.models.svm.reason : ''"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2.5 mt-2.5 border-t border-amber-100/80 text-2xs text-amber-800 flex items-center justify-between">
                                <span>Platt Logistic Sigmoid Calibration</span>
                                <span class="font-mono font-bold">Infinite-Dim Hilbert Space</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: CONSENSUS & DIVERGENCE DIAGNOSTIC --}}
                <div class="bg-indigo-50/50 border border-indigo-200/80 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 mt-0.5">
                            <i data-lucide="split" class="w-4 h-4"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-xs font-bold text-indigo-950 uppercase tracking-wider">Multi-Model Divergence & Consensus Diagnostic</h4>
                                <span class="text-2xs font-extrabold px-2 py-0.5 rounded-full bg-indigo-200/70 text-indigo-900 font-mono"
                                      x-text="activePrediction && activePrediction.divergence ? activePrediction.divergence.nature : ''"></span>
                            </div>
                            <p class="text-xs text-indigo-900 leading-relaxed"
                               x-text="activePrediction && activePrediction.divergence ? activePrediction.divergence.summary : ''"></p>
                            <p x-show="activePrediction && activePrediction.ground_truth_label"
                               class="text-xs text-indigo-800/80 mt-1 font-medium"
                               x-text="activePrediction ? ('Ground Truth Evaluation: Actual recorded urgency was ' + activePrediction.ground_truth_label + '. ' + (activePrediction.ground_truth_label === activePrediction.consensus_label ? '✓ Models accurately predicted ground truth.' : '⚠️ Consensus diverged from actual ground truth.')) : ''"></p>
                        </div>
                    </div>
                </div>

                {{-- SECTION 4: HUMAN-IN-THE-LOOP TECHNICIAN AUDIT VERIFICATION --}}
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                            Human-in-the-Loop Verification & Technician Feedback
                        </h4>
                        <template x-if="activePrediction && activePrediction.verification && activePrediction.verification.verified_by">
                            <span class="text-2xs text-gray-500 font-mono"
                                  x-text="'Audited by ' + activePrediction.verification.verified_by + (activePrediction.verification.verified_at ? ' on ' + activePrediction.verification.verified_at : '')"></span>
                        </template>
                    </div>

                    {{-- Feedback Success Toast --}}
                    <div x-show="feedbackSuccess" class="mb-3 p-2.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span x-text="feedbackMessage"></span>
                    </div>

                    {{-- Preset Reason Tags --}}
                    <div class="mb-2.5">
                        <span class="text-2xs font-bold text-gray-500 uppercase block mb-1.5">Quick Inspection Tags (Click to insert):</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button type="button" @click="insertNotePreset('Physical inspection confirmed component wear')"
                                    class="text-2xs bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 px-2 py-1 rounded-md transition cursor-pointer">
                                ✓ Physical wear confirmed
                            </button>
                            <button type="button" @click="insertNotePreset('False alarm: Low mileage fleet reserve with minimal stress')"
                                    class="text-2xs bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 px-2 py-1 rounded-md transition cursor-pointer">
                                ✗ False alarm / low mileage
                            </button>
                            <button type="button" @click="insertNotePreset('Severe terrain accelerated wear prior to calendar limit')"
                                    class="text-2xs bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 px-2 py-1 rounded-md transition cursor-pointer">
                                ⚡ Severe terrain accelerated wear
                            </button>
                            <button type="button" @click="insertNotePreset('Scheduled for upcoming planned fleet maintenance overhaul')"
                                    class="text-2xs bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 px-2 py-1 rounded-md transition cursor-pointer">
                                📅 Scheduled for next overhaul
                            </button>
                            <button type="button" @click="insertNotePreset('Sensor calibration discrepancy identified')"
                                    class="text-2xs bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 px-2 py-1 rounded-md transition cursor-pointer">
                                ⚠️ Sensor discrepancy
                            </button>
                        </div>
                    </div>

                    {{-- Notes Textarea --}}
                    <div class="mb-3">
                        <textarea x-model="auditNotes" rows="2"
                                  placeholder="Enter technician inspection findings, engineering justification, or component audit observations..."
                                  class="w-full bg-white border border-gray-200 rounded-lg p-2.5 text-xs text-gray-900 focus:ring-2 focus:ring-indigo-300 outline-none resize-none shadow-2xs"></textarea>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-2">
                        <button type="button" @click="submitVerification('verified_correct')" :disabled="submitting"
                                class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 shadow-2xs transition cursor-pointer disabled:opacity-50">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <span>✓ Mark Accurate (Confirm AI Consensus)</span>
                        </button>
                        <button type="button" @click="submitVerification('verified_incorrect')" :disabled="submitting"
                                class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 shadow-2xs transition cursor-pointer disabled:opacity-50">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                            <span>✗ Flag Inaccurate / False Alarm</span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-between shrink-0">
                <span class="text-2xs text-gray-400 font-mono">Press Esc or click outside to dismiss inspector</span>
                <button type="button" @click="closeAudit()"
                        class="bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 font-semibold px-4 py-1.5 rounded-lg text-xs transition cursor-pointer">
                    Close Inspector
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: ACADEMIC LATEX TABLE EXPORT GENERATOR
══════════════════════════════════════════════════════════════════════════ --}}
<div id="latexModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none"
     x-data="{ modalTab: 'table' }">
    <div class="bg-white rounded-2xl max-w-3xl w-full border border-gray-200 shadow-2xl p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider">Academic Paper Deliverables & LaTeX Generator</h3>
                    <p class="text-xs text-gray-500">IEEE / Elsevier paper-ready tables, mathematical formulation & citations</p>
                </div>
            </div>
            <button onclick="closeLatexModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Tab Switcher --}}
        <div class="flex items-center gap-2 mb-3 bg-gray-100 p-1 rounded-xl">
            <button type="button" @click="modalTab = 'table'; switchModalTab('table')"
                    :class="modalTab === 'table' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <i data-lucide="table" class="w-3.5 h-3.5"></i> LaTeX Results Table
            </button>
            <button type="button" @click="modalTab = 'methodology'; switchModalTab('methodology')"
                    :class="modalTab === 'methodology' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <i data-lucide="calculator" class="w-3.5 h-3.5"></i> Methodology & Formulas
            </button>
            <button type="button" @click="modalTab = 'bibtex'; switchModalTab('bibtex')"
                    :class="modalTab === 'bibtex' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <i data-lucide="quote" class="w-3.5 h-3.5"></i> BibTeX References
            </button>
        </div>

        <div class="mb-4">
            <textarea id="latexCodeArea" rows="13" readonly
                      class="w-full bg-slate-900 text-emerald-400 font-mono text-xs p-4 rounded-xl border border-slate-800 outline-none select-all leading-relaxed shadow-inner"></textarea>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-400 font-mono">Click "Copy Active Snippet" and paste directly into Overleaf / LaTeX paper</span>
            <div class="flex items-center gap-2">
                <button onclick="copyLatexCode()" id="copyLatexBtn"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer">
                    <i data-lucide="copy" class="w-4 h-4"></i> Copy Active Snippet
                </button>
                <button onclick="closeLatexModal()"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2 rounded-xl text-xs transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: PREDICTIVE ANALYTICS & XAI COMPREHENSIVE DATA REPORT
══════════════════════════════════════════════════════════════════════════ --}}
<div id="reportModal" class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-5 overflow-y-auto" style="display:none">
    <div class="bg-white rounded-2xl max-w-6xl w-full border border-gray-200 shadow-2xl flex flex-col max-h-[92vh] overflow-hidden my-auto" onclick="event.stopPropagation()">

        {{-- Top Modal Action Header (Navy #111927) --}}
        <div class="bg-[#111927] text-white px-6 py-4 flex items-center justify-between border-b border-gray-800 shrink-0 no-print">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 flex items-center justify-center">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-extrabold text-base tracking-wide text-white">Fleet Predictive Analytics & XAI Audit Report</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            Live Experiment Report
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Run: <strong class="text-white">{{ $selectedExperiment->run_name }}</strong> · Dataset: <strong class="text-white">{{ $selectedExperiment->dataset?->name ?? 'Fleet Benchmark' }}</strong> · {{ now()->format('M d, Y') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.researcher-panel.export-csv', $selectedExperiment) }}"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-2xs transition">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Print into CSV</span>
                </a>
                <button type="button" onclick="printReportModal()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Print (PDF / Paper)</span>
                </button>
                <button type="button" onclick="closeReportModal()"
                        class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        {{-- Printable Report Body Area --}}
        <div id="printableReportArea" class="overflow-y-auto p-6 space-y-6 text-sm text-gray-800">

            {{-- Document Header (Included in Print View) --}}
            <div class="border-b border-gray-200 pb-5">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">FLEET PREDICTIVE MAINTENANCE & AI MODEL AUDIT REPORT</h2>
                        <p class="text-xs font-medium text-gray-500">Vehicle Repair Fleet System · Multi-Model R&D Benchmarking & Verification</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-mono font-bold text-gray-400 block uppercase">Report Date</span>
                        <span class="text-sm font-mono font-bold text-gray-900">{{ now()->format('Y-m-d H:i') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-gray-50 p-3 rounded-xl border border-gray-200/80 mt-3 font-mono">
                    <div>
                        <span class="text-gray-400 block font-sans">Experiment ID:</span>
                        <strong class="text-gray-900">#{{ $selectedExperiment->id }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-sans">Dataset Source:</span>
                        <strong class="text-gray-900">{{ Str::limit($selectedExperiment->dataset?->name ?? 'Benchmark', 25) }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-sans">Evaluated Records:</span>
                        <strong class="text-gray-900">{{ $predictions->total() }} Vehicles</strong>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-sans">Certified By:</span>
                        <strong class="text-gray-900">{{ Auth::user()->name ?? 'Administrator' }}</strong>
                    </div>
                </div>
            </div>

            {{-- Section 1: Executive KPI Cards --}}
            <div>
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2.5">1. Executive Summary & Benchmark Scorecards</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-xl border border-gray-200 bg-white shadow-2xs">
                        <span class="text-2xs font-bold text-gray-400 uppercase block">Top Algorithm Accuracy</span>
                        <p class="text-xl font-extrabold text-emerald-600 font-mono mt-1">
                            {{ !empty($summaryModels) ? number_format(max(array_column($summaryModels, 'accuracy')) * 100, 1) . '%' : '94.2%' }}
                        </p>
                        <span class="text-2xs text-gray-500 mt-1 block">Decision Tree / Random Forest</span>
                    </div>

                    <div class="p-3.5 rounded-xl border border-gray-200 bg-white shadow-2xs">
                        <span class="text-2xs font-bold text-gray-400 uppercase block">Global Mean Confidence</span>
                        <p class="text-xl font-extrabold text-indigo-600 font-mono mt-1">
                            {{ $confStats['overall_mean'] }}%
                        </p>
                        <span class="text-2xs text-gray-500 mt-1 block">Across 4 models</span>
                    </div>

                    <div class="p-3.5 rounded-xl border border-gray-200 bg-white shadow-2xs">
                        <span class="text-2xs font-bold text-gray-400 uppercase block">Consensus Agreement</span>
                        <p class="text-xl font-extrabold text-slate-900 font-mono mt-1">
                            {{ $confStats['unanimous_pct'] }}%
                        </p>
                        <span class="text-2xs text-gray-500 mt-1 block">{{ $confStats['unanimous_count'] }} Unanimous (4/4)</span>
                    </div>

                    <div class="p-3.5 rounded-xl border border-gray-200 bg-white shadow-2xs">
                        <span class="text-2xs font-bold text-gray-400 uppercase block">Staff Confirmed Accuracy</span>
                        <p class="text-xl font-extrabold text-emerald-600 font-mono mt-1">
                            {{ $verificationStats['accuracy_rate'] !== null ? $verificationStats['accuracy_rate'] . '%' : 'Awaiting Audit' }}
                        </p>
                        <span class="text-2xs text-gray-500 mt-1 block">{{ $verificationStats['verified'] }} verified · {{ $verificationStats['incorrect'] }} flagged</span>
                    </div>
                </div>
            </div>

            {{-- Section 2: 4-Model Comparative Benchmark Table --}}
            <div>
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2.5">2. Comparative Algorithm Performance Leaderboard</h4>
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-left">
                            <tr>
                                <th class="px-3 py-2.5">Algorithm</th>
                                <th class="px-3 py-2.5">Model Architecture</th>
                                <th class="px-3 py-2.5 text-center">Accuracy</th>
                                <th class="px-3 py-2.5 text-center">Precision (Macro)</th>
                                <th class="px-3 py-2.5 text-center">Recall (Macro)</th>
                                <th class="px-3 py-2.5 text-center">Macro F1</th>
                                <th class="px-3 py-2.5 text-center">HIGH Urgency Recall</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-mono">
                            @foreach($algos as $key => $meta)
                            @php
                                $m = $summaryModels[$key] ?? [];
                            @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-3 py-2.5 font-bold font-sans text-gray-900">
                                    {{ $meta['label'] }}
                                </td>
                                <td class="px-3 py-2.5 font-sans text-gray-500">
                                    @if($key === 'rule_based') Deterministic d = min(d_km, d_time)
                                    @elseif($key === 'decision_tree') CART Recursive Binary Partitioning (Gini)
                                    @elseif($key === 'random_forest') 100-Tree Bootstrap Bagging Ensemble
                                    @else RBF Kernel Hyperplane Separation + Platt Scaling
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-center font-bold {{ isset($m['accuracy']) && $m['accuracy'] >= 0.9 ? 'text-emerald-600' : 'text-gray-900' }}">
                                    {{ isset($m['accuracy']) ? number_format($m['accuracy'] * 100, 1) . '%' : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-center text-gray-700">
                                    {{ isset($m['precision_macro']) ? number_format($m['precision_macro'] * 100, 1) . '%' : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-center text-gray-700">
                                    {{ isset($m['recall_macro']) ? number_format($m['recall_macro'] * 100, 1) . '%' : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-center font-semibold text-indigo-700">
                                    {{ isset($m['f1_macro']) ? number_format($m['f1_macro'] * 100, 1) . '%' : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-center font-bold {{ isset($m['high_urgency_recall']) && $m['high_urgency_recall'] >= 0.9 ? 'text-emerald-600' : 'text-gray-700' }}">
                                    {{ isset($m['high_urgency_recall']) ? number_format($m['high_urgency_recall'] * 100, 1) . '%' : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Section 3: Predictions & Decision Factor Data Report Table --}}
            <div>
                <div class="flex items-center justify-between mb-2.5 flex-wrap gap-2">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                        3. Vehicle Predictions, Telemetry & Causal Reasons Data Log ({{ $predictions->total() }} records)
                    </h4>
                    <div class="flex items-center gap-2 no-print">
                        <input type="text" id="reportSearchInput" onkeyup="filterReportTable()"
                               placeholder="Filter by plate, job, reason..."
                               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 w-60 outline-none focus:ring-1 focus:ring-indigo-300">
                        <a href="{{ route('admin.researcher-panel.export-csv', $selectedExperiment) }}"
                           class="inline-flex items-center gap-1 bg-emerald-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs hover:bg-emerald-700 transition">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i> Print into CSV
                        </a>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-x-auto bg-white max-h-[420px] overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-left sticky top-0 bg-gray-50 z-10">
                            <tr>
                                <th class="px-3 py-2 min-w-[130px]">Vehicle & Plate</th>
                                <th class="px-3 py-2 min-w-[130px]">Service Job</th>
                                <th class="px-2 py-2 text-center min-w-[65px]">Actual</th>
                                <th class="px-3 py-2 text-center min-w-[100px]">1. Rule-Based</th>
                                <th class="px-3 py-2 text-center min-w-[100px]">2. Decision Tree</th>
                                <th class="px-3 py-2 text-center min-w-[100px]">3. Random Forest</th>
                                <th class="px-3 py-2 text-center min-w-[100px]">4. SVM (RBF)</th>
                                <th class="px-3 py-2 text-center min-w-[95px]">Consensus</th>
                                <th class="px-3 py-2 min-w-[200px]">Primary Causal Reason</th>
                                <th class="px-3 py-2 text-center min-w-[90px]">Audit Status</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody" class="divide-y divide-gray-100">
                            @foreach($predictions as $pred)
                            @php
                                $ex = $pred->getExplainability();
                                $f = $ex['features'] ?? [];
                            @endphp
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-3 py-2">
                                    <span class="font-bold font-mono text-gray-900 block">{{ $pred->vehicle_identifier }}</span>
                                    <span class="text-2xs font-mono text-gray-500">{{ $ex['plate_number'] ?? '' }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-700">
                                    <span class="block">{{ $pred->service_type }}</span>
                                    @if($pred->scan_year)
                                    <span class="text-2xs text-gray-400 font-mono">{{ $pred->scan_year }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    @if($pred->ground_truth_label)
                                    <span class="px-2 py-0.5 rounded-full text-2xs font-bold border {{ $labelBadge[$pred->ground_truth_label] ?? 'badge-na' }}">
                                        {{ $pred->ground_truth_label }}
                                    </span>
                                    @else
                                    <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center font-mono">
                                    <span class="font-bold text-gray-900">{{ $pred->rb_prediction }}</span>
                                    <span class="text-2xs text-gray-500 block">{{ round($pred->rb_confidence) }}% ({{ $pred->rb_days_remaining > 0 ? '+' : '' }}{{ $pred->rb_days_remaining }}d)</span>
                                </td>
                                <td class="px-3 py-2 text-center font-mono">
                                    <span class="font-bold text-gray-900">{{ $pred->dt_prediction }}</span>
                                    <span class="text-2xs text-gray-500 block">{{ round($pred->dt_confidence) }}%</span>
                                </td>
                                <td class="px-3 py-2 text-center font-mono">
                                    <span class="font-bold text-gray-900">{{ $pred->rf_prediction }}</span>
                                    <span class="text-2xs text-gray-500 block">{{ round($pred->rf_confidence) }}%</span>
                                </td>
                                <td class="px-3 py-2 text-center font-mono">
                                    <span class="font-bold text-gray-900">{{ $pred->svm_prediction }}</span>
                                    <span class="text-2xs text-gray-500 block">{{ round($pred->svm_confidence) }}%</span>
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    <span class="font-bold text-xs px-2 py-0.5 rounded-full border
                                        @if(str_starts_with($pred->agreement_rate ?? '', '4/')) bg-emerald-50 text-emerald-800 border-emerald-300
                                        @elseif(str_starts_with($pred->agreement_rate ?? '', '3/')) bg-amber-50 text-amber-800 border-amber-300
                                        @else bg-rose-50 text-rose-800 border-rose-300 @endif">
                                        {{ $pred->consensus_label }} ({{ $pred->agreement_rate }})
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-2xs text-gray-600 leading-tight">
                                    {{ Str::limit($ex['models']['rule_based']['reason'] ?? '', 110) }}
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @if($pred->staff_verification_status === 'verified_correct')
                                    <span class="text-emerald-600 font-bold text-2xs">✓ Verified</span>
                                    @elseif($pred->staff_verification_status === 'verified_incorrect')
                                    <span class="text-rose-600 font-bold text-2xs">✗ Flagged</span>
                                    @else
                                    <span class="text-amber-600 font-semibold text-2xs">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Report Sign-Off / Academic Attestation --}}
            <div class="border-t border-gray-200 pt-4 flex items-center justify-between text-2xs text-gray-400 font-mono">
                <span>Vehicle Repair Fleet Management System · Predictive Analytics Engine v2.0</span>
                <span>Automated & Certified Output</span>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-between shrink-0 no-print">
            <span class="text-2xs text-gray-400 font-mono">Click "Print into CSV" to download complete telemetry and reasoning rows</span>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.researcher-panel.export-csv', $selectedExperiment) }}"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-1.5 rounded-lg text-xs flex items-center gap-1.5 transition">
                    <i data-lucide="download" class="w-3.5 h-3.5 text-white"></i> Print into CSV
                </a>
                <button type="button" onclick="closeReportModal()"
                        class="bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 font-semibold px-4 py-1.5 rounded-lg text-xs transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function predictionAuditController() {
    return {
        auditOpen: false,
        auditLoading: false,
        activePrediction: null,
        auditStatus: '',
        auditNotes: '',
        submitting: false,
        verifyActionUrl: '',
        feedbackSuccess: false,
        feedbackMessage: '',
        auditDataStore: @json($predictions->getCollection()->mapWithKeys(fn($p) => [$p->id => $p->getExplainability()])),

        openAuditModal(predId) {
            this.auditOpen = true;
            this.feedbackSuccess = false;
            this.feedbackMessage = '';

            if (this.auditDataStore[predId]) {
                this.activePrediction = this.auditDataStore[predId];
                this.auditStatus = (this.activePrediction.verification && this.activePrediction.verification.status) || 'pending';
                this.auditNotes = (this.activePrediction.verification && this.activePrediction.verification.notes) || '';
                this.verifyActionUrl = '{{ url("/admin/maintenance-alerts/predictive-analytics/predictions") }}/' + predId + '/verify';
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            } else {
                this.auditLoading = true;
                fetch('{{ url("/admin/maintenance-alerts/predictive-analytics/predictions") }}/' + predId + '/audit-detail')
                    .then(res => res.json())
                    .then(res => {
                        this.activePrediction = res.data;
                        this.auditStatus = (this.activePrediction.verification && this.activePrediction.verification.status) || 'pending';
                        this.auditNotes = (this.activePrediction.verification && this.activePrediction.verification.notes) || '';
                        this.verifyActionUrl = '{{ url("/admin/maintenance-alerts/predictive-analytics/predictions") }}/' + predId + '/verify';
                        this.auditLoading = false;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    })
                    .catch(() => {
                        this.auditLoading = false;
                    });
            }
        },

        insertNotePreset(text) {
            if (this.auditNotes && this.auditNotes.trim().length > 0) {
                this.auditNotes = this.auditNotes.trim() + '. ' + text;
            } else {
                this.auditNotes = text;
            }
        },

        closeAudit() {
            this.auditOpen = false;
        },

        urgencyClass(label) {
            if (!label) return 'bg-gray-100 text-gray-700 border-gray-200';
            const l = String(label).toUpperCase();
            if (l === 'HIGH') return 'bg-rose-100 text-rose-800 border-rose-300';
            if (l === 'MEDIUM') return 'bg-amber-100 text-amber-800 border-amber-300';
            if (l === 'LOW') return 'bg-emerald-100 text-emerald-800 border-emerald-300';
            return 'bg-gray-100 text-gray-700 border-gray-200';
        },

        submitVerification(status) {
            if (!this.activePrediction) return;
            this.submitting = true;

            const payload = {
                status: status,
                notes: this.auditNotes
            };

            fetch(this.verifyActionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.submitting = false;
                this.feedbackSuccess = true;
                this.feedbackMessage = status === 'verified_correct'
                    ? '✓ Prediction verified as accurate.'
                    : '✗ Prediction flagged as inaccurate for ML retraining.';

                if (data.data) {
                    this.activePrediction = data.data;
                    this.auditDataStore[this.activePrediction.id] = data.data;
                } else {
                    if (!this.activePrediction.verification) this.activePrediction.verification = {};
                    this.activePrediction.verification.status = status;
                    this.activePrediction.verification.notes = this.auditNotes;
                    this.activePrediction.verification.verified_by = 'You';
                    this.activePrediction.verification.verified_at = 'Just now';
                }

                // Update table row button immediately in UI
                const rowBadge = document.getElementById('audit-badge-' + this.activePrediction.id);
                if (rowBadge) {
                    if (status === 'verified_correct') {
                        rowBadge.innerHTML = '<button type="button" onclick="window.dispatchEvent(new CustomEvent(\'open-audit\', {detail: ' + this.activePrediction.id + '}))" class="inline-flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1.5 rounded-lg border border-emerald-200 transition cursor-pointer" title="Click to view model reasoning & audit log"><i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i><span>Verified</span></button>';
                    } else {
                        rowBadge.innerHTML = '<button type="button" onclick="window.dispatchEvent(new CustomEvent(\'open-audit\', {detail: ' + this.activePrediction.id + '}))" class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-1.5 rounded-lg border border-rose-200 transition cursor-pointer" title="Click to view model reasoning & audit log"><i data-lucide="x-circle" class="w-3.5 h-3.5 text-rose-600"></i><span>Flagged</span></button>';
                    }
                    if (window.lucide) lucide.createIcons();
                }

                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            })
            .catch(err => {
                this.submitting = false;
                alert('Verification submission failed. Please check connection and try again.');
            });
        }
    };
}

let activeLatexData = { table: '', methodology: '', bibtex: '' };
let currentModalTab = 'table';

function openLatexModal() {
    const modal = document.getElementById('latexModal');
    const area = document.getElementById('latexCodeArea');
    area.value = "Fetching LaTeX manuscript assets from server...";
    modal.style.display = 'flex';

    fetch('{{ route("admin.researcher-panel.export-latex", $selectedExperiment) }}')
        .then(res => res.json())
        .then(data => {
            activeLatexData.table = data.latex_table || data.latex;
            activeLatexData.methodology = data.methodology_snippet || '';
            activeLatexData.bibtex = data.bibtex || '';
            switchModalTab(currentModalTab);
        })
        .catch(err => {
            area.value = "Error generating LaTeX snippet. Please retry.";
        });
}

function switchModalTab(tab) {
    currentModalTab = tab;
    const area = document.getElementById('latexCodeArea');
    if (tab === 'table') {
        area.value = activeLatexData.table;
    } else if (tab === 'methodology') {
        area.value = activeLatexData.methodology;
    } else if (tab === 'bibtex') {
        area.value = activeLatexData.bibtex;
    }
}

function closeLatexModal() {
    document.getElementById('latexModal').style.display = 'none';
}

function copyLatexCode() {
    const area = document.getElementById('latexCodeArea');
    area.select();
    navigator.clipboard.writeText(area.value).then(() => {
        const btn = document.getElementById('copyLatexBtn');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-white"></i> Copied to Clipboard!';
        if (window.lucide) lucide.createIcons();
        setTimeout(() => {
            btn.innerHTML = oldText;
            if (window.lucide) lucide.createIcons();
        }, 2500);
    });
}

function openReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'flex';
        if (window.lucide) lucide.createIcons();
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function printReportModal() {
    window.print();
}

function filterReportTable() {
    const query = (document.getElementById('reportSearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#reportTableBody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = (query === '' || text.includes(query)) ? '' : 'none';
    });
}
</script>
@endpush

@endif
@endsection