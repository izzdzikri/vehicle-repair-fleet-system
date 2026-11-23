@extends('layouts.app')
@section('page-title', 'Corporate Bulk Fleet Check')

@section('content')

<div class="space-y-6">

    {{-- Top Header & Navigation --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                    Batch Intelligence
                </span>
                <span class="text-xs text-gray-500 font-medium">Multi-Model Fleet Projections</span>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mt-1">Corporate Bulk Fleet Health Check</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Execute algorithmic wear-and-tear projections across {{ $company->name }}'s registered fleet assets.
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ route('client.fleet-health-report') }}"
                class="text-xs font-bold px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition inline-flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> View Printable Fleet Report
            </a>
            <a href="{{ route('client.fleet-health-report.csv') }}"
                class="text-xs font-bold px-3.5 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 shadow-xs transition inline-flex items-center gap-1.5">
                <i data-lucide="download" class="w-3.5 h-3.5 text-gray-600"></i> Export CSV
            </a>
        </div>
    </div>

    {{-- PIC Role Authorization Status Callout --}}
    @if($isPrimaryPic)
    <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
            <i data-lucide="shield-check" class="w-4 h-4"></i>
        </div>
        <div class="text-xs">
            <span class="font-bold text-blue-900 block text-sm">Primary PIC Authorization Active</span>
            <p class="text-blue-700 mt-0.5 leading-relaxed">
                As a designated Primary PIC for <strong>{{ $company->name }}</strong>, you have full authority to select corporate vehicles, upload telemetry datasets, and trigger batch predictive maintenance scans.
            </p>
        </div>
    </div>
    @else
    <div class="p-4 rounded-xl bg-amber-50/80 border border-amber-200 flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
            <i data-lucide="eye" class="w-4 h-4"></i>
        </div>
        <div class="text-xs">
            <span class="font-bold text-amber-900 block text-sm">Corporate Viewer Mode (Read-Only)</span>
            <p class="text-amber-700 mt-0.5 leading-relaxed">
                As a <strong>{{ ucfirst(auth()->user()->pic_role ?? 'Viewer') }} PIC</strong>, you have read-only access to view fleet projections, active maintenance alerts, and inventory restock demand. Under multi-PIC corporate governance rules, only Primary PICs (maximum 2 per company) are authorized to execute batch algorithmic scans.
            </p>
        </div>
    </div>
    @endif

    {{-- Batch Fleet Check Form --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6" x-data="{ selectAll: true }">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-gray-900">Run Batch Predictive Scan</h3>
                <p class="text-xs text-gray-500 mt-0.5">Select fleet vehicles or upload an updated telemetry CSV file to generate consensus risk scores.</p>
            </div>
            <div class="text-xs font-semibold text-gray-500">
                5 ML Models: Rule Baseline, Decision Tree, Random Forest, SVM, Logistic Regression
            </div>
        </div>

        <form action="{{ route('client.fleet-check.run') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">

                {{-- Option 1: Multi-Select Vehicles --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <span>Target Fleet Vehicles ({{ $vehicles->count() }} Available)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600 hover:text-blue-700 cursor-pointer">
                            <input type="checkbox" x-model="selectAll"
                                @change="document.querySelectorAll('.vehicle-checkbox').forEach(el => el.checked = selectAll)"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>Select All Vehicles</span>
                        </label>
                    </div>

                    @if($vehicles->isEmpty())
                    <div class="p-6 text-center bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-500">
                        No fleet vehicles found under your corporate account. Please add vehicles first under My Fleet.
                    </div>
                    @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto p-1 border border-gray-100 rounded-xl bg-gray-50/30">
                        @foreach($vehicles as $vehicle)
                        <label class="flex items-start gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:bg-blue-50/20 cursor-pointer transition">
                            <input type="checkbox" name="vehicle_ids[]" value="{{ $vehicle->id }}" checked
                                class="vehicle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-1"
                                {{ ! $isPrimaryPic ? 'disabled' : '' }}>
                            <div class="text-xs min-w-0 flex-1">
                                <div class="font-mono font-bold text-gray-900">{{ $vehicle->plate_number }}</div>
                                <div class="text-gray-600 truncate">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year ?: '—' }})</div>
                                <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                    {{ number_format($vehicle->mileage) }} km &bull;
                                    {{ $vehicle->serviceHistory->count() }} past services
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Option 2: CSV Upload --}}
                <div class="pt-4 border-t border-gray-100">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Alternative: Upload Fleet Telemetry File (CSV)
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="file" name="fleet_csv" accept=".csv,.txt"
                            class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 border border-gray-200 rounded-lg bg-white"
                            {{ ! $isPrimaryPic ? 'disabled' : '' }}>
                        <span class="text-[11px] text-gray-400 shrink-0">
                            CSV header: plate_number (optional telemetry)
                        </span>
                    </div>
                </div>

                {{-- Action Submission --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-100 flex-wrap gap-4">
                    <div class="text-xs text-gray-500">
                        @if($isPrimaryPic)
                        Predictions use ensemble majority voting with highest-confidence tie-breaker.
                        @else
                        Only Primary PICs may submit batch calculations. Contact your company primary PIC to run scans.
                        @endif
                    </div>

                    <div>
                        @if($isPrimaryPic)
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                            <i data-lucide="cpu" class="w-4 h-4"></i> Run Batch Prediction Scan
                        </button>
                        @else
                        <button type="button" disabled
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-gray-200 text-gray-400 cursor-not-allowed">
                            <i data-lucide="lock" class="w-4 h-4"></i> Restricted to Primary PIC
                        </button>
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>

    {{-- Section: Current Predictive Projections Table --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-gray-900">Current Fleet Projections & Urgent Warnings</h3>
                <p class="text-xs text-gray-500 mt-0.5">Active predictive alerts generated for {{ $company->name }}'s vehicles.</p>
            </div>
            <span class="text-xs bg-gray-100 text-gray-700 font-bold px-2.5 py-1 rounded-lg">
                {{ $alerts->count() }} active warnings
            </span>
        </div>

        @if($alerts->isEmpty())
        <div class="p-8 text-center bg-gray-50 rounded-xl border border-gray-100">
            <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500 mx-auto mb-2"></i>
            <p class="text-sm font-bold text-gray-800">No active maintenance alerts for this fleet.</p>
            <p class="text-xs text-gray-500 mt-0.5">Click "Run Batch Prediction Scan" above to evaluate recent vehicle wear.</p>
        </div>
        @else
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase font-bold tracking-wider border-b border-gray-200">
                        <th class="py-2.5 px-3">Vehicle</th>
                        <th class="py-2.5 px-3">Projected Issue</th>
                        <th class="py-2.5 px-3 text-center">Urgency</th>
                        <th class="py-2.5 px-3">Confidence & Consensus</th>
                        <th class="py-2.5 px-3">Due Date</th>
                        <th class="py-2.5 px-3">Verified Status</th>
                        <th class="py-2.5 px-3">Recommendation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($alerts as $alert)
                    @php
                        $dueDate = $alert->effective_due_date;
                        $isOverdue = $dueDate && $dueDate->isPast();
                        $diff = $dueDate ? abs((int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false)) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="py-3 px-3">
                            <span class="font-mono font-bold text-gray-900">{{ $alert->vehicle->plate_number ?? 'N/A' }}</span>
                            <div class="text-[11px] text-gray-500">{{ $alert->vehicle->brand ?? '' }} {{ $alert->vehicle->model ?? '' }}</div>
                        </td>
                        <td class="py-3 px-3">
                            <span class="font-bold text-gray-800">{{ $alert->alert_type }}</span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($alert->urgency === 'high')
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 uppercase">
                                    High
                                </span>
                            @elseif($alert->urgency === 'medium')
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">
                                    Medium
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase">
                                    Low
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-gray-900">{{ $alert->plain_confidence }}</div>
                            <div class="text-[11px] text-gray-500">{{ $alert->plain_agreement_rate ?: 'Consensus' }}</div>
                        </td>
                        <td class="py-3 px-3">
                            @if($dueDate)
                                <div class="font-mono {{ $isOverdue ? 'text-rose-600 font-bold' : 'text-gray-900' }}">
                                    {{ $dueDate->format('d M Y') }}
                                </div>
                                <div class="text-[11px] {{ $isOverdue ? 'text-rose-600' : 'text-gray-500' }}">
                                    {{ $isOverdue ? "Overdue by {$diff}d" : "Due in {$diff}d" }}
                                </div>
                            @else
                                <span class="text-rose-600 font-medium">Immediate</span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-block text-[11px] font-medium px-2 py-0.5 rounded
                                {{ $alert->is_verified ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600' }}">
                                {{ $alert->plain_verification_status }}
                            </span>
                        </td>
                        <td class="py-3 px-3 max-w-xs text-gray-600 leading-snug">
                            {{ $alert->recommendation }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Section: Inventory Demand Advisory Preview --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-gray-900">Inventory Demand Advisory Preview</h3>
                <p class="text-xs text-gray-500 mt-0.5">Projected spare parts quantities required to service upcoming high & medium urgency alerts.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded bg-amber-50 text-amber-800 border border-amber-200">
                Advisory Only &bull; Never Auto-Purchased
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block">Identified Restock Shortfall</span>
                <p class="text-xl font-black text-rose-700 mt-1">{{ $inventoryAdvisory['total_shortfall_units'] }} units</p>
                <span class="text-[11px] text-gray-500">Across catalog items</span>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block">Estimated Parts Budget</span>
                <p class="text-xl font-black text-slate-900 mt-1">RM {{ number_format($inventoryAdvisory['total_estimated_budget'], 2) }}</p>
                <span class="text-[11px] text-gray-500">Based on catalog unit prices</span>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block">High/Medium Alerts Factored</span>
                <p class="text-xl font-black text-blue-700 mt-1">{{ $inventoryAdvisory['high_urgency_count'] + $inventoryAdvisory['medium_urgency_count'] }}</p>
                <span class="text-[11px] text-gray-500">Driving current parts demand</span>
            </div>
        </div>

        <p class="text-xs text-gray-500 italic">
            * {{ $inventoryAdvisory['disclaimer'] }} Full itemized breakdown and supplier details available on the <a href="{{ route('client.fleet-health-report') }}" class="text-blue-600 underline font-semibold">Fleet Health Report</a>.
        </p>
    </div>

</div>

@endsection
