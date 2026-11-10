@extends('layouts.app')
@section('page-title', 'ML Colab Lab — Interactive 4-Model Notebook')

@push('head')
<!-- CodeMirror for authentic Google Colab editor experience -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<style>
    /* Google Colab Notebook Aesthetic */
    .colab-cell { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; transition: border-color 0.2s, box-shadow 0.2s; overflow: hidden; }
    .colab-cell:focus-within, .colab-cell.active-cell { border-color: #6366f1; box-shadow: 0 0 0 1px #6366f1; }
    .colab-play-btn { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .colab-output { background: #0d1117; color: #58a6ff; font-family: 'JetBrains Mono', monospace; }
    .CodeMirror { height: auto; min-height: 140px; font-family: 'JetBrains Mono', monospace; font-size: 14px; line-height: 1.6; border-radius: 0.5rem; }
    .CodeMirror-gutters { background: #1e1e2e; border-right: 1px solid #2e2e42; }
    .CodeMirror-linenumber { color: #6272a4; }
</style>
@endpush

@section('content')
<div x-data="colabNotebook()" x-init="init()" class="space-y-4">

    {{-- ══════════════════════════════════════════════════════════════════════
         COLAB HEADER BAR (TITLE, MENU, HARDWARE MONITOR)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-amber-500/10 text-amber-800 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded border border-amber-500/20 flex items-center gap-1.5">
                        <i data-lucide="terminal" class="w-3.5 h-3.5 text-amber-600"></i>
                        Colab Code Lab
                    </span>
                    <span class="text-gray-500 text-sm font-mono">teraju_setia_predictive_models.ipynb</span>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-600 mt-1">
                    <span class="flex items-center gap-1 text-emerald-600 font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Python 3.13 Engine Connected
                    </span>
                    <span>•</span>
                    <span class="font-mono">scikit-learn 1.6</span>
                    <span>•</span>
                    <span class="font-mono">pandas 2.3</span>
                    <span>•</span>
                    <span class="text-gray-400">Press <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded text-xs font-mono font-bold">Shift + Enter</kbd> to run cell</span>
                </div>
            </div>

            {{-- Colab Actions Toolbar --}}
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Dataset Picker --}}
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-bold text-gray-600 uppercase">Dataset:</label>
                    <select x-model="selectedDatasetId"
                            class="text-sm font-semibold bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-gray-700 focus:ring-2 focus:ring-indigo-300 outline-none cursor-pointer">
                        @foreach($datasets as $ds)
                        <option value="{{ $ds->id }}">{{ $ds->name }} ({{ number_format($ds->row_count) }} rows)</option>
                        @endforeach
                    </select>
                </div>

                {{-- Run All Cells Button --}}
                <button type="button" @click="runAllCells()" :disabled="isRunning"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white px-4 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-2xs transition cursor-pointer">
                    <i data-lucide="play" class="w-4 h-4" x-show="!isRunning"></i>
                    <svg x-show="isRunning" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="isRunning ? 'Executing Notebook...' : '▶ Run All Cells'"></span>
                </button>

                {{-- Commit as Experiment --}}
                <button type="button" @click="commitExperiment()" :disabled="isCommitting || isRunning"
                        class="bg-slate-900 hover:bg-slate-800 disabled:bg-slate-500 text-white px-4 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-2xs transition cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4 text-indigo-300" x-show="!isCommitting"></i>
                    <svg x-show="isCommitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="isCommitting ? 'Committing...' : 'Commit as Experiment'"></span>
                </button>

                {{-- Reset Button --}}
                <button type="button" @click="resetCodeToDefaults()"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3.5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition cursor-pointer"
                        title="Reset all cells to original templates">
                    <i data-lucide="rotate-ccw" class="w-4 h-4 text-gray-500"></i> Reset Code
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         NOTEBOOK CELLS (1 TO 6)
    ══════════════════════════════════════════════════════════════════════ --}}
    @foreach($defaultCells as $cellKey => $cellMeta)
    <div class="colab-cell" id="card_{{ $cellKey }}">
        {{-- Cell Header Bar --}}
        <div class="px-4 py-3 bg-gray-50/90 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                {{-- Play / Run Button --}}
                <button type="button" @click="runSingleCell('{{ $cellKey }}')" :disabled="cellStates['{{ $cellKey }}'].loading"
                        class="colab-play-btn bg-white hover:bg-indigo-50 text-indigo-600 border border-gray-200 shadow-2xs cursor-pointer"
                        title="Run this cell (Shift + Enter)">
                    <template x-if="!cellStates['{{ $cellKey }}'].loading">
                        <i data-lucide="play" class="w-4 h-4 ml-0.5 text-indigo-600"></i>
                    </template>
                    <template x-if="cellStates['{{ $cellKey }}'].loading">
                        <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                </button>

                <div>
                    <h4 class="font-bold text-gray-900 text-sm font-mono">{{ $cellMeta['title'] }}</h4>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $cellMeta['description'] }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-mono">
                <template x-if="cellStates['{{ $cellKey }}'].executionTime">
                    <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-md text-xs font-bold font-mono"
                          x-text="cellStates['{{ $cellKey }}'].executionTime + 'ms'"></span>
                </template>
                <template x-if="cellStates['{{ $cellKey }}'].status === 'error'">
                    <span class="text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-md text-xs font-bold">Execution Error</span>
                </template>
            </div>
        </div>

        {{-- Code Editor Area (Textarea Fallback + CodeMirror Integration) --}}
        <div class="p-3 bg-white">
            <textarea id="editor_{{ $cellKey }}"
                      rows="8"
                      class="w-full bg-[#1e1e2e] text-[#f8f8f2] font-mono text-sm p-4 rounded-xl border border-gray-700 leading-relaxed outline-none focus:ring-2 focus:ring-indigo-500 shadow-inner resize-y">{{ $cellMeta['code'] }}</textarea>
        </div>

        {{-- Running Console Output Area (Interactive & Always Visible) --}}
        <div class="colab-output p-4 text-sm font-mono overflow-x-auto whitespace-pre leading-relaxed border-t border-[#21262d]">
            {{-- Console Header --}}
            <div class="flex items-center justify-between pb-2 mb-2 border-b border-[#21262d] text-xs font-mono text-gray-400">
                <span class="flex items-center gap-1.5 text-indigo-400 font-semibold">
                    <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                    <span>Running Console Output</span>
                </span>
                <template x-if="cellStates['{{ $cellKey }}'].executionTime">
                    <span class="text-emerald-400 font-bold" x-text="'Completed in ' + cellStates['{{ $cellKey }}'].executionTime + ' ms'"></span>
                </template>
                <template x-if="!cellStates['{{ $cellKey }}'].executionTime && !cellStates['{{ $cellKey }}'].loading">
                    <span class="text-gray-500">Ready to run (Click ▶ or press Shift+Enter)</span>
                </template>
            </div>

            {{-- Live Running State --}}
            <div x-show="cellStates['{{ $cellKey }}'].loading" class="flex items-center gap-2 text-amber-400 font-bold py-2 animate-pulse">
                <svg class="animate-spin w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>[Executing in Python 3.13 kernel...]</span>
            </div>

            {{-- Idle Prompt (Before run) --}}
            <div x-show="!cellStates['{{ $cellKey }}'].loading && !cellStates['{{ $cellKey }}'].stdout && !cellStates['{{ $cellKey }}'].stderr"
                 class="text-gray-500 italic py-1 text-xs">
                // Console idle. Click the play button (▶) or press Shift+Enter to run this cell.
            </div>

            {{-- Output Stdout --}}
            <template x-if="cellStates['{{ $cellKey }}'].stdout">
                <div class="text-slate-100 py-1 leading-relaxed" x-text="cellStates['{{ $cellKey }}'].stdout"></div>
            </template>

            {{-- Output Stderr / Error Tracebacks --}}
            <template x-if="cellStates['{{ $cellKey }}'].stderr">
                <div class="text-rose-400 bg-rose-950/40 border border-rose-800/40 p-3 rounded-lg font-bold mt-2 whitespace-pre leading-relaxed" x-text="cellStates['{{ $cellKey }}'].stderr"></div>
            </template>
        </div>
    </div>
    @endforeach

</div>

@push('scripts')
<!-- Load CodeMirror Scripts explicitly in scripts stack -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>

<script>
function colabNotebook() {
    return {
        selectedDatasetId: '{{ $selectedDataset?->id ?? 1 }}',
        isRunning: false,
        isCommitting: false,
        editors: {},
        cellKeys: ['cell_1', 'cell_2', 'cell_3', 'cell_4', 'cell_5', 'cell_6'],
        defaultTemplates: @json($defaultCells),
        cellStates: {
            cell_1: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_2: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_3: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_4: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_5: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_6: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
        },

        init() {
            const setupCM = () => {
                if (typeof CodeMirror === 'undefined') {
                    setTimeout(setupCM, 80);
                    return;
                }
                this.cellKeys.forEach(k => {
                    if (this.editors[k]) return;
                    const el = document.getElementById('editor_' + k);
                    if (!el) return;

                    try {
                        const cm = CodeMirror.fromTextArea(el, {
                            mode: 'python',
                            theme: 'dracula',
                            lineNumbers: true,
                            indentUnit: 4,
                            tabSize: 4,
                            indentWithTabs: false,
                            extraKeys: {
                                'Shift-Enter': () => this.runSingleCell(k),
                                'Ctrl-Enter': () => this.runSingleCell(k),
                            }
                        });
                        this.editors[k] = cm;
                    } catch (e) {
                        console.error('CodeMirror init failed for', k, e);
                    }
                });

                if (window.lucide) lucide.createIcons();
            };

            this.$nextTick(() => {
                setupCM();
            });
        },

        getAllCellCode() {
            const out = {};
            this.cellKeys.forEach(k => {
                if (this.editors[k]) {
                    out[k] = this.editors[k].getValue();
                } else {
                    const el = document.getElementById('editor_' + k);
                    out[k] = (el && el.value) ? el.value : (this.defaultTemplates[k]?.code || '');
                }
            });
            return out;
        },

        runSingleCell(cellKey) {
            this.cellStates[cellKey].loading = true;
            this.cellStates[cellKey].stderr = '';
            this.cellStates[cellKey].stdout = '';
            this.cellStates[cellKey].status = 'running';

            const payload = {
                dataset_id: this.selectedDatasetId,
                action: 'run_single_cell',
                cell_key: cellKey,
                cells: this.getAllCellCode(),
                _token: '{{ csrf_token() }}'
            };

            fetch('{{ route("admin.researcher-colab.run") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.cellStates[cellKey].loading = false;
                const cellRes = (data.cell_results && data.cell_results[cellKey]) ? data.cell_results[cellKey] : null;

                if (cellRes) {
                    this.cellStates[cellKey].stdout = cellRes.stdout || '';
                    this.cellStates[cellKey].stderr = cellRes.stderr || '';
                    this.cellStates[cellKey].executionTime = cellRes.execution_time_ms;
                    this.cellStates[cellKey].status = cellRes.status;
                } else if (data.stderr) {
                    this.cellStates[cellKey].stderr = data.stderr;
                    this.cellStates[cellKey].status = 'error';
                }
                if (window.lucide) lucide.createIcons();
            })
            .catch(err => {
                this.cellStates[cellKey].loading = false;
                this.cellStates[cellKey].stderr = 'Execution failed: ' + err.message;
                this.cellStates[cellKey].status = 'error';
                if (window.lucide) lucide.createIcons();
            });
        },

        runAllCells() {
            this.isRunning = true;
            this.cellKeys.forEach(k => {
                this.cellStates[k].loading = true;
                this.cellStates[k].stdout = '';
                this.cellStates[k].stderr = '';
                this.cellStates[k].executionTime = null;
                this.cellStates[k].status = 'running';
            });

            const payload = {
                dataset_id: this.selectedDatasetId,
                action: 'run_all',
                cells: this.getAllCellCode(),
                _token: '{{ csrf_token() }}'
            };

            fetch('{{ route("admin.researcher-colab.run") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isRunning = false;
                this.cellKeys.forEach(k => {
                    this.cellStates[k].loading = false;
                    const cr = (data.cell_results && data.cell_results[k]) ? data.cell_results[k] : null;
                    if (cr) {
                        this.cellStates[k].stdout = cr.stdout || '';
                        this.cellStates[k].stderr = cr.stderr || '';
                        this.cellStates[k].executionTime = cr.execution_time_ms;
                        this.cellStates[k].status = cr.status;
                    } else if (data.stderr && k === 'cell_1') {
                        this.cellStates[k].stderr = data.stderr;
                        this.cellStates[k].status = 'error';
                    } else {
                        this.cellStates[k].status = 'idle';
                    }
                });
                if (window.lucide) lucide.createIcons();
            })
            .catch(err => {
                this.isRunning = false;
                this.cellKeys.forEach(k => {
                    this.cellStates[k].loading = false;
                });
                alert('Notebook execution error: ' + err.message);
                if (window.lucide) lucide.createIcons();
            });
        },

        commitExperiment() {
            const runName = prompt('Enter Experiment Name for this Colab Run:', 'Colab Custom Run #' + Math.floor(Math.random() * 900 + 100));
            if (!runName) return;

            this.isCommitting = true;
            const payload = {
                dataset_id: this.selectedDatasetId,
                run_name: runName,
                cells: this.getAllCellCode(),
                _token: '{{ csrf_token() }}'
            };

            fetch('{{ route("admin.researcher-colab.commit") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isCommitting = false;
                if (data.status === 'success' && data.redirect_url) {
                    alert('Experiment saved! Redirecting to Researcher Panel to evaluate predictions and confidence scores...');
                    window.location.href = data.redirect_url;
                } else {
                    alert('Commit failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                this.isCommitting = false;
                alert('Commit failed: ' + err.message);
            });
        },

        resetCodeToDefaults() {
            if (!confirm('Reset all 6 code cells to original default code templates? Any unsaved edits will be lost.')) return;
            this.cellKeys.forEach(k => {
                const code = this.defaultTemplates[k]?.code || '';
                if (this.editors[k]) {
                    this.editors[k].setValue(code);
                }
                const el = document.getElementById('editor_' + k);
                if (el) el.value = code;
                this.cellStates[k].stdout = '';
                this.cellStates[k].stderr = '';
                this.cellStates[k].executionTime = null;
                this.cellStates[k].status = 'idle';
            });
        }
    };
}
</script>
@endpush
@endsection
