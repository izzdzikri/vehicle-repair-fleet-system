@extends('layouts.app')
@section('page-title', 'Corporate Fleet Health Report')

@section('content')

{{-- Print-only CSS optimizations --}}
<style>
@media print {
    body { background: #fff !important; color: #000 !important; }
    aside, header, nav, .no-print, #chatbot-bubble-toggle, #chatbot-window { display: none !important; }
    main { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
    .print-sheet { border: none !important; box-shadow: none !important; padding: 0 !important; }
    .page-break { page-break-after: always; }
}
</style>

{{-- Action & Filter Bar (Hidden in Print) --}}
<div class="no-print flex items-center justify-between flex-wrap gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                AI Predictive Intelligence
            </span>
            <span class="text-xs text-gray-500 font-medium">Research-Grade Fleet Assessment</span>
        </div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight mt-1">Fleet Health & Predictive Maintenance Report</h2>
        <p class="text-sm text-gray-500 mt-0.5">Comprehensive mechanical health audit, risk-ranked vehicles, and workshop inventory demand advisory.</p>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
        {{-- Bulk Fleet Check Link --}}
        <a href="{{ route('client.fleet-check') }}"
            class="text-xs font-bold px-3.5 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 shadow-xs transition inline-flex items-center gap-1.5">
            <i data-lucide="cpu" class="w-3.5 h-3.5 text-blue-600"></i> Run Bulk Fleet Check
        </a>

        {{-- Export CSV --}}
        <a href="{{ route('client.fleet-health-report.csv') }}"
            class="text-xs font-bold px-3.5 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 shadow-xs transition inline-flex items-center gap-1.5">
            <i data-lucide="download" class="w-3.5 h-3.5 text-gray-600"></i> Download CSV
        </a>

        {{-- Print Statement Button --}}
        <button type="button" onclick="window.print()"
            class="text-xs font-bold px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition inline-flex items-center gap-1.5">
            <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print Official Report
        </button>
    </div>
</div>

{{-- Printable Report Sheet Container --}}
<div class="print-sheet bg-white rounded-2xl border border-gray-200/90 shadow-sm p-8 space-y-8">

    {{-- Header with Workshop Branding & Supervisor Endorsement --}}
    <div class="border-b-2 border-slate-900 pb-6">
        <div class="flex justify-between items-start flex-wrap gap-6">
            <div class="max-w-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-base shadow-sm">
                        TS
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">TERAJU SETIA ENTERPRISE</h1>
                        <p class="text-xs text-gray-500 font-semibold">Vehicle Repair, Predictive Diagnostics & Fleet Care Center</p>
                    </div>
                </div>

                <div class="text-xs text-gray-600 mt-3 leading-relaxed">
                    <p>{{ $workshop['address'] }}</p>
                    <p class="mt-0.5">
                        <span class="font-semibold text-gray-700">Hotline:</span> {{ $workshop['phone'] }} &bull;
                        <span class="font-semibold text-gray-700">Email:</span> {{ $workshop['email'] }}
                    </p>
                    <p class="mt-1 text-[11px] text-gray-500 font-mono">
                        Reg No: {{ $workshop['reg_no'] }} &bull; Academic Supervision: <span class="font-semibold text-gray-800">{{ $workshop['supervisor'] }}</span>
                    </p>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block bg-slate-900 text-white px-3.5 py-1 rounded-md text-xs font-black uppercase tracking-wider mb-2">
                    Official Fleet Health Audit
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Report Issued Date</p>
                <p class="text-base font-extrabold text-slate-900">{{ $workshop['report_date'] }}</p>
                <p class="text-[11px] text-gray-400 mt-1">Multi-Model Ensemble Engine v2.0</p>
            </div>
        </div>

        {{-- Client Details Card --}}
        <div class="mt-6 pt-5 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/70 p-4 rounded-xl border border-gray-100">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Report Prepared For</span>
                <h3 class="text-base font-extrabold text-gray-900 mt-0.5">{{ $company->name ?? 'Corporate Fleet Client' }}</h3>
                <p class="text-xs text-gray-600 mt-0.5">{{ $company->address ?? 'Corporate Headquarters' }}</p>
                @if($company && $company->registration_no)
                <p class="text-xs text-gray-500 mt-0.5 font-mono">Company Reg: {{ $company->registration_no }}</p>
                @endif
            </div>
            <div class="md:text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Fleet Representative</span>
                <p class="text-sm font-bold text-gray-900 mt-0.5">{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->pic_role ?? 'PIC') }})</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ auth()->user()->email }} &bull; {{ auth()->user()->contact_no ?? 'No phone' }}</p>
                <p class="text-xs text-gray-500 mt-1">Monitored Assets: <strong>{{ $totalVehicles }} registered vehicles</strong></p>
            </div>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div>
        <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900">1. Executive Fleet Health Summary</h3>
            <span class="text-xs text-gray-500">Real-time predictive consensus across fleet assets</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            {{-- Fleet Size --}}
            <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-200/80">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Fleet Size</span>
                <p class="text-2xl font-black text-gray-900 tracking-tight mt-1">{{ $totalVehicles }}</p>
                <span class="text-[11px] text-gray-500 block mt-0.5">Total assets under care</span>
            </div>

            {{-- High Urgency --}}
            <div class="bg-rose-50/70 rounded-xl p-4 border border-rose-200/80">
                <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">High Urgency</span>
                <p class="text-2xl font-black text-rose-700 tracking-tight mt-1">{{ $highUrgencyAlerts }}</p>
                <span class="text-[11px] text-rose-600 block mt-0.5">Immediate repair needed</span>
            </div>

            {{-- Medium Urgency --}}
            <div class="bg-amber-50/70 rounded-xl p-4 border border-amber-200/80">
                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Medium Urgency</span>
                <p class="text-2xl font-black text-amber-700 tracking-tight mt-1">{{ $mediumUrgencyAlerts }}</p>
                <span class="text-[11px] text-amber-600 block mt-0.5">Schedule within 14 days</span>
            </div>

            {{-- Low / Healthy --}}
            <div class="bg-emerald-50/70 rounded-xl p-4 border border-emerald-200/80">
                <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Healthy Vehicles</span>
                <p class="text-2xl font-black text-emerald-700 tracking-tight mt-1">{{ $healthyVehiclesCount }}</p>
                <span class="text-[11px] text-emerald-600 block mt-0.5">Zero urgent defects</span>
            </div>

            {{-- Fleet Health Score --}}
            <div class="bg-slate-900 text-white rounded-xl p-4">
                <span class="text-[10px] font-bold text-slate-300 uppercase tracking-wider block">Fleet Health Index</span>
                <p class="text-2xl font-black tracking-tight mt-1">{{ $fleetHealthScore }}%</p>
                <span class="text-[11px] text-slate-400 block mt-0.5">
                    {{ $fleetHealthScore >= 80 ? 'Optimal Performance' : ($fleetHealthScore >= 60 ? 'Moderate Attention' : 'Elevated Risk') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Section 2: Top Risk Fleet Vehicles Table --}}
    <div>
        <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900">2. Priority Risk Vehicles & Predictive Warnings</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">Ranked by urgency level and projected target maintenance due date.</p>
            </div>
            <span class="text-xs font-semibold text-gray-600">{{ $topRiskAlerts->count() }} active warnings</span>
        </div>

        @if($topRiskAlerts->isEmpty())
        <div class="p-8 text-center bg-emerald-50/50 rounded-xl border border-emerald-200">
            <i data-lucide="check-circle-2" class="w-10 h-10 text-emerald-600 mx-auto mb-2"></i>
            <h4 class="text-sm font-bold text-emerald-900">All Fleet Vehicles In Healthy Operating Condition</h4>
            <p class="text-xs text-emerald-700 mt-1 max-w-md mx-auto">
                No high or medium urgency predictive warnings are currently flagged. All vehicles are within standard wear-and-tear thresholds.
            </p>
        </div>
        @else
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase font-bold tracking-wider border-b border-gray-200">
                        <th class="py-2.5 px-3">Vehicle</th>
                        <th class="py-2.5 px-3">Identified Service</th>
                        <th class="py-2.5 px-3 text-center">Urgency</th>
                        <th class="py-2.5 px-3">Confidence & Consensus</th>
                        <th class="py-2.5 px-3">Due Timeline</th>
                        <th class="py-2.5 px-3">Verified Status</th>
                        <th class="py-2.5 px-3">Recommended Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($topRiskAlerts as $alert)
                    @php
                        $dueDate = $alert->effective_due_date;
                        $isOverdue = $dueDate && $dueDate->isPast();
                        $diff = $dueDate ? abs((int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false)) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="py-3 px-3">
                            <div class="font-mono font-bold text-gray-900">{{ $alert->vehicle->plate_number ?? 'N/A' }}</div>
                            <div class="text-[11px] text-gray-500">
                                {{ $alert->vehicle->brand ?? '' }} {{ $alert->vehicle->model ?? '' }} ({{ $alert->vehicle->year ?? '—' }})
                            </div>
                            <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                                {{ number_format($alert->vehicle->mileage ?? 0) }} km
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <span class="font-bold text-gray-800">{{ $alert->alert_type }}</span>
                            @if($alert->jobType)
                            <div class="text-[11px] text-gray-500">{{ $alert->jobType->name }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($alert->urgency === 'high')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 uppercase">
                                    High
                                </span>
                            @elseif($alert->urgency === 'medium')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">
                                    Medium
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase">
                                    Low
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-gray-900">{{ $alert->plain_confidence }}</div>
                            <div class="text-[11px] text-gray-500">
                                {{ $alert->plain_agreement_rate ?: 'Ensemble Consensus' }}
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            @if($dueDate)
                                <div class="font-mono font-medium {{ $isOverdue ? 'text-rose-600 font-bold' : 'text-gray-900' }}">
                                    {{ $dueDate->format('d M Y') }}
                                </div>
                                <div class="text-[11px] {{ $isOverdue ? 'text-rose-600 font-semibold' : 'text-gray-500' }}">
                                    {{ $isOverdue ? "Overdue by {$diff}d" : "Due in {$diff}d" }}
                                </div>
                            @else
                                <span class="text-rose-600 font-semibold">Immediate</span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-block text-[11px] font-medium px-2 py-0.5 rounded
                                {{ $alert->is_verified ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600' }}">
                                {{ $alert->plain_verification_status }}
                            </span>
                        </td>
                        <td class="py-3 px-3 max-w-xs text-gray-700 leading-snug">
                            {{ $alert->recommendation ?: 'Schedule preventive inspection and oil/filter check.' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Section 3: Recommended Actions in Plain Wording --}}
    <div class="bg-slate-50 rounded-xl p-5 border border-slate-200/80">
        <div class="flex items-center gap-2 mb-3">
            <i data-lucide="clipboard-check" class="w-4 h-4 text-slate-700"></i>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">3. Operational Fleet Action Guidelines</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-700">
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
                <span class="font-bold text-rose-700 flex items-center gap-1.5 mb-1">
                    <span class="w-2 h-2 rounded-full bg-rose-600"></span> High Urgency Vehicles
                </span>
                <p class="leading-relaxed text-gray-600">
                    Immediately ground or route to workshop for critical safety inspections. Avoid deploying on long-haul routes or interstate deliveries until cleared by a senior mechanic.
                </p>
            </div>
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
                <span class="font-bold text-amber-700 flex items-center gap-1.5 mb-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Medium Urgency Vehicles
                </span>
                <p class="leading-relaxed text-gray-600">
                    Schedule preventive maintenance appointments within the next 14 calendar days during scheduled off-peak driver shifts to minimize operational downtime.
                </p>
            </div>
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
                <span class="font-bold text-emerald-700 flex items-center gap-1.5 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span> Low Urgency Vehicles
                </span>
                <p class="leading-relaxed text-gray-600">
                    Continue standard daily trip logging and routine pre-trip inspections. Next comprehensive assessment will be calculated automatically upon milestone mileage.
                </p>
            </div>
        </div>
    </div>

    {{-- Section 4: Inventory Demand Advisory (Recommendation only, never auto-purchase) --}}
    <div>
        <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2 flex-wrap gap-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900">4. Spare Parts Inventory Restock Demand Advisory</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">
                    Algorithmically aggregated demand derived from pending High and Medium fleet repair alerts.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 rounded font-semibold">
                    Advisory Only &bull; Never Auto-Purchased
                </span>
            </div>
        </div>

        {{-- Advisory Notification Callout --}}
        <div class="mb-4 p-3.5 rounded-xl bg-blue-50/60 border border-blue-200 text-xs text-blue-900 flex items-start gap-2.5">
            <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0 mt-0.5"></i>
            <div>
                <strong class="font-bold">Inventory Planning Guideline:</strong>
                {{ $inventoryAdvisory['disclaimer'] }}
            </div>
        </div>

        @if(empty($inventoryAdvisory['advisory_items']))
        <div class="p-6 text-center bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-500">
            No spare parts restock requirements flagged. Current workshop parts inventory is sufficient for pending fleet maintenance needs.
        </div>
        @else
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase font-bold tracking-wider border-b border-gray-200">
                        <th class="py-2.5 px-3">Part Details</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3 text-center">Projected Qty</th>
                        <th class="py-2.5 px-3 text-center">Current Stock</th>
                        <th class="py-2.5 px-3 text-center">Min Stock</th>
                        <th class="py-2.5 px-3 text-center">Shortfall</th>
                        <th class="py-2.5 px-3 text-right">Unit Price</th>
                        <th class="py-2.5 px-3 text-right">Est. Budget</th>
                        <th class="py-2.5 px-3">Preferred Supplier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($inventoryAdvisory['advisory_items'] as $item)
                    <tr class="hover:bg-gray-50/60 transition {{ $item['needs_restock'] ? 'bg-amber-50/20' : '' }}">
                        <td class="py-3 px-3">
                            <span class="font-bold text-gray-900">{{ $item['part_name'] }}</span>
                            <div class="text-[11px] text-gray-400 font-mono">{{ $item['part_number'] }}</div>
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700 capitalize">
                                {{ $item['category'] }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center font-bold text-gray-800">
                            {{ $item['recommended_qty'] }} units
                        </td>
                        <td class="py-3 px-3 text-center font-mono text-gray-700">
                            {{ $item['current_stock'] }}
                        </td>
                        <td class="py-3 px-3 text-center font-mono text-gray-500">
                            {{ $item['min_stock'] }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($item['shortfall_units'] > 0)
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    -{{ $item['shortfall_units'] }} units
                                </span>
                            @else
                                <span class="text-emerald-700 font-medium">Sufficient</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-right font-mono text-gray-700">
                            RM {{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="py-3 px-3 text-right font-mono font-bold {{ $item['estimated_cost'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                            RM {{ number_format($item['estimated_cost'], 2) }}
                        </td>
                        <td class="py-3 px-3 text-gray-600">
                            {{ $item['supplier_name'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50/90 font-bold border-t border-gray-200">
                        <td colspan="5" class="py-2.5 px-3 text-gray-700 uppercase tracking-wider text-[11px]">
                            Aggregated Recommended Restock Budget
                        </td>
                        <td class="py-2.5 px-3 text-center text-rose-700 font-mono">
                            {{ $inventoryAdvisory['total_shortfall_units'] }} units
                        </td>
                        <td></td>
                        <td class="py-2.5 px-3 text-right font-mono text-slate-900 text-sm">
                            RM {{ number_format($inventoryAdvisory['total_estimated_budget'], 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- Official Sign-Off & Supervisor Endorsement Block --}}
    <div class="pt-8 border-t-2 border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs text-gray-600">
            <div>
                <p class="font-bold uppercase tracking-wider text-gray-800">System Certification & Verification</p>
                <p class="mt-1 leading-relaxed">
                    This report was generated using the Teraju Setia Enterprise Multi-Model Ensemble Predictive Engine.
                    Model ensemble incorporates Decision Trees, Random Forests, Support Vector Machines, Logistic Regression, and Expert Rule Baselines.
                </p>
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <p class="font-semibold text-gray-900">Dr. Nureize</p>
                    <p class="text-[11px] text-gray-500">Academic & Technical Project Supervisor</p>
                </div>
            </div>

            <div class="md:text-right">
                <p class="font-bold uppercase tracking-wider text-gray-800">Workshop Authorization Sign-off</p>
                <div class="mt-6 border-b border-gray-400 w-48 ml-auto"></div>
                <p class="mt-2 text-xs font-bold text-gray-900">Teraju Setia Enterprise Workshop Manager</p>
                <p class="text-[11px] text-gray-500">Authorized Commercial Signature & Stamp</p>
            </div>
        </div>
    </div>

</div>

@endsection
