@extends('layouts.app')
@section('page-title', 'ML Data Hub & Training Engine')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .badge-low    { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    .badge-medium { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
    .badge-high   { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
    .badge-na     { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }
</style>
@endpush

@section('content')
@php
    $algos = [
        'rule_based'    => ['icon' => 'ruler', 'label' => 'Rule-Based', 'color' => 'violet'],
        'decision_tree' => ['icon' => 'git-branch', 'label' => 'Decision Tree', 'color' => 'sky'],
        'random_forest' => ['icon' => 'trees', 'label' => 'Random Forest', 'color' => 'emerald'],
        'svm'           => ['icon' => 'circle-dot', 'label' => 'SVM (RBF)', 'color' => 'amber'],
    ];
    $colorMap = [
        'violet'  => ['bg'=>'bg-violet-500','text'=>'text-violet-400','light'=>'bg-violet-50'],
        'sky'     => ['bg'=>'bg-sky-500','text'=>'text-sky-400','light'=>'bg-sky-50'],
        'emerald' => ['bg'=>'bg-emerald-500','text'=>'text-emerald-400','light'=>'bg-emerald-50'],
        'amber'   => ['bg'=>'bg-amber-500','text'=>'text-amber-400','light'=>'bg-amber-50'],
    ];
    $labelBadge = [
        'LOW'    => 'badge-low',
        'MEDIUM' => 'badge-medium',
        'HIGH'   => 'badge-high',
    ];
@endphp

{{-- ══════════════════════════════════════════════════════════════════════════
     HEADER CONTROL CARD
══════════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-slate-900 text-white text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded">
                    ML Pipeline Hub
                </span>
                <span class="text-gray-400 text-xs">·</span>
                <span class="text-xs text-gray-500 font-medium">In-System Python scikit-learn Training & Data Management</span>
            </div>
            <p class="text-sm text-gray-600 mt-1">
                Upload benchmark datasets, export live system usage records, tune hyperparameters, and execute multi-model cross-validation.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.researcher-panel') }}"
               class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2 transition shadow-2xs">
                <i data-lucide="microscope" class="w-4 h-4 text-indigo-300"></i> Open Researcher Panel →
            </a>
            <a href="{{ route('admin.maintenance') }}"
               class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 text-sm font-semibold px-3.5 py-2.5 rounded-lg flex items-center gap-2 transition shadow-2xs">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-gray-400"></i> Maintenance Alerts
            </a>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     WORKFLOW PIPELINE TABS (1. DATA HUB -> 2. TRAINING -> 3. EVALUATION)
══════════════════════════════════════════════════════════════════════════ --}}
<div x-data="{ tab: '{{ request('tab', 'data') }}' }" class="mb-0">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white border border-gray-200/80 rounded-xl p-2 mb-6 shadow-xs">
        <div class="flex items-center gap-1 bg-gray-100/80 p-1 rounded-lg overflow-x-auto">
            @foreach([
                ['key'=>'data',     'num'=>'1', 'icon'=>'database',    'label'=>'Data Hub',             'desc'=>'Upload & Live Export'],
                ['key'=>'train',    'num'=>'2', 'icon'=>'cpu',         'label'=>'Training Engine',      'desc'=>'4-Model Training & Tuning'],
                ['key'=>'analysis', 'num'=>'3', 'icon'=>'bar-chart-3', 'label'=>'Evaluation & Benchmarks', 'desc'=>'Accuracy & Metrics'],
            ] as $t)
            <button @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}' 
                        ? 'bg-white text-slate-900 shadow-xs border border-gray-200/80 font-bold' 
                        : 'text-gray-600 hover:text-gray-900 hover:bg-white/50 border border-transparent font-medium'"
                    class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg text-sm transition whitespace-nowrap shrink-0">
                <span :class="tab === '{{ $t['key'] }}' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                      class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center transition">
                    {{ $t['num'] }}
                </span>
                <i data-lucide="{{ $t['icon'] }}" class="w-3.5 h-3.5"></i>
                <span>{{ $t['label'] }}</span>
            </button>
            @endforeach
        </div>

        <a href="{{ route('admin.researcher-panel') }}"
           class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-indigo-700 bg-indigo-50/80 hover:bg-indigo-100 border border-indigo-200/80 whitespace-nowrap transition shrink-0 self-start sm:self-auto">
            <i data-lucide="microscope" class="w-3.5 h-3.5"></i>
            <span>Researcher Panel (4-Model Matrix) →</span>
        </a>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         SECTION 1: DATA HUB
    ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'data'" x-transition.opacity>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            {{-- Upload Dataset Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Upload Dataset</h3>
                        <p class="text-sm text-gray-500">CSV or XLSX — mapped to ML schema</p>
                    </div>
                </div>
                <form action="{{ route('admin.predictive-analytics.upload') }}" method="POST" enctype="multipart/form-data"
                      x-data="{ fileName: null, dragging: false }">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Dataset Name</label>
                        <input type="text" name="dataset_name" required placeholder="e.g. Fleet Q3 2026 Synthetic"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Dataset Tag</label>
                        <select name="source_type" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                            <option value="synthetic">Synthetic System Data</option>
                            <option value="kaggle_benchmark">Kaggle Benchmark Dataset</option>
                            <option value="system_live">Live System Export</option>
                            <option value="unlabeled_test">Unlabeled New Fleet Data</option>
                        </select>
                    </div>
                    <div class="mb-4"
                         @dragover.prevent="dragging = true" @dragleave="dragging = false"
                         @drop.prevent="dragging = false; fileName = $event.dataTransfer.files[0]?.name; $el.querySelector('input').files = $event.dataTransfer.files">
                        <label class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl py-6 cursor-pointer transition"
                               :class="dragging ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400 hover:bg-indigo-50/50'">
                            <i data-lucide="file-spreadsheet" class="w-6 h-6 text-indigo-400 mb-1"></i>
                            <span class="text-sm text-gray-600 font-medium" x-text="fileName || 'Drop CSV/XLSX or click to browse'"></span>
                            <input type="file" name="dataset_file" accept=".csv,.xlsx,.xls" class="sr-only" required
                                   @change="fileName = $event.target.files[0]?.name">
                        </label>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                        <i data-lucide="upload" class="w-4 h-4"></i> Upload Dataset
                    </button>
                </form>
            </div>

            {{-- Export System Data + Generate Synthetic --}}
            <div class="flex flex-col gap-4">
                {{-- Export Card --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i data-lucide="download-cloud" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Export System Data</h3>
                            <p class="text-sm text-gray-500">Build ML CSV from live vehicle + service records</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.predictive-analytics.export') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                            <i data-lucide="file-output" class="w-4 h-4"></i> Export Live System CSV
                        </button>
                    </form>
                </div>

                {{-- Synthetic Generator Card --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center text-violet-600">
                            <i data-lucide="wand-sparkles" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Generate Synthetic + Kaggle Datasets</h3>
                            <p class="text-sm text-gray-500">Stochastic wear-noise generation for realistic testing</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.predictive-analytics.generate') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="number" name="rows" value="600" min="100" max="2000"
                               class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-24 focus:ring-2 focus:ring-violet-300 outline-none">
                        <button type="submit" class="flex-1 bg-violet-600 hover:bg-violet-700 text-white font-semibold text-sm py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                            <i data-lucide="cpu" class="w-4 h-4"></i> Generate (rows)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Dataset Library --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <i data-lucide="archive" class="w-4 h-4 text-indigo-500"></i>
                    Dataset Library
                </h3>
                <span class="text-xs text-gray-500">{{ $datasets->count() }} datasets</span>
            </div>
            @if($datasets->isEmpty())
            <div class="py-12 text-center text-gray-400">
                <i data-lucide="folder-open" class="w-10 h-10 mx-auto mb-2 opacity-30"></i>
                <p class="text-sm">No datasets yet. Upload or generate one above.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Tag</th>
                            <th class="px-4 py-3 text-right">Rows</th>
                            <th class="px-4 py-3 text-left">Uploaded</th>
                            <th class="px-4 py-3 text-left">By</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($datasets as $ds)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $ds->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold border
                                    @if($ds->source_type === 'synthetic') badge-low
                                    @elseif($ds->source_type === 'kaggle_benchmark') bg-sky-100 text-sky-700 border-sky-200
                                    @elseif($ds->source_type === 'system_live') bg-violet-100 text-violet-700 border-violet-200
                                    @else badge-medium @endif">
                                    {{ $ds->source_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-700">{{ number_format($ds->row_count) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $ds->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $ds->uploader?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if(! $ds->experiments()->exists())
                                <form action="{{ route('admin.predictive-analytics.datasets.delete', $ds) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Delete this dataset?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-500 hover:text-rose-700 text-xs font-medium transition">Delete</button>
                                </form>
                                @else
                                <span class="text-gray-400 text-xs">In use</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         SECTION 2: TRAINING ENGINE
    ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'train'" x-transition.opacity style="display:none">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Training Config Form --}}
            <div class="lg:col-span-1 bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i data-lucide="settings-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Training Configuration</h3>
                        <p class="text-sm text-gray-500">Configure and launch experiment</p>
                    </div>
                </div>

                <form action="{{ route('admin.predictive-analytics.run') }}" method="POST"
                      x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    {{-- Dataset select --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Select Dataset</label>
                        @if($datasets->isEmpty())
                        <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            No datasets available. Go to <strong>Data Hub</strong> first.
                        </p>
                        @else
                        <select name="dataset_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                            @foreach($datasets as $ds)
                            <option value="{{ $ds->id }}">{{ $ds->name }} ({{ number_format($ds->row_count) }} rows)</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    {{-- Algorithm checkboxes --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Algorithms to Run</label>
                        <div class="space-y-2">
                            @foreach($algos as $key => $algo)
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="algorithms[]" value="{{ $key }}" checked
                                       class="w-4 h-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="w-5 h-5 rounded bg-{{ $colorMap[$algo['color']]['light'] ?? 'gray-50' }} flex items-center justify-center">
                                    <i data-lucide="{{ $algo['icon'] }}" class="w-3 h-3 {{ $colorMap[$algo['color']]['text'] ?? 'text-gray-500' }}"></i>
                                </span>
                                <span class="text-sm text-gray-700">{{ $algo['label'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Split + CV --}}
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Train Split</label>
                            <select name="split_ratio" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                                <option value="0.70">70 / 30</option>
                                <option value="0.80" selected>80 / 20</option>
                                <option value="0.85">85 / 15</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">CV Folds</label>
                            <select name="cv_folds" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                                <option value="3">3-fold</option>
                                <option value="5" selected>5-fold</option>
                                <option value="10">10-fold</option>
                            </select>
                        </div>
                    </div>

                    {{-- Hyperparameters accordion --}}
                    <div x-data="{ open: false }" class="mb-5">
                        <button type="button" @click="open = !open"
                                class="flex items-center justify-between w-full text-sm font-bold text-gray-700 bg-gray-50 border border-gray-200 rounded-lg px-3.5 py-2.5 hover:bg-gray-100 transition">
                            <span class="flex items-center gap-2"><i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Hyperparameter Quick-Config</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-transition class="mt-2 border border-gray-200 rounded-xl p-4 space-y-3 bg-gray-50">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">DT Max Depth</label>
                                    <input type="number" name="dt_max_depth" placeholder="None (auto)" min="1" max="30"
                                           class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-300 outline-none mt-0.5">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">RF Estimators</label>
                                    <input type="number" name="rf_n_estimators" value="100" min="10" max="500"
                                           class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-300 outline-none mt-0.5">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">RF Max Depth</label>
                                    <input type="number" name="rf_max_depth" placeholder="None" min="1" max="30"
                                           class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-300 outline-none mt-0.5">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">SVM C</label>
                                    <input type="number" name="svm_c" value="1.0" min="0.01" max="100" step="0.1"
                                           class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-300 outline-none mt-0.5">
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-bold text-gray-500 uppercase">SVM Gamma</label>
                                    <select name="svm_gamma" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 outline-none mt-0.5">
                                        <option value="scale">scale (default)</option>
                                        <option value="auto">auto</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-bold text-base py-3.5 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <i data-lucide="play-circle" class="w-4 h-4" x-show="!loading"></i>
                        <span x-text="loading ? 'Training in Progress...' : 'Run In-System Training & Evaluation'"></span>
                    </button>
                </form>
            </div>

            {{-- Experiment History --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-base">Experiment History</h3>
                </div>
                @if($experiments->isEmpty())
                <div class="py-16 text-center text-gray-400">
                    <i data-lucide="flask-conical" class="w-10 h-10 mx-auto mb-2 opacity-30"></i>
                    <p class="text-sm">No experiments yet. Run your first training above.</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-left">Run Name</th>
                                <th class="px-4 py-3 text-left">Dataset</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Best Acc.</th>
                                <th class="px-4 py-3 text-left">Run</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($experiments as $exp)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-900 text-xs">{{ $exp->run_name }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ Str::limit($exp->dataset?->name ?? '—', 25) }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full border
                                        @if($exp->status === 'completed') badge-low
                                        @elseif($exp->status === 'running') bg-sky-100 text-sky-700 border-sky-200
                                        @elseif($exp->status === 'failed') badge-high
                                        @else badge-medium @endif">
                                        {{ ucfirst($exp->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700">
                                    @php
                                        $bestAcc = $exp->metrics->max('accuracy');
                                    @endphp
                                    {{ $bestAcc ? round($bestAcc * 100, 1) . '%' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $exp->created_at->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         SECTION 3: EVALUATION & BENCHMARKS
    ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'analysis'" x-transition.opacity style="display:none">
        @if(! $latestCompleted)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-10 text-center text-amber-700">
            <i data-lucide="flask-conical" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="font-semibold">No completed experiments yet.</p>
            <p class="text-sm mt-1">Go to <strong>Step 2 — Training Engine</strong> and run an experiment first.</p>
        </div>
        @else

        {{-- ── 4-Model Leaderboard Cards ── --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @foreach($algos as $key => $algo)
            @php $m = $metrics->get($key); @endphp
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 {{ $colorMap[$algo['color']]['bg'] ?? 'bg-gray-300' }}"></div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-lg {{ $colorMap[$algo['color']]['light'] ?? 'bg-gray-50' }} flex items-center justify-center">
                        <i data-lucide="{{ $algo['icon'] }}" class="w-3.5 h-3.5 {{ $colorMap[$algo['color']]['text'] ?? 'text-gray-500' }}"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">{{ $algo['label'] }}</span>
                </div>
                @if($m)
                <div class="space-y-2">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Accuracy</p>
                        <p class="text-2xl font-extrabold text-gray-900">{{ round($m->accuracy * 100, 1) }}%</p>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-xs">
                        <div>
                            <p class="text-gray-500 text-xs font-semibold">Macro F1</p>
                            <p class="font-bold text-gray-700">{{ round($m->f1_macro * 100, 1) }}%</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs font-semibold">HIGH Recall</p>
                            <p class="font-bold text-rose-600">{{ round($m->high_urgency_recall * 100, 1) }}%</p>
                        </div>
                    </div>
                </div>
                @else
                <p class="text-gray-400 text-sm">Not run</p>
                @endif
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Benchmark Comparison Chart --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5">
                <h3 class="font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-500"></i>
                    Synthetic vs. Kaggle Benchmark Accuracy
                </h3>
                <canvas id="benchmarkChart" height="200"></canvas>
            </div>

            {{-- Yearly Accuracy Table --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 overflow-auto">
                <h3 class="font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <i data-lucide="calendar-range" class="w-4 h-4 text-indigo-500"></i>
                    Yearly Accuracy Breakdown (Journal Reporting)
                </h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs font-bold text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-2 text-left">Model</th>
                            @foreach(['2024','2025','2026','overall'] as $yr)
                            <th class="pb-2 text-right">{{ ucfirst($yr) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($algos as $key => $algo)
                        @php $m = $metrics->get($key); $yearly = $m?->yearly_accuracy_json ?? []; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-2.5 font-medium text-gray-800 text-xs">{{ $algo['label'] }}</td>
                            @foreach(['2024','2025','2026','overall'] as $yr)
                            <td class="py-2.5 text-right font-mono text-xs
                                @if(isset($yearly[$yr]) && $yearly[$yr] >= 90) text-emerald-600 font-bold
                                @elseif(isset($yearly[$yr]) && $yearly[$yr] >= 80) text-amber-600
                                @elseif(isset($yearly[$yr])) text-rose-600
                                @else text-gray-400 @endif">
                                {{ isset($yearly[$yr]) ? $yearly[$yr] . '%' : '—' }}
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Confusion Matrices + Feature Importance Tabs --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5" x-data="{ cmTab: 'rule_based' }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <i data-lucide="grid-3x3" class="w-4 h-4 text-indigo-500"></i>
                    Confusion Matrix & Feature Importance
                </h3>
                <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                    @foreach($algos as $key => $algo)
                    <button @click="cmTab = '{{ $key }}'"
                            :class="cmTab === '{{ $key }}' ? '{{ $colorMap[$algo['color']]['bg'] ?? 'bg-gray-400' }} text-white shadow-sm' : 'text-gray-500 hover:bg-white'"
                            class="px-3 py-1 rounded-md text-xs font-semibold transition">{{ $algo['label'] }}</button>
                    @endforeach
                </div>
            </div>

            @foreach($algos as $key => $algo)
            @php $m = $metrics->get($key); $cm = $m?->confusion_matrix_json; $fi = $m?->extra_meta_json['feature_importances'] ?? null; @endphp
            <div x-show="cmTab === '{{ $key }}'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Confusion Matrix --}}
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase mb-3">Confusion Matrix (Pred ↓ / Actual →)</p>
                    @if($cm)
                    @php
                        $labels = ['LOW', 'MED', 'HIGH'];
                        $maxVal = collect($cm)->flatten()->max();
                    @endphp
                    <div class="overflow-auto">
                        <table class="text-xs font-mono text-center rounded-xl overflow-hidden border border-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 bg-gray-50 text-gray-400 text-left font-normal">Pred \ Actual</th>
                                    @foreach($labels as $l)
                                    <th class="px-5 py-2 bg-gray-50 font-bold text-gray-700">{{ $l }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cm as $ri => $row)
                                <tr>
                                    <td class="px-3 py-3 font-bold text-gray-700 bg-gray-50">{{ $labels[$ri] ?? $ri }}</td>
                                    @foreach($row as $ci => $val)
                                    @php
                                        $isCorrect = $ri === $ci;
                                        $opacity = $maxVal > 0 ? round(($val / $maxVal) * 60) + 10 : 10;
                                        $bgStyle = $isCorrect
                                            ? "background:rgba(16,185,129,0.{$opacity})"
                                            : ($val > 0 ? "background:rgba(244,63,94,0.{$opacity})" : '');
                                    @endphp
                                    <td class="px-5 py-3 font-bold {{ $isCorrect ? 'text-emerald-700' : ($val > 0 ? 'text-rose-600' : 'text-gray-300') }}"
                                        style="{{ $bgStyle }}">{{ $val }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-gray-400 text-sm">No confusion matrix data.</p>
                    @endif
                </div>

                {{-- Feature Importance / Extra Meta --}}
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase mb-3">
                        {{ $fi ? 'Feature Importances (Top 10)' : 'Model Metadata' }}
                    </p>
                    @if($fi)
                    @php
                        $fiSorted = collect($fi)->sortDesc()->take(8);
                        $maxFi = $fiSorted->max();
                    @endphp
                    <div class="space-y-1.5">
                        @foreach($fiSorted as $fname => $fval)
                        <div>
                            <div class="flex justify-between text-xs mb-0.5">
                                <span class="text-gray-700 font-medium truncate max-w-[70%]">{{ $fname }}</span>
                                <span class="text-gray-500 font-mono">{{ round($fval * 100, 1) }}%</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $colorMap[$algo['color']]['bg'] ?? 'bg-indigo-500' }} rounded-full"
                                     style="width: {{ $maxFi > 0 ? round(($fval / $maxFi) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @elseif($m && $m->extra_meta_json)
                    <div class="space-y-1 text-xs">
                        @foreach($m->extra_meta_json as $k => $v)
                        @if(! is_array($v))
                        <div class="flex justify-between border-b border-gray-100 py-1.5">
                            <span class="text-gray-500 capitalize">{{ str_replace('_', ' ', $k) }}</span>
                            <span class="font-mono font-semibold text-gray-700">{{ is_float($v) ? round($v * 100, 2) . '%' : $v }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-400 text-sm">No metadata available for this algorithm.</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    </div>

@push('scripts')
<script>
// ── Benchmark Chart ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('benchmarkChart');
    if (!ctx) return;

    @php
        $benchLabels = ['Rule-Based', 'Decision Tree', 'Random Forest', 'SVM'];
        $benchAlgos  = ['rule_based', 'decision_tree', 'random_forest', 'svm'];

        $synData = [];
        $kagData = [];
        foreach ($benchAlgos as $a) {
            $sm = $syntheticExp?->metrics?->firstWhere('algorithm', $a);
            $km = $kaggleExp?->metrics?->firstWhere('algorithm', $a);
            $synData[] = $sm ? round($sm->accuracy * 100, 1) : null;
            $kagData[] = $km ? round($km->accuracy * 100, 1) : null;
        }
    @endphp

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($benchLabels),
            datasets: [
                {
                    label: 'Synthetic System Data',
                    data: @json($synData),
                    backgroundColor: 'rgba(99, 102, 241, 0.75)',
                    borderColor: '#6366f1',
                    borderWidth: 1.5,
                    borderRadius: 6,
                },
                {
                    label: 'Kaggle Benchmark',
                    data: @json($kagData),
                    backgroundColor: 'rgba(16, 185, 129, 0.65)',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.parsed.y !== null ? `${ctx.dataset.label}: ${ctx.parsed.y}%` : 'No data'
                    }
                }
            },
            scales: {
                y: {
                    min: 0, max: 100,
                    ticks: { callback: v => v + '%', font: { size: 10 } },
                    grid: { color: '#f1f5f9' }
                },
                x: { ticks: { font: { size: 10 } }, grid: { display: false } }
            }
        }
    });
});

</script>
@endpush

@endsection
