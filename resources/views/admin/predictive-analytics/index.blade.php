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
        'rule_based'          => ['icon' => 'ruler',        'label' => 'Rule-Based',          'color' => 'violet'],
        'decision_tree'       => ['icon' => 'git-branch',   'label' => 'Decision Tree',       'color' => 'sky'],
        'random_forest'       => ['icon' => 'trees',        'label' => 'Random Forest',       'color' => 'emerald'],
        'svm'                 => ['icon' => 'circle-dot',   'label' => 'SVM (RBF)',           'color' => 'amber'],
        'logistic_regression' => ['icon' => 'trending-up',  'label' => 'Logistic Regression', 'color' => 'rose'],
    ];
    $evalAlgos = array_merge($algos, [
        'ensemble' => ['icon' => 'layers', 'label' => '5-Model Ensemble', 'color' => 'indigo'],
    ]);
    $colorMap = [
        'violet'  => ['bg'=>'bg-violet-500', 'text'=>'text-violet-400', 'light'=>'bg-violet-50'],
        'sky'     => ['bg'=>'bg-sky-500',    'text'=>'text-sky-400',    'light'=>'bg-sky-50'],
        'emerald' => ['bg'=>'bg-emerald-500','text'=>'text-emerald-400','light'=>'bg-emerald-50'],
        'amber'   => ['bg'=>'bg-amber-500',  'text'=>'text-amber-400',  'light'=>'bg-amber-50'],
        'rose'    => ['bg'=>'bg-rose-500',   'text'=>'text-rose-400',   'light'=>'bg-rose-50'],
        'indigo'  => ['bg'=>'bg-indigo-600', 'text'=>'text-indigo-400', 'light'=>'bg-indigo-50'],
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
                ['key'=>'train',    'num'=>'2', 'icon'=>'cpu',         'label'=>'Training Engine',      'desc'=>'5-Model Training & Tuning'],
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
            <span>Researcher Panel (5-Model Matrix) →</span>
        </a>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         SECTION 1: DATA HUB
    ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'data'" x-transition.opacity>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            {{-- Upload & Ingest Dataset Card with Column Mapping Wizard --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6"
                 x-data="{
                     wizardStep: 1,
                     fileName: null,
                     dragging: false,
                     previewLoading: false,
                     previewError: null,
                     previewData: null,
                     selectedMappings: {},

                     inspectFile() {
                         const fileInput = this.$refs.fileInput;
                         if (!fileInput || !fileInput.files.length) {
                             this.previewError = 'Please select a CSV or XLSX file first.';
                             return;
                         }
                         this.previewLoading = true;
                         this.previewError = null;

                         const formData = new FormData();
                         formData.append('dataset_file', fileInput.files[0]);

                         fetch('{{ route('admin.predictive-analytics.preview-dataset') }}', {
                             method: 'POST',
                             headers: {
                                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                 'Accept': 'application/json'
                             },
                             body: formData
                         })
                         .then(res => {
                             if (!res.ok) return res.json().then(j => { throw new Error(j.message || 'Failed to inspect dataset.') });
                             return res.json();
                         })
                         .then(data => {
                             this.previewLoading = false;
                             if (data.status === 'success') {
                                 this.previewData = data;
                                 this.selectedMappings = Object.assign({}, data.suggested_mapping);
                                 this.wizardStep = 2;
                                 this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                             } else {
                                 this.previewError = data.message || 'Failed to parse file preview.';
                             }
                         })
                         .catch(err => {
                             this.previewLoading = false;
                             this.previewError = err.message || 'Error inspecting file.';
                         });
                     },

                     resetWizard() {
                         this.wizardStep = 1;
                         this.previewData = null;
                         this.selectedMappings = {};
                         this.previewError = null;
                         this.fileName = null;
                         if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                     }
                 }">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Upload Dataset</h3>
                            <p class="text-sm text-gray-500">CSV or XLSX — preview & schema mapping</p>
                        </div>
                    </div>
                    <span x-show="wizardStep === 2" class="text-xs bg-indigo-100 text-indigo-800 font-bold px-2.5 py-1 rounded-full">
                        Step 2: Schema Mapping
                    </span>
                </div>

                {{-- Alert for preview error --}}
                <div x-show="previewError" class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                    <span x-text="previewError"></span>
                </div>

                <form id="datasetUploadForm"
                      :action="wizardStep === 2 ? '{{ route('admin.predictive-analytics.import-mapped') }}' : '{{ route('admin.predictive-analytics.upload') }}'"
                      method="POST" enctype="multipart/form-data">
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

                    {{-- Step 1: File Dropzone --}}
                    <div x-show="wizardStep === 1">
                        <div class="mb-4"
                             @dragover.prevent="dragging = true" @dragleave="dragging = false"
                             @drop.prevent="dragging = false; fileName = $event.dataTransfer.files[0]?.name; $refs.fileInput.files = $event.dataTransfer.files">
                            <label class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl py-6 cursor-pointer transition"
                                   :class="dragging ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400 hover:bg-indigo-50/50'">
                                <i data-lucide="file-spreadsheet" class="w-6 h-6 text-indigo-400 mb-1"></i>
                                <span class="text-sm text-gray-600 font-medium" x-text="fileName || 'Drop CSV/XLSX or click to browse'"></span>
                                <input type="file" name="dataset_file" x-ref="fileInput" accept=".csv,.xlsx,.xls" class="sr-only" required
                                       @change="fileName = $event.target.files[0]?.name">
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 border border-gray-200">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i> Direct Upload
                            </button>
                            <button type="button" @click="inspectFile()" :disabled="previewLoading"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-semibold text-xs py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 shadow-xs">
                                <svg x-show="previewLoading" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <i data-lucide="table" class="w-3.5 h-3.5" x-show="!previewLoading"></i>
                                <span x-text="previewLoading ? 'Inspecting...' : 'Preview & Map Columns →'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Data Preview & Column Mapping --}}
                    <div x-show="wizardStep === 2" class="space-y-4">
                        <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-xl p-3 text-xs text-indigo-900 flex items-center justify-between">
                            <span class="flex items-center gap-1.5 font-semibold">
                                <i data-lucide="check-circle" class="w-4 h-4 text-indigo-600"></i>
                                Detected <strong x-text="previewData ? previewData.headers.length : 0"></strong> columns across <strong x-text="previewData ? previewData.total_rows : 0"></strong> rows
                            </span>
                            <button type="button" @click="resetWizard()" class="text-indigo-600 hover:text-indigo-800 font-bold underline">
                                Change File
                            </button>
                        </div>

                        {{-- Preview Rows Table --}}
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="bg-gray-50 px-3 py-1.5 border-b border-gray-200 text-2xs font-bold text-gray-500 uppercase tracking-wider">
                                File Sample (First 5 Rows)
                            </div>
                            <div class="overflow-x-auto max-h-40">
                                <table class="w-full text-2xs">
                                    <thead class="bg-gray-100 text-gray-600">
                                        <tr>
                                            <template x-for="header in (previewData ? previewData.headers : [])" :key="header">
                                                <th class="px-2 py-1.5 text-left font-mono font-semibold whitespace-nowrap" x-text="header"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(row, rIdx) in (previewData ? previewData.preview_rows : [])" :key="rIdx">
                                            <tr class="hover:bg-gray-50">
                                                <template x-for="header in (previewData ? previewData.headers : [])" :key="header">
                                                    <td class="px-2 py-1 text-gray-700 font-mono whitespace-nowrap" x-text="row[header] !== undefined && row[header] !== null ? row[header] : '—'"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Column Mapping Selectors --}}
                        <div class="border border-gray-200 rounded-xl p-3 bg-gray-50/50 max-h-60 overflow-y-auto space-y-2">
                            <p class="text-2xs font-bold text-gray-500 uppercase tracking-wider mb-1">
                                Map Columns to System Schema
                            </p>
                            <template x-for="(meta, targetKey) in (previewData ? previewData.target_fields : {})" :key="targetKey">
                                <div class="flex items-center justify-between gap-2 bg-white p-2 rounded-lg border border-gray-200">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 flex items-center gap-1">
                                            <span x-text="meta.label"></span>
                                            <span x-show="meta.required" class="text-rose-500 font-bold">*</span>
                                        </p>
                                        <p class="text-3xs text-gray-400 font-mono" x-text="targetKey"></p>
                                    </div>
                                    <select :name="'column_mapping[' + targetKey + ']'"
                                            x-model="selectedMappings[targetKey]"
                                            class="border border-gray-200 rounded-md px-2 py-1 text-xs font-mono text-gray-700 bg-white focus:ring-1 focus:ring-indigo-300 outline-none w-44">
                                        <option value="">— Skip / Auto —</option>
                                        <template x-for="h in (previewData ? previewData.headers : [])" :key="h">
                                            <option :value="h" :selected="selectedMappings[targetKey] === h" x-text="h"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button type="button" @click="wizardStep = 1"
                                    class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold py-2.5 rounded-xl transition">
                                ← Back
                            </button>
                            <button type="submit"
                                    class="w-2/3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 shadow-xs">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> Import & Preprocess Dataset
                            </button>
                        </div>
                    </div>
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

                {{-- Synthetic Generator Card with Noise Selector --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5"
                     x-data="{ noise: 0.08, rows: 600, missing: 0.0, bias: 'none', seed: 42, showAdvanced: false }">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center text-violet-600 shrink-0">
                            <i data-lucide="wand-sparkles" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Generate Synthetic + Kaggle Datasets</h3>
                            <p class="text-xs text-gray-500">Stochastic wear-noise telemetry generator for realistic model evaluation</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.predictive-analytics.generate') }}" method="POST" class="space-y-4">
                        @csrf

                        {{-- Noise Level Selector --}}
                        <div class="bg-gray-50/70 p-3.5 rounded-xl border border-gray-200/80 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="activity" class="w-3.5 h-3.5 text-violet-600"></i>
                                    Telemetry Noise Jitter
                                </label>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                      :class="noise <= 0.05 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : (noise <= 0.10 ? 'bg-violet-100 text-violet-800 border border-violet-200' : (noise <= 0.18 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-rose-100 text-rose-800 border border-rose-200'))"
                                      x-text="Math.round(noise * 100) + '% — ' + (noise <= 0.05 ? 'Clean Sensor' : (noise <= 0.10 ? 'Standard Benchmark' : (noise <= 0.18 ? 'Harsh Vibration' : 'Severe Noise')))">
                                </span>
                            </div>

                            {{-- Range Slider --}}
                            <div>
                                <input type="range" min="0.01" max="0.35" step="0.01" x-model.number="noise" name="noise"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-violet-600">
                                <div class="flex justify-between text-[10px] text-gray-400 font-medium mt-1">
                                    <span>1% (Clean)</span>
                                    <span>8% (Default)</span>
                                    <span>15% (Harsh)</span>
                                    <span>35% (Extreme)</span>
                                </div>
                            </div>

                            {{-- Quick Preset Pills --}}
                            <div class="flex items-center gap-1.5 pt-1 flex-wrap">
                                <span class="text-[11px] font-semibold text-gray-500 mr-1">Presets:</span>
                                <button type="button" @click="noise = 0.03"
                                        class="px-2 py-1 rounded-md text-xs font-semibold transition"
                                        :class="noise === 0.03 ? 'bg-violet-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100'">
                                    Clean 3%
                                </button>
                                <button type="button" @click="noise = 0.08"
                                        class="px-2 py-1 rounded-md text-xs font-semibold transition"
                                        :class="noise === 0.08 ? 'bg-violet-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100'">
                                    Default 8%
                                </button>
                                <button type="button" @click="noise = 0.15"
                                        class="px-2 py-1 rounded-md text-xs font-semibold transition"
                                        :class="noise === 0.15 ? 'bg-violet-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100'">
                                    Harsh 15%
                                </button>
                                <button type="button" @click="noise = 0.25"
                                        class="px-2 py-1 rounded-md text-xs font-semibold transition"
                                        :class="noise === 0.25 ? 'bg-violet-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100'">
                                    Severe 25%
                                </button>
                            </div>
                        </div>

                        {{-- Row Count Input & Presets --}}
                        <div class="flex items-center justify-between gap-3 bg-gray-50/70 p-3 rounded-xl border border-gray-200/80">
                            <div>
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider block">Sample Rows</label>
                                <span class="text-[11px] text-gray-400">Records per dataset</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="rows = 300"
                                        class="px-2 py-1 rounded text-xs font-medium border"
                                        :class="rows === 300 ? 'bg-violet-50 text-violet-700 border-violet-300 font-bold' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                    300
                                </button>
                                <button type="button" @click="rows = 600"
                                        class="px-2 py-1 rounded text-xs font-medium border"
                                        :class="rows === 600 ? 'bg-violet-50 text-violet-700 border-violet-300 font-bold' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                    600
                                </button>
                                <button type="button" @click="rows = 1000"
                                        class="px-2 py-1 rounded text-xs font-medium border"
                                        :class="rows === 1000 ? 'bg-violet-50 text-violet-700 border-violet-300 font-bold' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                    1000
                                </button>
                                <input type="number" name="rows" x-model.number="rows" min="100" max="2000" step="50"
                                       class="border border-gray-200 rounded-lg px-2.5 py-1 text-xs font-mono w-20 text-center font-bold text-gray-800 focus:ring-2 focus:ring-violet-300 outline-none bg-white">
                            </div>
                        </div>

                        {{-- Advanced Options Collapsible --}}
                        <div>
                            <button type="button" @click="showAdvanced = !showAdvanced"
                                    class="text-xs font-semibold text-violet-600 hover:text-violet-700 flex items-center gap-1">
                                <span x-text="showAdvanced ? 'Hide Advanced Options ▴' : 'Show Advanced Options (Missing Nulls, Bias, Seed) ▾'"></span>
                            </button>

                            <div x-show="showAdvanced" x-transition class="mt-2.5 p-3 rounded-xl bg-gray-50 border border-gray-200 space-y-2.5 text-xs">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-gray-600 uppercase text-[10px] mb-1">Missing Nulls Rate</label>
                                        <select name="missing" x-model.number="missing" class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700">
                                            <option value="0.0">0% (Complete Data)</option>
                                            <option value="0.05">5% (Occasional Dropouts)</option>
                                            <option value="0.10">10% (Faulty Telemetry)</option>
                                            <option value="0.20">20% (Sparse Sensors)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-600 uppercase text-[10px] mb-1">Operating Bias</label>
                                        <select name="bias" x-model="bias" class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700">
                                            <option value="none">None (Balanced Mixed)</option>
                                            <option value="mountain">Mountain (High Strain)</option>
                                            <option value="urban">Urban (Stop-and-Go)</option>
                                            <option value="highway">Highway (High Speed)</option>
                                            <option value="corporate">Corporate (Heavy Duty)</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-600 uppercase text-[10px] mb-1">Random Generator Seed</label>
                                    <input type="number" name="seed" x-model.number="seed"
                                           class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 font-mono">
                                </div>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit"
                                class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs py-2.5 rounded-xl transition flex items-center justify-center gap-2 shadow-xs">
                            <i data-lucide="cpu" class="w-4 h-4"></i>
                            <span>Generate Datasets (<span x-text="rows"></span> rows, <span x-text="Math.round(noise * 100) + '% noise'"></span>)</span>
                        </button>
                        <p class="text-[11px] text-gray-400 text-center">
                            Produces 1&times; Synthetic system dataset + 1&times; Kaggle benchmark dataset with selected noise.
                        </p>
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
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <a href="{{ route('admin.predictive-analytics.datasets.show', $ds) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/60 transition shadow-2xs">
                                        <i data-lucide="table" class="w-3.5 h-3.5"></i>
                                        <span>View Data</span>
                                    </a>

                                    <a href="{{ route('admin.predictive-analytics.datasets.download', $ds) }}"
                                       class="inline-flex items-center justify-center w-7 h-7 text-xs font-medium rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition"
                                       title="Download CSV">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    </a>

                                    @if(! $ds->experiments()->exists())
                                    <form action="{{ route('admin.predictive-analytics.datasets.delete', $ds) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this dataset?')">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center justify-center w-7 h-7 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg text-xs font-medium transition" title="Delete dataset">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-gray-400 text-xs px-1.5 py-0.5" title="Dataset has completed experiments">In use</span>
                                    @endif
                                </div>
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
                      x-data="{ loading: false, selectedDataset: '{{ request('dataset_id', $datasets->first()?->id ?? '') }}' }" @submit="loading = true" novalidate>
                    @csrf

                    @if($errors->any())
                    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs space-y-1">
                        @foreach($errors->all() as $err)
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0 text-rose-500"></i>
                            <span>{{ $err }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Dataset select --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-bold text-gray-700">Select Dataset</label>
                            @if(! $datasets->isEmpty())
                            <a :href="'{{ url('/admin/maintenance-alerts/predictive-analytics/datasets') }}/' + selectedDataset"
                               class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition"
                               title="Inspect table records">
                                <i data-lucide="table" class="w-3.5 h-3.5"></i>
                                <span>View Data Table →</span>
                            </a>
                            @endif
                        </div>
                        @if($datasets->isEmpty())
                        <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            No datasets available. Go to <strong>Data Hub</strong> first.
                        </p>
                        @else
                        <select name="dataset_id" x-model="selectedDataset" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                            @foreach($datasets as $ds)
                            <option value="{{ $ds->id }}" {{ request('dataset_id') == $ds->id ? 'selected' : '' }}>{{ $ds->name }} ({{ number_format($ds->row_count) }} rows)</option>
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

                    {{-- Split + CV + Seed --}}
                    <div class="grid grid-cols-3 gap-3 mb-4">
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
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Random Seed</label>
                            <input type="number" name="seed" value="42" min="1" max="999999"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"
                                   title="Seed for reproducible train/test split and stochastic models">
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
                                    <input type="number" name="svm_c" value="1.0" min="0.01" max="100" step="any"
                                           class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-300 outline-none mt-0.5">
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-bold text-gray-500 uppercase">SVM Gamma</label>
                                    <select name="svm_gamma" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 outline-none mt-0.5">
                                        <option value="scale">scale (default)</option>
                                        <option value="auto">auto</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">LR C</label>
                                    <input type="number" name="lr_C" value="1.0" min="0.01" max="100" step="any"
                                           class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-300 outline-none mt-0.5">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">LR Max Iter</label>
                                    <input type="number" name="lr_max_iter" value="1000" min="100" max="5000"
                                           class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-300 outline-none mt-0.5">
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-bold text-gray-500 uppercase">LR Solver</label>
                                    <select name="lr_solver" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-1 outline-none mt-0.5">
                                        <option value="lbfgs">lbfgs (default)</option>
                                        <option value="saga">saga</option>
                                        <option value="liblinear">liblinear</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading" :class="loading ? 'cursor-wait opacity-80' : ''"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-bold text-base py-3.5 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <i data-lucide="play-circle" class="w-4 h-4" x-show="!loading"></i>
                        <span x-text="loading ? 'Training in Progress (Evaluating 5 Models)...' : 'Run In-System Training & Evaluation'"></span>
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

        {{-- ── 5-Model + Ensemble Leaderboard Cards ── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            @foreach($evalAlgos as $key => $algo)
            @php $m = $metrics->get($key); @endphp
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 {{ $colorMap[$algo['color']]['bg'] ?? 'bg-gray-300' }}"></div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-lg {{ $colorMap[$algo['color']]['light'] ?? 'bg-gray-50' }} flex items-center justify-center">
                        <i data-lucide="{{ $algo['icon'] }}" class="w-3.5 h-3.5 {{ $colorMap[$algo['color']]['text'] ?? 'text-gray-500' }}"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700 truncate" title="{{ $algo['label'] }}">{{ $algo['label'] }}</span>
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
                        @foreach($evalAlgos as $key => $algo)
                        @php $m = $metrics->get($key); $yearly = $m?->yearly_accuracy_json ?? []; @endphp
                        <tr class="hover:bg-gray-50 {{ $key === 'ensemble' ? 'bg-indigo-50/30' : '' }}">
                            <td class="py-2.5 font-medium text-gray-800 text-xs flex items-center gap-1.5">
                                @if($key === 'ensemble')
                                <span class="w-2 h-2 rounded-full bg-indigo-600 shrink-0"></span>
                                @endif
                                <span>{{ $algo['label'] }}</span>
                            </td>
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
                <div class="flex gap-1 bg-gray-100 rounded-lg p-1 flex-wrap">
                    @foreach($evalAlgos as $key => $algo)
                    <button @click="cmTab = '{{ $key }}'"
                            :class="cmTab === '{{ $key }}' ? '{{ $colorMap[$algo['color']]['bg'] ?? 'bg-gray-400' }} text-white shadow-sm' : 'text-gray-500 hover:bg-white'"
                            class="px-3 py-1 rounded-md text-xs font-semibold transition">{{ $algo['label'] }}</button>
                    @endforeach
                </div>
            </div>

            @foreach($evalAlgos as $key => $algo)
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
        $benchLabels = ['Rule-Based', 'Decision Tree', 'Random Forest', 'SVM', 'Logistic Regression', '5-Model Ensemble'];
        $benchAlgos  = ['rule_based', 'decision_tree', 'random_forest', 'svm', 'logistic_regression', 'ensemble'];

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
