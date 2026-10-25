@extends('layouts.app')
@section('page-title', 'Corporate Fleet Maintenance Statement')

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
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Fleet Maintenance Statement</h2>
        <p class="text-sm text-gray-500 mt-0.5">Comprehensive expense summary, vehicle cost allocation, and service ledger for corporate accounting.</p>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
        {{-- Period Filter Form --}}
        <form method="GET" action="{{ route('client.statement') }}" class="flex items-center gap-2">
            <label class="text-xs font-bold text-gray-600 uppercase tracking-wider">Period:</label>
            <input type="month" name="month" value="{{ $month !== 'all' ? $month : '' }}"
                class="text-xs border border-gray-300 rounded-lg px-3 py-2 bg-white text-gray-800 focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="text-xs font-semibold px-3 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-gray-700 transition">
                Filter
            </button>
            @if($month !== 'all')
            <a href="{{ route('client.statement', ['month' => 'all']) }}" class="text-xs font-semibold px-3 py-2 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg text-gray-600 transition">
                Show All
            </a>
            @endif
        </form>

        {{-- Export PDF --}}
        <a href="{{ route('client.statement', ['month' => $month, 'format' => 'pdf']) }}"
            class="text-xs font-bold px-3.5 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 shadow-xs transition inline-flex items-center gap-1.5">
            <i data-lucide="download" class="w-3.5 h-3.5 text-gray-500"></i> Download PDF
        </a>

        {{-- Print Statement Button (Uniform Primary CTA styling) --}}
        <button type="button" onclick="window.print()"
            class="text-xs font-bold px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition inline-flex items-center gap-1.5">
            <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print Statement
        </button>
    </div>
</div>

{{-- Printable Statement Container --}}
<div class="print-sheet bg-white rounded-2xl border border-gray-200/90 shadow-sm p-8">

    {{-- Statement Header --}}
    <div class="border-b-2 border-slate-900 pb-6 mb-6">
        <div class="flex justify-between items-start flex-wrap gap-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">TERAJU SETIA ENTERPRISE</h1>
                <p class="text-xs text-gray-500 mt-0.5">Commercial Vehicle Maintenance & Fleet Management Centre</p>
                <div class="text-xs text-gray-600 mt-2 leading-relaxed">
                    No. 1, Jalan Malinja 2, Taman Perindustrian Matahari<br>
                    86000 Kluang, Johor Darul Ta'zim, Malaysia<br>
                    Tel: 07-787 8728 / 019-728 8728 &bull; Email: fleet@terajusetia.com.my
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block bg-slate-900 text-white px-3.5 py-1 rounded-md text-xs font-black uppercase tracking-wider mb-2">
                    Fleet Statement
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Statement Period</p>
                <p class="text-base font-extrabold text-slate-900">
                    {{ $month !== 'all' ? \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : 'All Time Historical' }}
                </p>
                <p class="text-[11px] text-gray-400 mt-1">Generated: {{ now()->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        {{-- Client Details Card --}}
        <div class="mt-6 pt-5 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/60 p-4 rounded-xl border border-gray-100">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Statement Issued To</span>
                <h3 class="text-base font-extrabold text-gray-900 mt-0.5">{{ $company->name ?? auth()->user()->name }}</h3>
                <p class="text-xs text-gray-600 mt-0.5">{{ $company->address ?? 'Corporate Client' }}</p>
                @if($company && $company->registration_no)
                <p class="text-xs text-gray-500 mt-0.5 font-mono">Reg No: {{ $company->registration_no }}</p>
                @endif
            </div>
            <div class="md:text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Primary Account Contact</span>
                <p class="text-sm font-bold text-gray-900 mt-0.5">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ auth()->user()->email }} &bull; {{ auth()->user()->contact_no ?? 'No phone' }}</p>
                <p class="text-xs text-gray-500 mt-1">Fleet Assets: <strong>{{ $vehicles->count() }} vehicles</strong> registered</p>
            </div>
        </div>
    </div>

    {{-- Executive Summary Financials --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Total Fleet Spend</span>
            <p class="text-2xl font-black text-gray-900 tracking-tight mt-1">RM {{ number_format($totalSpent, 2) }}</p>
            <span class="text-[11px] text-gray-400 block mt-0.5">services & parts</span>
        </div>

        <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Total Invoiced</span>
            <p class="text-2xl font-black text-blue-700 tracking-tight mt-1">RM {{ number_format($totalInvoiced, 2) }}</p>
            <span class="text-[11px] text-gray-400 block mt-0.5">{{ $invoices->count() }} invoice(s)</span>
        </div>

        <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Total Settled (Paid)</span>
            <p class="text-2xl font-black text-emerald-700 tracking-tight mt-1">RM {{ number_format($totalPaid, 2) }}</p>
            <span class="text-[11px] text-emerald-600 block mt-0.5">verified receipts</span>
        </div>

        <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
            <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Outstanding Balance</span>
            <p class="text-2xl font-black {{ $outstandingBalance > 0 ? 'text-rose-600' : 'text-gray-900' }} tracking-tight mt-1">
                RM {{ number_format($outstandingBalance, 2) }}
            </p>
            <span class="text-[11px] text-gray-400 block mt-0.5">{{ $outstandingBalance > 0 ? 'Pending payment' : 'Account current' }}</span>
        </div>
    </div>

    {{-- SECTION 1: PER-VEHICLE MAINTENANCE EXPENSE BREAKDOWN --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900">1. Vehicle Expense Allocation Breakdown</h3>
            <span class="text-xs text-gray-500">{{ $vehicleSummary->count() }} active assets analyzed</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-100/80 text-gray-600 uppercase font-bold tracking-wider border-b border-gray-200">
                        <th class="py-2.5 px-3">Vehicle Plate</th>
                        <th class="py-2.5 px-3">Make & Model</th>
                        <th class="py-2.5 px-3">Current Odometer</th>
                        <th class="py-2.5 px-3 text-center">Services Logged</th>
                        <th class="py-2.5 px-3">Last Service Date</th>
                        <th class="py-2.5 px-3 text-right">Total Incurred</th>
                        <th class="py-2.5 px-3 text-right">% of Fleet Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vehicleSummary as $row)
                    @php
                        $percent = $totalSpent > 0 ? round(($row['total_spend'] / $totalSpent) * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50/60">
                        <td class="py-2.5 px-3 font-mono font-bold text-gray-900">
                            {{ $row['vehicle']->plate_number }}
                        </td>
                        <td class="py-2.5 px-3 font-medium text-gray-800">
                            {{ $row['vehicle']->brand }} {{ $row['vehicle']->model }} ({{ $row['vehicle']->year }})
                        </td>
                        <td class="py-2.5 px-3 font-mono text-gray-600">
                            {{ number_format($row['vehicle']->mileage) }} km
                        </td>
                        <td class="py-2.5 px-3 text-center font-bold text-gray-800">
                            {{ $row['service_count'] }}
                        </td>
                        <td class="py-2.5 px-3 text-gray-600">
                            {{ $row['last_service'] ? \Carbon\Carbon::parse($row['last_service'])->format('d M Y') : '—' }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-bold text-gray-900">
                            RM {{ number_format($row['total_spend'], 2) }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-medium text-gray-600">
                            {{ $percent }}%
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-gray-500">No fleet vehicle records found.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-bold border-t-2 border-gray-200 text-gray-900">
                        <td colspan="3" class="py-2.5 px-3 uppercase text-[11px]">Fleet Totals</td>
                        <td class="py-2.5 px-3 text-center">{{ $vehicleSummary->sum('service_count') }}</td>
                        <td class="py-2.5 px-3"></td>
                        <td class="py-2.5 px-3 text-right">RM {{ number_format($totalSpent, 2) }}</td>
                        <td class="py-2.5 px-3 text-right">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- SECTION 2: ITEMIZED SERVICE & INVOICE LEDGER --}}
    <div>
        <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900">2. Itemized Service & Repair Ledger</h3>
            <span class="text-xs text-gray-500">{{ $serviceHistory->count() }} logged event(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-100/80 text-gray-600 uppercase font-bold tracking-wider border-b border-gray-200">
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3">Vehicle</th>
                        <th class="py-2.5 px-3">Service Description</th>
                        <th class="py-2.5 px-3 text-center">Ref Job #</th>
                        <th class="py-2.5 px-3 text-right">Cost (RM)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($serviceHistory as $entry)
                    <tr class="hover:bg-gray-50/60">
                        <td class="py-2.5 px-3 whitespace-nowrap text-gray-600">
                            {{ \Carbon\Carbon::parse($entry->service_date)->format('d M Y') }}
                        </td>
                        <td class="py-2.5 px-3 font-mono font-bold text-gray-900 whitespace-nowrap">
                            {{ $entry->vehicle->plate_number ?? '—' }}
                        </td>
                        <td class="py-2.5 px-3 text-gray-800 font-medium leading-relaxed">
                            {{ $entry->description }}
                        </td>
                        <td class="py-2.5 px-3 text-center font-mono text-gray-500">
                            {{ $entry->job_card_id ? '#'.$entry->job_card_id : '—' }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-bold text-gray-900 whitespace-nowrap">
                            RM {{ number_format($entry->cost, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-gray-500">
                            No service records found for the selected period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Statement Footer & Disclaimer --}}
    <div class="mt-10 pt-6 border-t border-gray-200 text-[11px] text-gray-400 flex justify-between items-end flex-wrap gap-4">
        <div>
            <p class="font-bold text-gray-600">TERAJU SETIA ENTERPRISE &bull; FLEET OPERATIONS</p>
            <p class="mt-0.5">This document serves as an official accounting statement of workshop maintenance activities.</p>
            <p>For inquiries regarding billing or parts warranty, please email accounts@terajusetia.com.my.</p>
        </div>
        <div class="text-right">
            <p class="font-mono">Page 1 of 1</p>
            <p class="text-gray-400 mt-0.5">Official Computer-Generated Statement</p>
        </div>
    </div>

</div>

@endsection
