@extends('layouts.app')
@section('page-title', 'ML Colab Lab — Interactive Multi-Model Notebook')

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
    .nav-tab-active { background-color: #0f172a; color: #ffffff !important; box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
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
                    <span class="bg-indigo-50 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded border border-indigo-200"
                          x-text="(cellKeys.length) + ' Cells • ' + (cellKeys.length - 2) + ' Models'"></span>
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
                            class="text-sm font-semibold bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-300 outline-none cursor-pointer">
                        @foreach($datasets as $ds)
                        <option value="{{ $ds->id }}">{{ $ds->name }} ({{ number_format($ds->row_count) }} rows)</option>
                        @endforeach
                    </select>
                </div>

                {{-- Run All Cells Button --}}
                <button type="button" @click="runAllCells()" :disabled="isRunning"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white px-3.5 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-2xs transition cursor-pointer">
                    <i data-lucide="play" class="w-4 h-4" x-show="!isRunning"></i>
                    <svg x-show="isRunning" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="isRunning ? 'Executing Notebook...' : '▶ Run All Cells'"></span>
                </button>

                {{-- Commit as Experiment --}}
                <button type="button" @click="commitExperiment()" :disabled="isCommitting || isRunning"
                        class="bg-slate-900 hover:bg-slate-800 disabled:bg-slate-500 text-white px-3.5 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-2xs transition cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4 text-indigo-300" x-show="!isCommitting"></i>
                    <svg x-show="isCommitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="isCommitting ? 'Committing...' : 'Commit Run'"></span>
                </button>

                {{-- Reset Button --}}
                <button type="button" @click="resetCodeToDefaults()"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold flex items-center gap-1.5 transition cursor-pointer"
                        title="Reset all cells to default templates">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-gray-500"></i> Reset
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         MODEL NAVIGATOR TAB BAR (INDIVIDUAL PANES + ADD ALGORITHM MODEL)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        {{-- Navigation Tabs Strip --}}
        <div class="flex items-center gap-1 overflow-x-auto py-1 scrollbar-thin">
            {{-- All Cells (Notebook View) --}}
            <button type="button" @click="setActivePane('all')"
                    :class="activePane === 'all' ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer">
                <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                <span>All Cells</span>
                <span class="ml-0.5 px-1.5 py-0.2 bg-slate-700 text-white text-[10px] rounded-full"
                      :class="activePane === 'all' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'"
                      x-text="cellKeys.length"></span>
            </button>

            <div class="h-4 w-px bg-gray-200 mx-1"></div>

            {{-- Cell 1: Data Pipeline --}}
            <button type="button" @click="setActivePane('cell_1')"
                    :class="activePane === 'cell_1' ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer">
                <i data-lucide="database" class="w-3.5 h-3.5 text-sky-500"></i>
                <span>Pipeline</span>
            </button>

            {{-- Cell 2: Rule-Based Model --}}
            <button type="button" @click="setActivePane('cell_2')"
                    :class="activePane === 'cell_2' ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer">
                <i data-lucide="cpu" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>1. Rule-Based</span>
            </button>

            {{-- Cell 3: Decision Tree --}}
            <button type="button" @click="setActivePane('cell_3')"
                    :class="activePane === 'cell_3' ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer">
                <i data-lucide="git-fork" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>2. Decision Tree</span>
            </button>

            {{-- Cell 4: Random Forest --}}
            <button type="button" @click="setActivePane('cell_4')"
                    :class="activePane === 'cell_4' ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer">
                <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-500"></i>
                <span>3. Random Forest</span>
            </button>

            {{-- Cell 5: SVM --}}
            <button type="button" @click="setActivePane('cell_5')"
                    :class="activePane === 'cell_5' ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer">
                <i data-lucide="zap" class="w-3.5 h-3.5 text-purple-500"></i>
                <span>4. SVM (RBF)</span>
            </button>

            {{-- Dynamic Custom Models Tabs --}}
            <template x-for="(cell, cIdx) in customCells" :key="cell.key">
                <button type="button" @click="setActivePane(cell.key)"
                        :class="activePane === cell.key ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer border border-indigo-200/60 bg-indigo-50/40">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span x-text="(cIdx + 5) + '. ' + cell.shortTitle"></span>
                    <span @click.stop="removeCustomModel(cell.key)"
                          class="hover:text-rose-500 hover:bg-rose-50 rounded-full p-0.5 ml-0.5 transition cursor-pointer"
                          title="Remove this algorithm model">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </span>
                </button>
            </template>

            <div class="h-4 w-px bg-gray-200 mx-1"></div>

            {{-- Cell 6: Leaderboard --}}
            <button type="button" @click="setActivePane('cell_6')"
                    :class="activePane === 'cell_6' ? 'nav-tab-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap cursor-pointer">
                <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 text-rose-500"></i>
                <span>Leaderboard</span>
            </button>
        </div>

        {{-- Add Algorithm Model Action Button --}}
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" @click="openAddModelModal = true"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-indigo-200"></i>
                <span>+ Add Algorithm Model</span>
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         FOCUSED INDIVIDUAL PANE BANNER (WHEN NOT IN "ALL CELLS" MODE)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="activePane !== 'all'" class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-xl p-3.5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300">
                <i data-lucide="layers" class="w-4 h-4"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono uppercase tracking-wider text-indigo-300 font-bold">Individual Model Workspace</span>
                    <span class="bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 text-[11px] font-mono font-semibold px-2 py-0.5 rounded"
                          x-text="getActivePaneTitle()"></span>
                </div>
                <p class="text-xs text-slate-300 mt-0.5" x-text="getActivePaneDescription()"></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Navigation Arrows --}}
            <button type="button" @click="goToAdjacentPane(-1)" :disabled="cellKeys.indexOf(activePane) === 0"
                    class="bg-white/10 hover:bg-white/20 disabled:opacity-40 text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition cursor-pointer">
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                <span>Prev Cell</span>
            </button>
            <button type="button" @click="goToAdjacentPane(1)" :disabled="cellKeys.indexOf(activePane) === cellKeys.length - 1"
                    class="bg-white/10 hover:bg-white/20 disabled:opacity-40 text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition cursor-pointer">
                <span>Next Cell</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </button>
            <button type="button" @click="setActivePane('all')"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer shadow-2xs">
                <i data-lucide="maximize-2" class="w-3 h-3 text-indigo-200"></i>
                <span>All Cells View</span>
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         DEFAULT NOTEBOOK CELLS (1 TO 6)
    ══════════════════════════════════════════════════════════════════════ --}}
    @foreach($defaultCells as $cellKey => $cellMeta)
    <div class="colab-cell" id="card_{{ $cellKey }}" x-show="activePane === 'all' || activePane === '{{ $cellKey }}'">
        {{-- Cell Header Bar --}}
        <div class="px-4 py-3 bg-gray-50/90 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                {{-- Play / Run Button --}}
                <button type="button" @click="runSingleCell('{{ $cellKey }}')" :disabled="cellStates['{{ $cellKey }}']?.loading"
                        class="colab-play-btn bg-white hover:bg-indigo-50 text-indigo-600 border border-gray-200 shadow-2xs cursor-pointer"
                        title="Run this cell (Shift + Enter)">
                    <template x-if="!cellStates['{{ $cellKey }}']?.loading">
                        <i data-lucide="play" class="w-4 h-4 ml-0.5 text-indigo-600"></i>
                    </template>
                    <template x-if="cellStates['{{ $cellKey }}']?.loading">
                        <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                </button>

                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-gray-900 text-sm font-mono">{{ $cellMeta['title'] }}</h4>
                        @if(isset($cellMeta['badge']))
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded border {{ $cellMeta['badge_color'] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                            {{ $cellMeta['badge'] }}
                        </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $cellMeta['description'] }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-mono">
                {{-- Focus / Isolate Button when in "All Cells" view --}}
                <button type="button" @click="setActivePane('{{ $cellKey }}')" x-show="activePane === 'all'"
                        class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 border border-gray-200 px-2 py-1 rounded-md text-xs font-sans font-semibold flex items-center gap-1 transition cursor-pointer"
                        title="Focus on this individual model pane">
                    <i data-lucide="focus" class="w-3.5 h-3.5"></i>
                    <span>Focus Pane</span>
                </button>

                <template x-if="cellStates['{{ $cellKey }}']?.executionTime">
                    <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-md text-xs font-bold font-mono"
                          x-text="cellStates['{{ $cellKey }}']?.executionTime + 'ms'"></span>
                </template>
                <template x-if="cellStates['{{ $cellKey }}']?.status === 'error'">
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
                <div class="flex items-center gap-2">
                    <template x-if="cellStates['{{ $cellKey }}']?.executionTime">
                        <span class="text-emerald-400 font-bold" x-text="'Completed in ' + cellStates['{{ $cellKey }}']?.executionTime + ' ms'"></span>
                    </template>
                    <template x-if="!cellStates['{{ $cellKey }}']?.executionTime && !cellStates['{{ $cellKey }}']?.loading">
                        <span class="text-gray-500">Ready to run (Click ▶ or press Shift+Enter)</span>
                    </template>
                    <button type="button" @click="clearCellConsole('{{ $cellKey }}')"
                            x-show="cellStates['{{ $cellKey }}']?.stdout || cellStates['{{ $cellKey }}']?.stderr"
                            class="text-gray-400 hover:text-gray-200 px-1.5 py-0.5 rounded text-[11px] font-sans hover:bg-white/10 transition cursor-pointer">
                        Clear Output
                    </button>
                </div>
            </div>

            {{-- Live Running State --}}
            <div x-show="cellStates['{{ $cellKey }}']?.loading" class="flex items-center gap-2 text-amber-400 font-bold py-2 animate-pulse">
                <svg class="animate-spin w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>[Executing in Python 3.13 kernel...]</span>
            </div>

            {{-- Idle Prompt (Before run) --}}
            <div x-show="!cellStates['{{ $cellKey }}']?.loading && !cellStates['{{ $cellKey }}']?.stdout && !cellStates['{{ $cellKey }}']?.stderr"
                 class="text-gray-500 italic py-1 text-xs">
                // Console idle. Click the play button (▶) or press Shift+Enter to run this cell.
            </div>

            {{-- Output Stdout --}}
            <template x-if="cellStates['{{ $cellKey }}']?.stdout">
                <div class="text-slate-100 py-1 leading-relaxed" x-text="cellStates['{{ $cellKey }}']?.stdout"></div>
            </template>

            {{-- Output Stderr / Error Tracebacks --}}
            <template x-if="cellStates['{{ $cellKey }}']?.stderr">
                <div class="text-rose-400 bg-rose-950/40 border border-rose-800/40 p-3 rounded-lg font-bold mt-2 whitespace-pre leading-relaxed" x-text="cellStates['{{ $cellKey }}']?.stderr"></div>
            </template>
        </div>
    </div>
    @endforeach

    {{-- ══════════════════════════════════════════════════════════════════════
         DYNAMIC CUSTOM ALGORITHM MODEL CELLS
    ══════════════════════════════════════════════════════════════════════ --}}
    <template x-for="(cell, cIdx) in customCells" :key="cell.key">
        <div class="colab-cell" :id="'card_' + cell.key" x-show="activePane === 'all' || activePane === cell.key">
            {{-- Cell Header Bar --}}
            <div class="px-4 py-3 bg-indigo-50/50 border-b border-indigo-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    {{-- Play / Run Button --}}
                    <button type="button" @click="runSingleCell(cell.key)" :disabled="cellStates[cell.key]?.loading"
                            class="colab-play-btn bg-white hover:bg-indigo-50 text-indigo-600 border border-indigo-200 shadow-2xs cursor-pointer"
                            title="Run this custom algorithm cell (Shift + Enter)">
                        <template x-if="!cellStates[cell.key]?.loading">
                            <i data-lucide="play" class="w-4 h-4 ml-0.5 text-indigo-600"></i>
                        </template>
                        <template x-if="cellStates[cell.key]?.loading">
                            <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                    </button>

                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-gray-900 text-sm font-mono" x-text="cell.title"></h4>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded border"
                                  :class="cell.badgeColor || 'bg-indigo-50 text-indigo-700 border-indigo-200'"
                                  x-text="cell.badge"></span>
                            <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-1.5 py-0.2 rounded uppercase">Custom Model</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="cell.description"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs font-mono">
                    {{-- Focus / Isolate Button when in "All Cells" view --}}
                    <button type="button" @click="setActivePane(cell.key)" x-show="activePane === 'all'"
                            class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 border border-gray-200 px-2 py-1 rounded-md text-xs font-sans font-semibold flex items-center gap-1 transition cursor-pointer"
                            title="Focus on this individual model pane">
                        <i data-lucide="focus" class="w-3.5 h-3.5"></i>
                        <span>Focus Pane</span>
                    </button>

                    {{-- Delete / Remove Custom Model Button --}}
                    <button type="button" @click="removeCustomModel(cell.key)"
                            class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-rose-200 px-2 py-1 rounded-md text-xs font-sans font-semibold flex items-center gap-1 transition cursor-pointer"
                            title="Remove this algorithm model cell">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                        <span>Remove Model</span>
                    </button>

                    <template x-if="cellStates[cell.key]?.executionTime">
                        <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-md text-xs font-bold font-mono"
                              x-text="cellStates[cell.key]?.executionTime + 'ms'"></span>
                    </template>
                    <template x-if="cellStates[cell.key]?.status === 'error'">
                        <span class="text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-md text-xs font-bold">Execution Error</span>
                    </template>
                </div>
            </div>

            {{-- Code Editor Area --}}
            <div class="p-3 bg-white">
                <textarea :id="'editor_' + cell.key"
                          rows="8"
                          class="w-full bg-[#1e1e2e] text-[#f8f8f2] font-mono text-sm p-4 rounded-xl border border-gray-700 leading-relaxed outline-none focus:ring-2 focus:ring-indigo-500 shadow-inner resize-y"
                          x-text="cell.code"></textarea>
            </div>

            {{-- Running Console Output Area --}}
            <div class="colab-output p-4 text-sm font-mono overflow-x-auto whitespace-pre leading-relaxed border-t border-[#21262d]">
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-[#21262d] text-xs font-mono text-gray-400">
                    <span class="flex items-center gap-1.5 text-indigo-400 font-semibold">
                        <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                        <span>Running Console Output</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <template x-if="cellStates[cell.key]?.executionTime">
                            <span class="text-emerald-400 font-bold" x-text="'Completed in ' + cellStates[cell.key]?.executionTime + ' ms'"></span>
                        </template>
                        <template x-if="!cellStates[cell.key]?.executionTime && !cellStates[cell.key]?.loading">
                            <span class="text-gray-500">Ready to run (Click ▶ or press Shift+Enter)</span>
                        </template>
                        <button type="button" @click="clearCellConsole(cell.key)"
                                x-show="cellStates[cell.key]?.stdout || cellStates[cell.key]?.stderr"
                                class="text-gray-400 hover:text-gray-200 px-1.5 py-0.5 rounded text-[11px] font-sans hover:bg-white/10 transition cursor-pointer">
                            Clear Output
                        </button>
                    </div>
                </div>

                {{-- Live Running State --}}
                <div x-show="cellStates[cell.key]?.loading" class="flex items-center gap-2 text-amber-400 font-bold py-2 animate-pulse">
                    <svg class="animate-spin w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>[Executing in Python 3.13 kernel...]</span>
                </div>

                {{-- Idle Prompt --}}
                <div x-show="!cellStates[cell.key]?.loading && !cellStates[cell.key]?.stdout && !cellStates[cell.key]?.stderr"
                     class="text-gray-500 italic py-1 text-xs">
                    // Console idle. Click the play button (▶) or press Shift+Enter to run this cell.
                </div>

                {{-- Output Stdout --}}
                <template x-if="cellStates[cell.key]?.stdout">
                    <div class="text-slate-100 py-1 leading-relaxed" x-text="cellStates[cell.key]?.stdout"></div>
                </template>

                {{-- Output Stderr --}}
                <template x-if="cellStates[cell.key]?.stderr">
                    <div class="text-rose-400 bg-rose-950/40 border border-rose-800/40 p-3 rounded-lg font-bold mt-2 whitespace-pre leading-relaxed" x-text="cellStates[cell.key]?.stderr"></div>
                </template>
            </div>
        </div>
    </template>

    {{-- ══════════════════════════════════════════════════════════════════════
         ADD ALGORITHM MODEL MODAL
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="openAddModelModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="openAddModelModal = false"
             class="bg-white rounded-2xl max-w-3xl w-full border border-gray-200 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            {{-- Modal Header --}}
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-indigo-400"></i>
                        Add New Algorithm Model
                    </h3>
                    <p class="text-xs text-indigo-200 mt-0.5">
                        Select a pre-configured machine learning algorithm to add as a dedicated pane in your notebook pipeline.
                    </p>
                </div>
                <button type="button" @click="openAddModelModal = false" class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto space-y-5">
                {{-- Preset Selection Cards --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Select Algorithm Preset
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="(preset, pKey) in presets" :key="pKey">
                            <div @click="selectedPreset = pKey; newModelName = preset.name; newModelShort = preset.shortName;"
                                 :class="selectedPreset === pKey ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 hover:border-gray-300 bg-white'"
                                 class="p-3.5 rounded-xl border transition cursor-pointer flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="font-bold text-sm text-gray-900" x-text="preset.name"></span>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded border"
                                              :class="preset.badgeColor"
                                              x-text="preset.badge"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 leading-relaxed" x-text="preset.description"></p>
                                </div>
                                <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                                    <span class="font-mono text-[11px] text-gray-400">scikit-learn 1.6</span>
                                    <span x-show="selectedPreset === pKey" class="text-indigo-600 font-bold flex items-center gap-1 text-[11px]">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Selected
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Model Configuration Inputs --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Model Display Name</label>
                        <input type="text" x-model="newModelName"
                               class="w-full text-sm font-semibold bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tab Short Title</label>
                        <input type="text" x-model="newModelShort"
                               class="w-full text-sm font-semibold bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <span class="text-xs text-gray-500">
                    Inserted before Cell 6 (Leaderboard Benchmark)
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openAddModelModal = false"
                            class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-200 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" @click="addNewModel()"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-lg text-sm flex items-center gap-1.5 shadow-2xs transition cursor-pointer">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Add Model to Notebook</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

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
        activePane: 'all', // 'all', 'cell_1', 'cell_2', ..., or custom cell key
        openAddModelModal: false,
        selectedPreset: 'hist_gbm',
        newModelName: 'Histogram Gradient Boosting (GBM)',
        newModelShort: 'Gradient Boosting',
        editors: {},
        cellKeys: ['cell_1', 'cell_2', 'cell_3', 'cell_4', 'cell_5', 'cell_6'],
        customCells: [],
        defaultTemplates: @json($defaultCells),
        cellStates: {
            cell_1: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_2: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_3: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_4: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_5: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
            cell_6: { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' },
        },

        // Ready-to-run scikit-learn algorithm presets
        presets: {
            hist_gbm: {
                name: 'Histogram Gradient Boosting (GBM)',
                shortName: 'Gradient Boosting',
                badge: 'Ensemble Boosting',
                badgeColor: 'bg-teal-50 text-teal-700 border-teal-200',
                description: 'Fast histogram-based gradient boosted decision trees with early stopping and monotonic constraints.',
                code: `# [Cell Custom] Model: Histogram Gradient Boosting (GBM)
from sklearn.ensemble import HistGradientBoostingClassifier

# Hyperparameters: learning_rate, max_iter, max_leaf_nodes, min_samples_leaf
gbm_model = HistGradientBoostingClassifier(
    learning_rate=0.10,
    max_iter=100,
    max_leaf_nodes=31,
    min_samples_leaf=20,
    random_state=42
)

gbm_model.fit(X_train_pp, y_train)
gbm_preds = gbm_model.predict(X_test_pp)
gbm_confs = gbm_model.predict_proba(X_test_pp).max(axis=1) * 100
gbm_acc = (gbm_preds == y_test).mean() * 100

# Register in EXTRA_MODELS for multi-model evaluation in Cell 6
if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['Gradient Boosting (GBM)'] = (gbm_preds, gbm_confs)

print(f"✓ Histogram Gradient Boosting Fitted ({gbm_model.n_iter_} Iterations)")
print(f"  • Test Accuracy: {gbm_acc:.2f}%")
print(f"  • Mean Prediction Confidence: {gbm_confs.mean():.2f}%")`
            },
            knn: {
                name: 'k-Nearest Neighbors (KNN)',
                shortName: 'KNN',
                badge: 'Instance-Based Metric',
                badgeColor: 'bg-cyan-50 text-cyan-700 border-cyan-200',
                description: 'Distance-weighted instance learning based on feature proximity to the nearest training vehicles.',
                code: `# [Cell Custom] Model: k-Nearest Neighbors (KNN)
from sklearn.neighbors import KNeighborsClassifier

# Hyperparameters: n_neighbors (k), weights ('uniform' or 'distance'), metric
knn_model = KNeighborsClassifier(
    n_neighbors=7,
    weights='distance',
    metric='minkowski',
    p=2
)

knn_model.fit(X_train_pp, y_train)
knn_preds = knn_model.predict(X_test_pp)
knn_confs = knn_model.predict_proba(X_test_pp).max(axis=1) * 100
knn_acc = (knn_preds == y_test).mean() * 100

if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['k-Nearest Neighbors (KNN)'] = (knn_preds, knn_confs)

print(f"✓ k-Nearest Neighbors Fitted (k={knn_model.n_neighbors}, distance weighting)")
print(f"  • Test Accuracy: {knn_acc:.2f}%")
print(f"  • Mean Neighbor Confidence: {knn_confs.mean():.2f}%")`
            },
            logistic_regression: {
                name: 'Multinomial Logistic Regression',
                shortName: 'Logistic Reg.',
                badge: 'Linear Softmax',
                badgeColor: 'bg-blue-50 text-blue-700 border-blue-200',
                description: 'Linear probabilistic classification baseline using multinomial softmax cross-entropy loss with L2 regularization.',
                code: `# [Cell Custom] Model: Multinomial Logistic Regression
from sklearn.linear_model import LogisticRegression

# Hyperparameters: C (inverse regularization strength), solver, max_iter
lr_model = LogisticRegression(
    C=1.0,
    solver='lbfgs',
    max_iter=500,
    random_state=42
)

lr_model.fit(X_train_pp, y_train)
lr_preds = lr_model.predict(X_test_pp)
lr_confs = lr_model.predict_proba(X_test_pp).max(axis=1) * 100
lr_acc = (lr_preds == y_test).mean() * 100

if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['Logistic Regression'] = (lr_preds, lr_confs)

print(f"✓ Multinomial Logistic Regression Fitted (Converged in {lr_model.n_iter_[0]} iter)")
print(f"  • Test Accuracy: {lr_acc:.2f}%")
print(f"  • Mean Softmax Confidence: {lr_confs.mean():.2f}%")`
            },
            adaboost: {
                name: 'AdaBoost Classifier',
                shortName: 'AdaBoost',
                badge: 'Adaptive Boosting',
                badgeColor: 'bg-orange-50 text-orange-700 border-orange-200',
                description: 'Sequential boosting ensemble combining weak decision stumps with adaptive sample error weights.',
                code: `# [Cell Custom] Model: AdaBoost Classifier
from sklearn.ensemble import AdaBoostClassifier

# Hyperparameters: n_estimators, learning_rate
ada_model = AdaBoostClassifier(
    n_estimators=50,
    learning_rate=0.8,
    random_state=42
)

ada_model.fit(X_train_pp, y_train)
ada_preds = ada_model.predict(X_test_pp)
ada_confs = ada_model.predict_proba(X_test_pp).max(axis=1) * 100
ada_acc = (ada_preds == y_test).mean() * 100

if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['AdaBoost Ensemble'] = (ada_preds, ada_confs)

print(f"✓ AdaBoost Ensemble Fitted (50 Estimator Stages)")
print(f"  • Test Accuracy: {ada_acc:.2f}%")
print(f"  • Mean Confidence: {ada_confs.mean():.2f}%")`
            },
            mlp_neural_net: {
                name: 'Multi-Layer Perceptron (Neural Net)',
                shortName: 'MLP Neural Net',
                badge: 'Deep Learning',
                badgeColor: 'bg-violet-50 text-violet-700 border-violet-200',
                description: 'Feedforward deep artificial neural network with non-linear hidden layers and backpropagation.',
                code: `# [Cell Custom] Model: Multi-Layer Perceptron (Neural Network)
from sklearn.neural_network import MLPClassifier

# Hyperparameters: hidden_layer_sizes, activation, alpha (L2 penalty), max_iter
mlp_model = MLPClassifier(
    hidden_layer_sizes=(64, 32),
    activation='relu',
    solver='adam',
    alpha=0.0001,
    max_iter=300,
    random_state=42
)

mlp_model.fit(X_train_pp, y_train)
mlp_preds = mlp_model.predict(X_test_pp)
mlp_confs = mlp_model.predict_proba(X_test_pp).max(axis=1) * 100
mlp_acc = (mlp_preds == y_test).mean() * 100

if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['Neural Network (MLP)'] = (mlp_preds, mlp_confs)

print(f"✓ MLP Neural Network Fitted (Loss: {mlp_model.loss_:.4f}, Iterations: {mlp_model.n_iter_})")
print(f"  • Test Accuracy: {mlp_acc:.2f}%")
print(f"  • Mean Activation Confidence: {mlp_confs.mean():.2f}%")`
            },
            extra_trees: {
                name: 'Extra Trees Classifier',
                shortName: 'Extra Trees',
                badge: 'Randomized Trees',
                badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                description: 'Extremely randomized trees ensemble that draws random thresholds for each candidate feature to reduce variance.',
                code: `# [Cell Custom] Model: Extra Trees Classifier (Extremely Randomized Trees)
from sklearn.ensemble import ExtraTreesClassifier

# Hyperparameters: n_estimators, max_depth, max_features
et_model = ExtraTreesClassifier(
    n_estimators=100,
    max_depth=8,
    max_features='sqrt',
    random_state=42,
    n_jobs=-1
)

et_model.fit(X_train_pp, y_train)
et_preds = et_model.predict(X_test_pp)
et_confs = et_model.predict_proba(X_test_pp).max(axis=1) * 100
et_acc = (et_preds == y_test).mean() * 100

if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['Extra Trees Ensemble'] = (et_preds, et_confs)

print(f"✓ Extra Trees Ensemble Fitted (100 Trees)")
print(f"  • Test Accuracy: {et_acc:.2f}%")
print(f"  • Mean Prediction Confidence: {et_confs.mean():.2f}%")`
            },
            naive_bayes: {
                name: 'Gaussian Naive Bayes',
                shortName: 'Naive Bayes',
                badge: 'Bayesian / Probabilistic',
                badgeColor: 'bg-amber-50 text-amber-700 border-amber-200',
                description: 'Generative Bayes classifier calculating conditional class posteriors with Gaussian continuous assumption.',
                code: `# [Cell Custom] Model: Gaussian Naive Bayes
from sklearn.naive_bayes import GaussianNB

gnb_model = GaussianNB()
gnb_model.fit(X_train_pp, y_train)
gnb_preds = gnb_model.predict(X_test_pp)
gnb_confs = gnb_model.predict_proba(X_test_pp).max(axis=1) * 100
gnb_acc = (gnb_preds == y_test).mean() * 100

if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['Gaussian Naive Bayes'] = (gnb_preds, gnb_confs)

print(f"✓ Gaussian Naive Bayes Fitted")
print(f"  • Test Accuracy: {gnb_acc:.2f}%")
print(f"  • Mean Confidence: {gnb_confs.mean():.2f}%")`
            },
            custom_blank: {
                name: 'Custom Scikit-Learn Estimator',
                shortName: 'Custom Estimator',
                badge: 'Custom Architecture',
                badgeColor: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                description: 'Blank algorithmic template with access to X_train_pp, X_test_pp, y_train, and y_test.',
                code: `# [Cell Custom] User-Defined Algorithm Model
# X_train_pp, X_test_pp, y_train, y_test are already loaded in memory
from sklearn.linear_model import RidgeClassifier

custom_model = RidgeClassifier(alpha=1.0)
custom_model.fit(X_train_pp, y_train)
custom_preds = custom_model.predict(X_test_pp)

# Estimate confidence scores
decision_vals = np.abs(custom_model.decision_function(X_test_pp))
custom_confs = np.clip(50 + 50 * (decision_vals.max(axis=1) / (decision_vals.max() + 1e-6)), 50, 100)
custom_acc = (custom_preds == y_test).mean() * 100

# Register in EXTRA_MODELS for Cell 6 evaluation
if 'EXTRA_MODELS' not in globals():
    EXTRA_MODELS = {}
EXTRA_MODELS['Custom Ridge Model'] = (custom_preds, custom_confs)

print(f"✓ Custom Classifier Fitted")
print(f"  • Test Accuracy: {custom_acc:.2f}%")
print(f"  • Mean Confidence: {custom_confs.mean():.2f}%")`
            }
        },

        init() {
            const setupCM = () => {
                if (typeof CodeMirror === 'undefined') {
                    setTimeout(setupCM, 80);
                    return;
                }
                this.cellKeys.forEach(k => {
                    this.attachCodeMirror(k);
                });

                if (window.lucide) lucide.createIcons();
            };

            this.$nextTick(() => {
                setupCM();
            });
        },

        attachCodeMirror(k) {
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
        },

        setActivePane(paneKey) {
            this.activePane = paneKey;
            this.$nextTick(() => {
                if (paneKey === 'all') {
                    Object.values(this.editors).forEach(cm => cm.refresh());
                } else if (this.editors[paneKey]) {
                    this.editors[paneKey].refresh();
                    this.editors[paneKey].focus();
                }
                if (window.lucide) lucide.createIcons();
            });
        },

        goToAdjacentPane(direction) {
            const currentIdx = this.cellKeys.indexOf(this.activePane);
            if (currentIdx === -1) return;
            const nextIdx = currentIdx + direction;
            if (nextIdx >= 0 && nextIdx < this.cellKeys.length) {
                this.setActivePane(this.cellKeys[nextIdx]);
            }
        },

        getActivePaneTitle() {
            if (this.activePane === 'all') return 'Full Notebook';
            if (this.defaultTemplates[this.activePane]) {
                return this.defaultTemplates[this.activePane].title;
            }
            const custom = this.customCells.find(c => c.key === this.activePane);
            return custom ? custom.title : this.activePane;
        },

        getActivePaneDescription() {
            if (this.activePane === 'all') return 'Viewing all pipeline cells, baseline algorithms, and leaderboard in sequence.';
            if (this.defaultTemplates[this.activePane]) {
                return this.defaultTemplates[this.activePane].description;
            }
            const custom = this.customCells.find(c => c.key === this.activePane);
            return custom ? custom.description : 'Custom algorithm evaluation cell.';
        },

        addNewModel() {
            const preset = this.presets[this.selectedPreset] || this.presets['hist_gbm'];
            const customCount = this.customCells.length + 1;
            const newKey = 'custom_' + Date.now();
            const modelTitle = 'Cell ' + (this.cellKeys.length) + ': ' + (this.newModelName || preset.name);

            const newCell = {
                key: newKey,
                title: modelTitle,
                shortTitle: this.newModelShort || preset.shortName,
                badge: preset.badge,
                badgeColor: preset.badgeColor,
                description: preset.description,
                code: preset.code,
            };

            this.customCells.push(newCell);

            // Insert before Cell 6 (Leaderboard) in cellKeys array
            const cell6Idx = this.cellKeys.indexOf('cell_6');
            if (cell6Idx !== -1) {
                this.cellKeys.splice(cell6Idx, 0, newKey);
            } else {
                this.cellKeys.push(newKey);
            }

            this.cellStates[newKey] = {
                loading: false,
                stdout: '',
                stderr: '',
                executionTime: null,
                status: 'idle'
            };

            this.openAddModelModal = false;

            // Initialize CodeMirror and switch to this new model's pane
            this.$nextTick(() => {
                this.attachCodeMirror(newKey);
                this.setActivePane(newKey);
            });
        },

        removeCustomModel(cellKey) {
            if (!confirm('Remove this algorithm model cell from the notebook?')) return;

            if (this.editors[cellKey]) {
                this.editors[cellKey].toTextArea();
                delete this.editors[cellKey];
            }

            this.customCells = this.customCells.filter(c => c.key !== cellKey);
            this.cellKeys = this.cellKeys.filter(k => k !== cellKey);
            delete this.cellStates[cellKey];

            if (this.activePane === cellKey) {
                this.setActivePane('all');
            } else {
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        clearCellConsole(cellKey) {
            if (this.cellStates[cellKey]) {
                this.cellStates[cellKey].stdout = '';
                this.cellStates[cellKey].stderr = '';
                this.cellStates[cellKey].executionTime = null;
                this.cellStates[cellKey].status = 'idle';
            }
        },

        getAllCellCode() {
            const out = {};
            this.cellKeys.forEach(k => {
                if (this.editors[k]) {
                    out[k] = this.editors[k].getValue();
                } else {
                    const el = document.getElementById('editor_' + k);
                    const custom = this.customCells.find(c => c.key === k);
                    out[k] = (el && el.value) ? el.value : (this.defaultTemplates[k]?.code || custom?.code || '');
                }
            });
            return out;
        },

        runSingleCell(cellKey) {
            if (!this.cellStates[cellKey]) {
                this.cellStates[cellKey] = { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' };
            }
            this.cellStates[cellKey].loading = true;
            this.cellStates[cellKey].stderr = '';
            this.cellStates[cellKey].stdout = '';
            this.cellStates[cellKey].status = 'running';

            const payload = {
                dataset_id: this.selectedDatasetId,
                action: 'run_single_cell',
                cell_key: cellKey,
                cells: this.getAllCellCode(),
                cell_order: this.cellKeys,
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
                if (!this.cellStates[k]) {
                    this.cellStates[k] = { loading: false, stdout: '', stderr: '', executionTime: null, status: 'idle' };
                }
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
                cell_order: this.cellKeys,
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
                    if (this.cellStates[k]) this.cellStates[k].loading = false;
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
                cell_order: this.cellKeys,
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
            if (!confirm('Reset all 6 base code cells to original default code templates? Any unsaved edits will be lost.')) return;
            ['cell_1', 'cell_2', 'cell_3', 'cell_4', 'cell_5', 'cell_6'].forEach(k => {
                const code = this.defaultTemplates[k]?.code || '';
                if (this.editors[k]) {
                    this.editors[k].setValue(code);
                }
                const el = document.getElementById('editor_' + k);
                if (el) el.value = code;
                if (this.cellStates[k]) {
                    this.cellStates[k].stdout = '';
                    this.cellStates[k].stderr = '';
                    this.cellStates[k].executionTime = null;
                    this.cellStates[k].status = 'idle';
                }
            });
        }
    };
}
</script>
@endpush
@endsection
