@extends('layouts.app')
@section('page-title', 'Purchase Order ' . $purchaseOrder->po_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6 print:space-y-0">

    {{-- Top Action Bar (hidden on print) --}}
    <div class="flex justify-between items-center no-print flex-wrap gap-3">
        <a href="/admin/purchase-orders"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Purchase Orders
        </a>
        <div class="flex items-center gap-2">
            <a href="/admin/purchase-orders/{{ $purchaseOrder->id }}/pdf"
                class="flex items-center gap-1.5 text-xs font-semibold bg-emerald-600 text-white px-3.5 py-2 rounded-xl hover:bg-emerald-700 transition shadow-xs">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download PDF
            </a>
            <button onclick="window.print()"
                class="flex items-center gap-1.5 text-xs font-semibold bg-white border border-gray-200 text-gray-700 px-3.5 py-2 rounded-xl hover:bg-gray-50 transition shadow-xs">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-gray-500"></i> Print
            </button>
        </div>
    </div>

    {{-- Invoice / Goods Receipt Note Document --}}
    <div class="invoice-sheet bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-10 print:border-none print:shadow-none print:p-0">

        {{-- Header Section (Matches Customer Invoice Header 1:1) --}}
        <div class="flex justify-between items-start mb-6 pb-6 border-b border-gray-100 flex-wrap gap-4 print:mb-3 print:pb-3">
            <div class="flex items-start gap-4">
                <img src="/images/invoice-logo.png" alt="Teraju Setia Enterprise Logo" class="h-16 w-auto object-contain shrink-0">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Teraju Setia Enterprise</h2>
                    <p class="text-xs text-gray-500 mt-1">No. 1, Jalan Malinja 2, Taman Perindustrian Matahari</p>
                    <p class="text-xs text-gray-500">86000 Kluang, Johor Darul Ta'zim &bull; Tel: 07-787 8728</p>
                </div>
            </div>

            <div class="text-right">
                <span class="font-mono text-base font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded-lg border border-gray-200 inline-block">
                    {{ $purchaseOrder->po_number }}
                </span>

                <div class="mt-2">
                    @if($purchaseOrder->status === 'received')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Received & Stocked
                    </span>
                    @elseif($purchaseOrder->status === 'ordered')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Dispatched
                    </span>
                    @elseif($purchaseOrder->status === 'cancelled')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border bg-gray-100 text-gray-600 border-gray-200">
                        Cancelled
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border bg-amber-50 text-amber-700 border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Draft Requisition
                    </span>
                    @endif
                </div>

                <p class="text-xs text-gray-400 mt-2">Issued: {{ $purchaseOrder->created_at->format('d M Y') }}</p>
                @if($purchaseOrder->received_at)
                <p class="text-xs font-medium text-emerald-700">Delivered: {{ $purchaseOrder->received_at->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        {{-- Meta Cards Section (Matches Customer Invoice Billed To & Vehicle Cards) --}}
        <div class="grid grid-cols-2 gap-6 mb-6 print:mb-3 print:gap-4">
            <div class="p-4 rounded-xl bg-gray-50/70 border border-gray-100 print:p-2.5">
                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Supplier / Vendor</p>
                <p class="font-bold text-base text-gray-900">{{ $purchaseOrder->supplier->name ?? 'Johor Motor Supplies' }}</p>
                @if($purchaseOrder->supplier?->contact_person)
                <p class="text-xs text-gray-500 mt-0.5">Attn: {{ $purchaseOrder->supplier->contact_person }}</p>
                @endif
                @if($purchaseOrder->supplier?->phone)
                <p class="text-xs text-gray-500 font-mono">Tel: {{ $purchaseOrder->supplier->phone }}</p>
                @endif
            </div>

            <div class="p-4 rounded-xl bg-gray-50/70 border border-gray-100 print:p-2.5">
                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Delivery / Warehouse Target</p>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                        Main Parts Store
                    </span>
                    <span class="text-xs text-gray-500">Bay A &bull; Rack {{ $purchaseOrder->sparePart->shelf_location ?? '01' }}</span>
                </div>
                <p class="text-xs font-medium text-gray-700 mt-1">
                    {{ $purchaseOrder->auto_generated ? 'Automated Low-Stock Heuristic' : 'Manual Workshop Requisition' }}
                    &bull; {{ $purchaseOrder->creator->name ?? 'Admin' }}
                </p>
            </div>
        </div>

        {{-- Items Table (Matches Customer Invoice Table) --}}
        <div class="overflow-x-auto mb-6 print:mb-3">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                        <th class="py-3 px-3 print:py-1.5 print:px-2">Item / Component Description</th>
                        <th class="py-3 px-3 print:py-1.5 print:px-2">SKU / Part No.</th>
                        <th class="py-3 px-3 text-right print:py-1.5 print:px-2">Qty</th>
                        <th class="py-3 px-3 text-right print:py-1.5 print:px-2">Unit Cost</th>
                        <th class="py-3 px-3 text-right print:py-1.5 print:px-2">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <tr class="hover:bg-gray-50/50">
                        <td class="py-3 px-3 print:py-2 print:px-2 font-medium text-gray-900">
                            {{ $purchaseOrder->sparePart->name ?? 'Spare Part Component' }}
                            @if($purchaseOrder->sparePart && $purchaseOrder->sparePart->brand)
                            <span class="text-xs text-gray-400 block font-normal">Brand: {{ $purchaseOrder->sparePart->brand }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 print:py-2 print:px-2 font-mono text-xs text-gray-600">
                            {{ $purchaseOrder->sparePart->part_number ?? '—' }}
                        </td>
                        <td class="py-3 px-3 print:py-2 print:px-2 text-right font-semibold text-gray-900">
                            {{ number_format($purchaseOrder->quantity) }}
                        </td>
                        <td class="py-3 px-3 print:py-2 print:px-2 text-right text-gray-600">
                            RM {{ number_format($purchaseOrder->unit_cost ?? 0, 2) }}
                        </td>
                        <td class="py-3 px-3 print:py-2 print:px-2 text-right font-semibold text-gray-900">
                            RM {{ number_format($purchaseOrder->total_cost, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Financial Totals (Matches Customer Invoice Totals) --}}
        <div class="flex justify-end pt-4 border-t border-gray-100 mb-6 print:mb-3 print:pt-2">
            <div class="w-72 space-y-2 text-sm print:space-y-1">
                <div class="flex justify-between text-gray-500 text-xs">
                    <span>Replenishment Subtotal</span>
                    <span class="font-semibold text-gray-700">RM {{ number_format($purchaseOrder->total_cost, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-500 text-xs">
                    <span>Workshop SST / Tax (0%)</span>
                    <span class="font-semibold text-gray-700">RM 0.00</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 text-base pt-2 border-t border-gray-100">
                    <span>Total Payable</span>
                    <span class="text-lg">RM {{ number_format($purchaseOrder->total_cost, 2) }}</span>
                </div>
                <div class="flex justify-between text-emerald-700 font-semibold text-xs">
                    <span>Receipt Status</span>
                    <span>{{ $purchaseOrder->status === 'received' ? 'Fully Received & Stocked' : ucfirst($purchaseOrder->status) }}</span>
                </div>
            </div>
        </div>

        {{-- Compact Signatures Block (Guaranteed 1-Page Fit) --}}
        <div class="signature-block grid grid-cols-2 gap-6 pt-5 border-t border-gray-100 text-xs print:pt-2">
            <div>
                <p class="font-bold text-gray-600 uppercase tracking-wider text-[11px] mb-5 print:mb-3">Received & Stocked By:</p>
                <div class="border-b border-gray-300 w-44 mb-1.5"></div>
                <p class="font-bold text-gray-800">Storekeeper / Technician</p>
                <p class="text-[11px] text-gray-400">Date: {{ $purchaseOrder->received_at ? $purchaseOrder->received_at->format('d M Y') : '____ / ____ / ________' }}</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-gray-600 uppercase tracking-wider text-[11px] mb-5 print:mb-3">Authorized Workshop Manager:</p>
                <div class="border-b border-gray-300 w-44 ml-auto mb-1.5"></div>
                <p class="font-bold text-gray-800">Teraju Setia Management</p>
                <p class="text-[11px] text-gray-400">Approved for Accounts Payable</p>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="footer-note mt-6 pt-3 border-t border-gray-100 text-center text-[10px] text-gray-400 print:mt-2 print:pt-1">
            <p>Computer-generated Goods Receipt & Purchase Order Note &bull; Teraju Setia Enterprise &bull; Page 1 of 1</p>
        </div>

    </div>

</div>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm 12mm 8mm 12mm !important;
    }
    .invoice-sheet {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    .signature-block, .footer-note, table, tr {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
}
</style>
@endsection
