@extends('layouts.app')
@section('page-title', 'Interactive Model Playground & What-If Simulator')

@section('content')
<div x-data="modelSimulator()" x-init="init()" class="space-y-6">

    {{-- ══════════════════════════════════════════════════════════════════════
         TOP HEADER BAR & NAVIGATION
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span class="bg-indigo-500/10 text-indigo-800 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded border border-indigo-500/20 flex items-center gap-1.5">
                        <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-indigo-600"></i>
                        Interactive Simulator
                    </span>
                    <span class="text-xs text-gray-500 font-mono">What-If Inference Studio</span>
                    @if($latestExperiment)
                    <span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-0.5 rounded border border-emerald-200 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Model Weights: {{ $latestExperiment->run_name }}</span>
                    </span>
                    @endif
                </div>
                <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">Interactive Multi-Model Prediction Playground</h1>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed max-w-3xl">
                    Pick a fleet vehicle, load benchmark test cases, or tweak telemetry sliders in real-time. Execute simultaneous live inference across the 4 predictive algorithms and evaluate multi-model consensus.
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap shrink-0">
                <a href="{{ route('admin.researcher-panel') }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-xs font-bold flex items-center gap-1.5 transition">
                    <i data-lucide="microscope" class="w-3.5 h-3.5 text-gray-600"></i>
                    <span>Researcher Panel</span>
                </a>
                <a href="{{ route('admin.researcher-colab') }}"
                   class="bg-slate-900 hover:bg-slate-800 text-white px-3 py-2 rounded-lg text-xs font-bold flex items-center gap-1.5 transition shadow-2xs">
                    <i data-lucide="terminal" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>ML Colab Lab</span>
                </a>
                <a href="{{ route('admin.predictive-analytics.index') }}"
                   class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-2 rounded-lg text-xs font-bold flex items-center gap-1.5 transition">
                    <i data-lucide="cpu" class="w-3.5 h-3.5 text-indigo-600"></i>
                    <span>ML Training</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         DATA PICKER & SCENARIO SELECTION DECK
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        {{-- Picker Tab Controls --}}
        <div class="bg-gray-50/80 border-b border-gray-200 px-5 py-3 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="database" class="w-4 h-4 text-indigo-600"></i>
                    <span>Step 1: Pick & Choose Data:</span>
                </span>
                <div class="flex items-center bg-white p-1 rounded-lg border border-gray-200 text-xs font-semibold">
                    <button type="button" @click="dataPickerTab = 'vehicle'"
                            :class="dataPickerTab === 'vehicle' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1 rounded-md transition cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="car" class="w-3.5 h-3.5"></i>
                        <span>Fleet Vehicle</span>
                    </button>
                    <button type="button" @click="dataPickerTab = 'preset'"
                            :class="dataPickerTab === 'preset' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1 rounded-md transition cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Benchmark Scenarios</span>
                    </button>
                    <button type="button" @click="dataPickerTab = 'custom'"
                            :class="dataPickerTab === 'custom' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1 rounded-md transition cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                        <span>Custom What-If</span>
                    </button>
                </div>
            </div>

            <div class="text-xs text-gray-500 font-mono">
                Click any sample or adjust sliders below to auto-load telemetry
            </div>
        </div>

        {{-- Tab Content 1: Fleet Vehicle Picker --}}
        <div x-show="dataPickerTab === 'vehicle'" class="p-5 border-b border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Select Fleet Vehicle ({{ count($vehicles) }} Available):</label>
                    <select x-model="selectedVehicleIndex" @change="loadSelectedVehicle()"
                            class="w-full text-sm font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:ring-2 focus:ring-indigo-300 outline-none cursor-pointer">
                        <option value="">-- Choose a Vehicle from Database --</option>
                        @foreach($vehicles as $idx => $v)
                        <option value="{{ $idx }}">
                            {{ $v['plate_number'] }} • {{ $v['brand_model'] }} ({{ number_format($v['days_since_service']) }}d / {{ number_format($v['km_since_service']) }}km)
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 bg-indigo-50/40 p-3.5 rounded-xl border border-indigo-100 flex items-center justify-between gap-4 text-xs">
                    <div>
                        <span class="font-bold text-indigo-900 block" x-text="activeRecordName || 'No Vehicle Selected'"></span>
                        <span class="text-indigo-700 mt-0.5 block" x-text="activeRecordDesc || 'Select a vehicle from the dropdown to load its live maintenance telemetry.'"></span>
                    </div>
                    <button type="button" @click="loadSelectedVehicle()" :disabled="selectedVehicleIndex === ''"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer shrink-0">
                        Load Telemetry
                    </button>
                </div>
            </div>
        </div>

        {{-- Tab Content 2: Benchmark Scenario Cards --}}
        <div x-show="dataPickerTab === 'preset'" class="p-5 border-b border-gray-100">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                @foreach($sampleScenarios as $pIdx => $sc)
                <div @click="loadScenario({{ $pIdx }})"
                     class="p-3.5 rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-indigo-50/30 transition cursor-pointer flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between gap-1 mb-1.5">
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border {{ $sc['badge_color'] }}">
                                {{ $sc['badge'] }}
                            </span>
                        </div>
                        <h4 class="font-bold text-xs text-gray-900 group-hover:text-indigo-600 transition">{{ $sc['name'] }}</h4>
                        <p class="text-[11px] text-gray-500 mt-1 leading-snug line-clamp-2">{{ $sc['description'] }}</p>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500 font-mono">
                        <span>{{ number_format($sc['days_since_service']) }}d • {{ number_format($sc['km_since_service']) }}km</span>
                        <span class="text-indigo-600 font-bold group-hover:translate-x-0.5 transition">Load ▶</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════════
             TELEMETRY INPUTS & SLIDERS ("CHOOSE & TWEAK DATA")
        ══════════════════════════════════════════════════════════════════════ --}}
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-800 uppercase tracking-wider">Step 2: Fine-Tune Telemetry Inputs:</span>
                    <span class="bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded font-mono font-bold" x-text="activeRecordName"></span>
                </div>
                <button type="button" @click="resetToDefaultTelemetry()"
                        class="text-xs text-gray-500 hover:text-indigo-600 font-semibold flex items-center gap-1 transition cursor-pointer">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset Values</span>
                </button>
            </div>

            {{-- 3-Column Grid of Sliders and Numeric Inputs --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- Input 1: Days Since Last Service --}}
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-gray-700 uppercase flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span>Days Since Service</span>
                        </label>
                        <span class="text-sm font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded"
                              x-text="form.days_since_service + ' days'"></span>
                    </div>
                    <input type="range" min="0" max="400" step="1" x-model.number="form.days_since_service"
                           class="w-full accent-indigo-600 cursor-pointer">
                    <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1 font-mono">
                        <span>0 d (Fresh)</span>
                        <span>180 d (6 mo limit)</span>
                        <span>400 d</span>
                    </div>
                </div>

                {{-- Input 2: Kilometers Since Service --}}
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-gray-700 uppercase flex items-center gap-1">
                            <i data-lucide="gauge" class="w-3.5 h-3.5 text-purple-500"></i>
                            <span>KM Since Service</span>
                        </label>
                        <span class="text-sm font-mono font-bold text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded"
                              x-text="Number(form.km_since_service).toLocaleString() + ' km'"></span>
                    </div>
                    <input type="range" min="0" max="25000" step="100" x-model.number="form.km_since_service"
                           class="w-full accent-purple-600 cursor-pointer">
                    <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1 font-mono">
                        <span>0 km</span>
                        <span>5,000 km (Nominal)</span>
                        <span>25,000 km</span>
                    </div>
                </div>

                {{-- Input 3: Terrain Severity Multiplier --}}
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-gray-700 uppercase flex items-center gap-1">
                            <i data-lucide="mountain" class="w-3.5 h-3.5 text-amber-500"></i>
                            <span>Terrain Severity Factor</span>
                        </label>
                        <span class="text-sm font-mono font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded"
                              x-text="Number(form.terrain_severity).toFixed(2) + 'x'"></span>
                    </div>
                    <input type="range" min="0.8" max="2.0" step="0.05" x-model.number="form.terrain_severity"
                           class="w-full accent-amber-600 cursor-pointer">
                    <div class="flex items-center justify-between text-[10px] text-gray-500 mt-1 font-mono">
                        <span :class="form.terrain_severity <= 1.0 ? 'font-bold text-indigo-600' : ''">1.0x Highway</span>
                        <span :class="form.terrain_severity > 1.0 && form.terrain_severity <= 1.3 ? 'font-bold text-indigo-600' : ''">1.3x Urban</span>
                        <span :class="form.terrain_severity > 1.3 && form.terrain_severity <= 1.6 ? 'font-bold text-indigo-600' : ''">1.6x Rural</span>
                        <span :class="form.terrain_severity > 1.6 ? 'font-bold text-rose-600' : ''">2.0x Off-Road</span>
                    </div>
                </div>

                {{-- Input 4: Daily Driving Rate (KM / Day) --}}
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-gray-700 uppercase flex items-center gap-1">
                            <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-500"></i>
                            <span>Daily Usage Rate</span>
                        </label>
                        <span class="text-sm font-mono font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded"
                              x-text="form.daily_km + ' km/day'"></span>
                    </div>
                    <input type="range" min="10" max="250" step="5" x-model.number="form.daily_km"
                           class="w-full accent-emerald-600 cursor-pointer">
                    <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1 font-mono">
                        <span>10 km/d (Low)</span>
                        <span>45 km/d (Avg)</span>
                        <span>250 km/d (Heavy)</span>
                    </div>
                </div>

                {{-- Input 5: Service Interval Settings --}}
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Service Interval Schedule:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[10px] text-gray-500 font-semibold uppercase block mb-1">Kilometers</span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="form.interval_km = 5000"
                                        :class="form.interval_km === 5000 ? 'bg-indigo-600 text-white font-bold' : 'bg-white border text-gray-700 hover:bg-gray-50'"
                                        class="px-2 py-1 rounded text-xs transition flex-1">5k</button>
                                <button type="button" @click="form.interval_km = 10000"
                                        :class="form.interval_km === 10000 ? 'bg-indigo-600 text-white font-bold' : 'bg-white border text-gray-700 hover:bg-gray-50'"
                                        class="px-2 py-1 rounded text-xs transition flex-1">10k</button>
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-500 font-semibold uppercase block mb-1">Months</span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="form.interval_months = 3"
                                        :class="form.interval_months === 3 ? 'bg-indigo-600 text-white font-bold' : 'bg-white border text-gray-700 hover:bg-gray-50'"
                                        class="px-2 py-1 rounded text-xs transition flex-1">3m</button>
                                <button type="button" @click="form.interval_months = 6"
                                        :class="form.interval_months === 6 ? 'bg-indigo-600 text-white font-bold' : 'bg-white border text-gray-700 hover:bg-gray-50'"
                                        class="px-2 py-1 rounded text-xs transition flex-1">6m</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Input 6: Vehicle Metadata (Fuel & Owner) --}}
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Classification Categoricals:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[10px] text-gray-500 font-semibold uppercase block mb-1">Fuel Type</span>
                            <select x-model="form.fuel_type"
                                    class="w-full text-xs font-semibold bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 outline-none cursor-pointer">
                                <option value="diesel">Diesel</option>
                                <option value="petrol">Petrol</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="electric">Electric</option>
                            </select>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-500 font-semibold uppercase block mb-1">Owner Type</span>
                            <select x-model="form.owner_type"
                                    class="w-full text-xs font-semibold bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 outline-none cursor-pointer">
                                <option value="commercial">Commercial Fleet</option>
                                <option value="corporate">Corporate / Govt</option>
                                <option value="individual">Individual</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
                 ACTION TOOLBAR (RUN BUTTON & ALGORITHM TOGGLES)
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs uppercase tracking-wider font-bold text-indigo-300">Target Models to Run:</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="bg-white/10 text-white text-[11px] font-mono font-semibold px-2 py-0.5 rounded border border-white/15">1. Rule-Based</span>
                            <span class="bg-white/10 text-white text-[11px] font-mono font-semibold px-2 py-0.5 rounded border border-white/15">2. Decision Tree</span>
                            <span class="bg-white/10 text-white text-[11px] font-mono font-semibold px-2 py-0.5 rounded border border-white/15">3. Random Forest</span>
                            <span class="bg-white/10 text-white text-[11px] font-mono font-semibold px-2 py-0.5 rounded border border-white/15">4. SVM (RBF)</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 mt-1">
                        Click below to dispatch live inference to the Python 3.13 kernel. Keyboard shortcut: <kbd class="px-1.5 py-0.5 bg-white/10 rounded font-mono text-[10px] text-indigo-200">Ctrl + Enter</kbd>
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <button type="button" @click="runInference()" :disabled="isLoading"
                            class="bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 disabled:opacity-50 text-white font-extrabold px-6 py-3 rounded-xl text-sm flex items-center gap-2.5 shadow-md transition cursor-pointer">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-300" x-show="!isLoading"></i>
                        <svg x-show="isLoading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Running Inference...' : '⚡ Run Multi-Model Live Inference'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         INFERENCE RESULTS DISPLAY (CONSENSUS + 4-MODEL CARDS)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="inferenceResult" class="space-y-6" style="display: none;">

        {{-- 1. Executive Consensus Banner --}}
        <div class="rounded-2xl border p-6 transition shadow-xs"
             :class="{
                 'bg-rose-50/80 border-rose-200 text-rose-950': inferenceResult?.consensus?.label === 'HIGH',
                 'bg-amber-50/80 border-amber-200 text-amber-950': inferenceResult?.consensus?.label === 'MEDIUM',
                 'bg-emerald-50/80 border-emerald-200 text-emerald-950': inferenceResult?.consensus?.label === 'LOW'
             }">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-extrabold text-2xl shadow-xs"
                         :class="{
                             'bg-rose-600 text-white': inferenceResult?.consensus?.label === 'HIGH',
                             'bg-amber-500 text-white': inferenceResult?.consensus?.label === 'MEDIUM',
                             'bg-emerald-600 text-white': inferenceResult?.consensus?.label === 'LOW'
                         }">
                        <span x-text="inferenceResult?.consensus?.label"></span>
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono uppercase tracking-wider font-bold">Multi-Model Consensus Verdict:</span>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border bg-white"
                                  x-text="inferenceResult?.consensus?.status_text"></span>
                            <span class="text-xs font-mono text-gray-500"
                                  x-text="'Kernel Runtime: ' + inferenceResult?.execution_time_ms + 'ms'"></span>
                        </div>
                        <h3 class="text-2xl font-extrabold tracking-tight mt-0.5"
                            x-text="inferenceResult?.consensus?.label === 'HIGH' ? '🚨 High Maintenance Urgency Detected' : (inferenceResult?.consensus?.label === 'MEDIUM' ? '⚠️ Medium Maintenance Urgency Warning' : '✅ Low Urgency (Healthy Fleet Vehicle)')"></h3>
                        <p class="text-xs font-medium mt-1 leading-relaxed"
                           x-text="inferenceResult?.explainability?.recommended_action"></p>
                    </div>
                </div>

                {{-- Fast Stats Pills --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs text-center min-w-[110px]">
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">Days Remaining</span>
                        <span class="text-lg font-mono font-extrabold"
                              :class="inferenceResult?.models?.rule_based?.diagnostics?.days_remaining <= 0 ? 'text-rose-600' : 'text-emerald-600'"
                              x-text="(inferenceResult?.models?.rule_based?.diagnostics?.days_remaining > 0 ? '+' : '') + inferenceResult?.models?.rule_based?.diagnostics?.days_remaining + ' d'"></span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs text-center min-w-[110px]">
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">Agreement</span>
                        <span class="text-lg font-mono font-extrabold text-indigo-600"
                              x-text="inferenceResult?.consensus?.agreement_rate + '%'"></span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs text-center min-w-[110px]">
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">Effective KM</span>
                        <span class="text-lg font-mono font-extrabold text-gray-800"
                              x-text="Number(inferenceResult?.models?.rule_based?.diagnostics?.effective_interval_km).toLocaleString() + ' km'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Side-by-Side 4-Model Comparative Cards --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="layers" class="w-4 h-4 text-indigo-600"></i>
                    <span>Side-by-Side Model Predictions (N = 4):</span>
                </h3>
                <span class="text-xs text-gray-500 font-mono">Real-time classification outputs</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Model 1 Card: Rule-Based --}}
                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
                                Model 1: Rule-Based
                            </span>
                            <span class="text-xs font-mono font-bold"
                                  :class="{
                                      'text-rose-600': inferenceResult?.models?.rule_based?.prediction === 'HIGH',
                                      'text-amber-600': inferenceResult?.models?.rule_based?.prediction === 'MEDIUM',
                                      'text-emerald-600': inferenceResult?.models?.rule_based?.prediction === 'LOW'
                                  }"
                                  x-text="inferenceResult?.models?.rule_based?.prediction"></span>
                        </div>
                        <h4 class="font-bold text-sm text-gray-900">Deterministic Hybrid</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Physical time/mileage formula with terrain adjustment.</p>

                        <div class="mt-3 space-y-1.5 text-xs font-mono bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Margin Confidence:</span>
                                <span class="font-bold text-gray-800" x-text="inferenceResult?.models?.rule_based?.confidence + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Days Remaining:</span>
                                <span class="font-bold" :class="inferenceResult?.models?.rule_based?.diagnostics?.days_remaining <= 0 ? 'text-rose-600' : 'text-emerald-600'"
                                      x-text="inferenceResult?.models?.rule_based?.diagnostics?.days_remaining + ' d'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Terrain Penalty:</span>
                                <span class="font-bold text-gray-800" x-text="'-' + inferenceResult?.explainability?.terrain_penalty_pct + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Model 2 Card: Decision Tree --}}
                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">
                                Model 2: Decision Tree
                            </span>
                            <span class="text-xs font-mono font-bold"
                                  :class="{
                                      'text-rose-600': inferenceResult?.models?.decision_tree?.prediction === 'HIGH',
                                      'text-amber-600': inferenceResult?.models?.decision_tree?.prediction === 'MEDIUM',
                                      'text-emerald-600': inferenceResult?.models?.decision_tree?.prediction === 'LOW'
                                  }"
                                  x-text="inferenceResult?.models?.decision_tree?.prediction"></span>
                        </div>
                        <h4 class="font-bold text-sm text-gray-900">CART Classifier</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Hierarchical Gini impurity splitting tree.</p>

                        <div class="mt-3 space-y-1.5 text-xs font-mono bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Leaf Confidence:</span>
                                <span class="font-bold text-gray-800" x-text="inferenceResult?.models?.decision_tree?.confidence + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">HIGH Prob:</span>
                                <span class="font-bold" x-text="inferenceResult?.models?.decision_tree?.probabilities?.HIGH + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">LOW Prob:</span>
                                <span class="font-bold" x-text="inferenceResult?.models?.decision_tree?.probabilities?.LOW + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Model 3 Card: Random Forest --}}
                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">
                                Model 3: Random Forest
                            </span>
                            <span class="text-xs font-mono font-bold"
                                  :class="{
                                      'text-rose-600': inferenceResult?.models?.random_forest?.prediction === 'HIGH',
                                      'text-amber-600': inferenceResult?.models?.random_forest?.prediction === 'MEDIUM',
                                      'text-emerald-600': inferenceResult?.models?.random_forest?.prediction === 'LOW'
                                  }"
                                  x-text="inferenceResult?.models?.random_forest?.prediction"></span>
                        </div>
                        <h4 class="font-bold text-sm text-gray-900">100 Trees Ensemble</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Bootstrap aggregating with majority vote weighting.</p>

                        <div class="mt-3 space-y-1.5 text-xs font-mono bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Ensemble Votes:</span>
                                <span class="font-bold text-gray-800" x-text="inferenceResult?.models?.random_forest?.confidence + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">HIGH Prob:</span>
                                <span class="font-bold text-rose-600" x-text="inferenceResult?.models?.random_forest?.probabilities?.HIGH + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">MED / LOW:</span>
                                <span class="font-bold text-gray-800" x-text="(inferenceResult?.models?.random_forest?.probabilities?.MEDIUM || 0) + '% / ' + (inferenceResult?.models?.random_forest?.probabilities?.LOW || 0) + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Model 4 Card: SVM (RBF) --}}
                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">
                                Model 4: SVM (RBF)
                            </span>
                            <span class="text-xs font-mono font-bold"
                                  :class="{
                                      'text-rose-600': inferenceResult?.models?.svm?.prediction === 'HIGH',
                                      'text-amber-600': inferenceResult?.models?.svm?.prediction === 'MEDIUM',
                                      'text-emerald-600': inferenceResult?.models?.svm?.prediction === 'LOW'
                                  }"
                                  x-text="inferenceResult?.models?.svm?.prediction"></span>
                        </div>
                        <h4 class="font-bold text-sm text-gray-900">Kernel Support Vector</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Platt calibrated probabilities in RBF kernel space.</p>

                        <div class="mt-3 space-y-1.5 text-xs font-mono bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Platt Confidence:</span>
                                <span class="font-bold text-gray-800" x-text="inferenceResult?.models?.svm?.confidence + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">HIGH Prob:</span>
                                <span class="font-bold text-purple-600" x-text="inferenceResult?.models?.svm?.probabilities?.HIGH + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Classification:</span>
                                <span class="font-bold text-gray-800">Support Vectors</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- 3. Explainable AI (XAI) Causal Analysis --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h4 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="help-circle" class="w-4 h-4 text-indigo-600"></i>
                    <span>Explainable AI (XAI) Causal Factors & Decision Reasoning</span>
                </h4>
                <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                    Transparent Telemetry Audit
                </span>
            </div>

            {{-- Progress Bars for Capacity Utilization --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-semibold text-gray-700">Mileage Interval Consumption:</span>
                        <span class="font-mono font-bold"
                              :class="inferenceResult?.explainability?.km_utilization_pct >= 100 ? 'text-rose-600' : 'text-emerald-600'"
                              x-text="inferenceResult?.explainability?.km_utilization_pct + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500"
                             :class="inferenceResult?.explainability?.km_utilization_pct >= 100 ? 'bg-rose-500' : 'bg-emerald-500'"
                             :style="'width: ' + Math.min(inferenceResult?.explainability?.km_utilization_pct || 0, 100) + '%'"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-semibold text-gray-700">Calendar Schedule Consumption:</span>
                        <span class="font-mono font-bold"
                              :class="inferenceResult?.explainability?.time_utilization_pct >= 100 ? 'text-rose-600' : 'text-emerald-600'"
                              x-text="inferenceResult?.explainability?.time_utilization_pct + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500"
                             :class="inferenceResult?.explainability?.time_utilization_pct >= 100 ? 'bg-rose-500' : 'bg-emerald-500'"
                             :style="'width: ' + Math.min(inferenceResult?.explainability?.time_utilization_pct || 0, 100) + '%'"></div>
                    </div>
                </div>
            </div>

            {{-- Bullet Points of Causal Factors --}}
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <span class="text-xs font-bold text-gray-700 uppercase block mb-2">Identified Decision Factors:</span>
                <ul class="space-y-1.5 text-xs text-gray-700">
                    <template x-for="(factor, fIdx) in inferenceResult?.explainability?.factors || []" :key="fIdx">
                        <li class="flex items-start gap-2">
                            <span class="text-indigo-600 font-bold">•</span>
                            <span x-text="factor"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SESSION LOG TABLE (RECENT INFERENCE SCENARIOS)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="sessionHistory.length > 0" class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-sm text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-indigo-600"></i>
                    <span>Recent Test Scenarios Log (<span x-text="sessionHistory.length"></span>)</span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Audit history of scenarios simulated during this session</p>
            </div>
            <button type="button" @click="sessionHistory = []"
                    class="text-xs text-gray-500 hover:text-rose-600 transition cursor-pointer font-semibold">
                Clear Log
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 border-b border-gray-200 font-bold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Scenario / Vehicle</th>
                        <th class="px-4 py-3">Telemetry Inputs</th>
                        <th class="px-4 py-3">1. Rule</th>
                        <th class="px-4 py-3">2. Tree</th>
                        <th class="px-4 py-3">3. Forest</th>
                        <th class="px-4 py-3">4. SVM</th>
                        <th class="px-4 py-3">Consensus</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono">
                    <template x-for="(item, idx) in sessionHistory" :key="idx">
                        <tr class="hover:bg-gray-50/60 transition">
                            <td class="px-4 py-2.5 text-gray-500" x-text="item.timestamp"></td>
                            <td class="px-4 py-2.5 font-bold font-sans text-gray-900" x-text="item.name"></td>
                            <td class="px-4 py-2.5 text-gray-600">
                                <span x-text="item.features.days_since_service + 'd • ' + item.features.km_since_service + 'km • ' + item.features.terrain_severity + 'x'"></span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-bold" :class="item.results.rule_based.prediction === 'HIGH' ? 'text-rose-600' : (item.results.rule_based.prediction === 'MEDIUM' ? 'text-amber-600' : 'text-emerald-600')"
                                      x-text="item.results.rule_based.prediction"></span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-bold" :class="item.results.decision_tree.prediction === 'HIGH' ? 'text-rose-600' : (item.results.decision_tree.prediction === 'MEDIUM' ? 'text-amber-600' : 'text-emerald-600')"
                                      x-text="item.results.decision_tree.prediction"></span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-bold" :class="item.results.random_forest.prediction === 'HIGH' ? 'text-rose-600' : (item.results.random_forest.prediction === 'MEDIUM' ? 'text-amber-600' : 'text-emerald-600')"
                                      x-text="item.results.random_forest.prediction"></span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-bold" :class="item.results.svm.prediction === 'HIGH' ? 'text-rose-600' : (item.results.svm.prediction === 'MEDIUM' ? 'text-amber-600' : 'text-emerald-600')"
                                      x-text="item.results.svm.prediction"></span>
                            </td>
                            <td class="px-4 py-2.5 font-sans font-bold">
                                <span class="px-2 py-0.5 rounded text-[11px]"
                                      :class="{
                                          'bg-rose-100 text-rose-800': item.consensus.label === 'HIGH',
                                          'bg-amber-100 text-amber-800': item.consensus.label === 'MEDIUM',
                                          'bg-emerald-100 text-emerald-800': item.consensus.label === 'LOW'
                                      }"
                                      x-text="item.consensus.label + ' (' + item.consensus.agreement_fraction + ')'"></span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-sans">
                                <button type="button" @click="restoreSessionItem(item)"
                                        class="text-indigo-600 hover:text-indigo-800 font-bold transition cursor-pointer">
                                    Re-load
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
function modelSimulator() {
    return {
        dataPickerTab: 'preset', // 'vehicle', 'preset', 'custom'
        selectedVehicleIndex: '',
        activeRecordName: '🚨 Critical Overdue 4x4 (Jungle / Timber Track)',
        activeRecordDesc: 'Preloaded benchmark scenario ready for live multi-model inference.',
        vehicles: @json($vehicles),
        scenarios: @json($sampleScenarios),
        isLoading: false,
        inferenceResult: null,
        sessionHistory: [],

        // Active Telemetry Vector
        form: {
            days_since_service: 210,
            km_since_service: 14200,
            interval_km: 5000,
            interval_months: 6,
            daily_km: 65,
            terrain_severity: 1.8,
            vehicle_age: 5,
            total_mileage: 142000,
            fuel_type: 'diesel',
            owner_type: 'commercial'
        },

        init() {
            // Auto-run inference on initial load so the page opens with immediate results!
            this.$nextTick(() => {
                this.runInference();
                if (window.lucide) lucide.createIcons();
            });

            // Keyboard shortcut Ctrl + Enter to run
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    this.runInference();
                }
            });
        },

        loadSelectedVehicle() {
            if (this.selectedVehicleIndex === '') return;
            const v = this.vehicles[this.selectedVehicleIndex];
            if (!v) return;

            this.form.days_since_service = v.days_since_service;
            this.form.km_since_service = v.km_since_service;
            this.form.daily_km = v.daily_km;
            this.form.interval_km = v.interval_km;
            this.form.interval_months = v.interval_months;
            this.form.terrain_severity = v.terrain_severity;
            this.form.vehicle_age = v.vehicle_age;
            this.form.total_mileage = v.total_mileage;
            this.form.fuel_type = v.fuel_type;
            this.form.owner_type = v.owner_type;

            this.activeRecordName = v.plate_number + ' • ' + v.brand_model;
            this.activeRecordDesc = 'Real fleet record from Teraju Setia database with live mileage logs.';
            this.runInference();
        },

        loadScenario(idx) {
            const sc = this.scenarios[idx];
            if (!sc) return;

            this.form.days_since_service = sc.days_since_service;
            this.form.km_since_service = sc.km_since_service;
            this.form.interval_km = sc.interval_km;
            this.form.interval_months = sc.interval_months;
            this.form.daily_km = sc.daily_km;
            this.form.terrain_severity = sc.terrain_severity;
            this.form.vehicle_age = sc.vehicle_age;
            this.form.total_mileage = sc.total_mileage;
            this.form.fuel_type = sc.fuel_type;
            this.form.owner_type = sc.owner_type;

            this.activeRecordName = sc.name;
            this.activeRecordDesc = sc.description;
            this.runInference();
        },

        resetToDefaultTelemetry() {
            this.loadScenario(0);
        },

        runInference() {
            this.isLoading = true;

            fetch('{{ route("admin.researcher-simulator.run") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.form)
            })
            .then(res => res.json())
            .then(data => {
                this.isLoading = false;
                if (data.status === 'success') {
                    this.inferenceResult = data;

                    // Add to session log
                    this.sessionHistory.unshift({
                        timestamp: new Date().toLocaleTimeString(),
                        name: this.activeRecordName,
                        features: JSON.parse(JSON.stringify(this.form)),
                        results: data.models,
                        consensus: data.consensus
                    });

                    // Keep up to 20 recent records
                    if (this.sessionHistory.length > 20) {
                        this.sessionHistory.pop();
                    }
                } else {
                    alert('Inference error: ' + (data.message || 'Unknown error'));
                }
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            })
            .catch(err => {
                this.isLoading = false;
                alert('Inference execution error: ' + err.message);
            });
        },

        restoreSessionItem(item) {
            this.form = JSON.parse(JSON.stringify(item.features));
            this.activeRecordName = item.name;
            this.runInference();
        }
    };
}
</script>
@endpush
@endsection
