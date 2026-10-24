@extends('layouts.app')
@section('page-title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    @php
        $backPrefix = match(auth()->user()->role) {
            'admin' => '/admin', 'staff' => '/staff', 'coordinator' => '/coordinator',
            'corporate' => '/client', default => '/customer',
        };
    @endphp
    <div class="flex justify-between items-center no-print flex-wrap gap-3">
        <a href="{{ $backPrefix }}/invoices" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Invoices
        </a>
        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()->role, ['admin','staff']))
            <form method="POST" action="{{ $backPrefix }}/invoices/{{ $invoice->id }}/resend">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 text-xs font-semibold bg-slate-900 text-white px-3.5 py-2 rounded-xl hover:bg-slate-800 transition shadow-xs">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i> Email Invoice
                </button>
            </form>
            @endif
            <a href="{{ $backPrefix }}/invoices/{{ $invoice->id }}/pdf"
                class="flex items-center gap-1.5 text-xs font-semibold bg-emerald-600 text-white px-3.5 py-2 rounded-xl hover:bg-emerald-700 transition shadow-xs">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download PDF
            </a>
            <button onclick="window.print()" class="flex items-center gap-1.5 text-xs font-semibold bg-white border border-gray-200 text-gray-700 px-3.5 py-2 rounded-xl hover:bg-gray-50 transition shadow-xs">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-gray-500"></i> Print
            </button>
        </div>
    </div>


    {{-- Invoice Card --}}
    <div class="invoice-sheet bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-10">
        <div class="flex justify-between items-start mb-8 pb-6 border-b border-gray-100 flex-wrap gap-4">
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
                    {{ $invoice->invoice_number }}
                </span>
                <div class="mt-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                        {{ $invoice->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          ($invoice->status === 'partial' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                          'bg-rose-50 text-rose-700 border-rose-200') }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $invoice->status === 'paid' ? 'bg-emerald-500' : ($invoice->status === 'partial' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-2">Issued: {{ $invoice->created_at->format('d M Y') }}</p>
                @if($invoice->due_date)
                <p class="text-xs font-medium text-gray-600">Due: {{ $invoice->due_date->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="p-4 rounded-xl bg-gray-50/70 border border-gray-100">
                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Billed To</p>
                <p class="font-bold text-base text-gray-900">{{ $invoice->customer_name }}</p>
            </div>
            <div class="p-4 rounded-xl bg-gray-50/70 border border-gray-100">
                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Vehicle / Work Order</p>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                        {{ $invoice->jobCard->vehicle->plate_number ?? '—' }}
                    </span>
                    <span class="text-xs text-gray-500">Job Card #{{ $invoice->jobCard->id }}</span>
                </div>
                <p class="text-xs font-medium text-gray-700 mt-1">{{ $invoice->jobCard->jobType->name ?? $invoice->jobCard->appointment->service_type ?? 'Service Order' }}</p>
            </div>
        </div>

        <div class="overflow-x-auto mb-6">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                        <th class="py-3 px-3">Item / Service Description</th>
                        <th class="py-3 px-3 text-right">Qty</th>
                        <th class="py-3 px-3 text-right">Unit Price</th>
                        <th class="py-3 px-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @foreach($invoice->jobCard->parts as $p)
                    <tr class="hover:bg-gray-50/50">
                        <td class="py-3 px-3 font-medium text-gray-900">{{ $p->sparePart->name ?? 'Part' }}</td>
                        <td class="py-3 px-3 text-right text-gray-600">{{ $p->quantity }}</td>
                        <td class="py-3 px-3 text-right text-gray-600">RM {{ number_format($p->unit_price, 2) }}</td>
                        <td class="py-3 px-3 text-right font-semibold text-gray-900">RM {{ number_format($p->quantity * $p->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                    @foreach($invoice->jobCard->labourCharges as $l)
                    <tr class="hover:bg-gray-50/50">
                        <td class="py-3 px-3 font-medium text-gray-900">
                            {{ $l->description }}
                            <span class="text-xs text-purple-600 font-semibold ml-1">(Labour)</span>
                        </td>
                        <td class="py-3 px-3 text-right text-gray-600">1</td>
                        <td class="py-3 px-3 text-right text-gray-600">RM {{ number_format($l->charge, 2) }}</td>
                        <td class="py-3 px-3 text-right font-semibold text-gray-900">RM {{ number_format($l->charge, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
            <div class="w-72 space-y-2 text-sm">
                <div class="flex justify-between text-gray-500 text-xs">
                    <span>Subtotal</span><span class="font-semibold text-gray-700">RM {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-500 text-xs">
                    <span>Sales Tax ({{ $invoice->tax_rate }}%)</span><span class="font-semibold text-gray-700">RM {{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 text-base pt-2 border-t border-gray-100">
                    <span>Total Due</span><span class="text-lg">RM {{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="flex justify-between text-emerald-700 font-semibold text-xs">
                    <span>Amount Paid</span><span>RM {{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-rose-600 text-sm pt-2 border-t border-gray-100">
                    <span>Outstanding Balance</span><span>RM {{ number_format($invoice->balance, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Signatures Block (Matches PO Invoice & provides official workshop sign-off) --}}
        <div class="signature-block grid grid-cols-2 gap-6 pt-5 border-t border-gray-100 text-xs print:pt-2 mt-6">
            <div>
                <p class="font-bold text-gray-600 uppercase tracking-wider text-[11px] mb-5 print:mb-3">Customer Signature / Acknowledgement:</p>
                <div class="border-b border-gray-300 w-44 mb-1.5"></div>
                <p class="font-bold text-gray-800">{{ $invoice->customer_name }}</p>
                <p class="text-[11px] text-gray-400">Date: ____ / ____ / ________</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-gray-600 uppercase tracking-wider text-[11px] mb-5 print:mb-3">Authorized Workshop Cashier:</p>
                <div class="border-b border-gray-300 w-44 ml-auto mb-1.5"></div>
                <p class="font-bold text-gray-800">Teraju Setia Management</p>
                <p class="text-[11px] text-gray-400">Official Receipt &amp; Tax Invoice</p>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="footer-note mt-6 pt-3 border-t border-gray-100 text-center text-[10px] text-gray-400 print:mt-2 print:pt-1">
            <p>Computer-generated Official Tax Invoice &bull; Teraju Setia Enterprise &bull; Page 1 of 1</p>
        </div>
    </div>

    {{-- Payment History & Payment Recording Section --}}
    <div id="payment-section" class="payment-history-sheet bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 md:p-8 print:p-4 print:mt-4 print:border-gray-200 scroll-mt-8 transition-all duration-300">
        <h3 class="text-base font-bold text-gray-900 tracking-tight mb-4">Payment Transactions History</h3>
        @forelse($invoice->payments as $pay)
        <div class="flex justify-between items-center border-b border-gray-100 py-3 text-sm">
            <div>
                <p class="font-bold text-gray-900">RM {{ number_format($pay->amount, 2) }} — <span class="capitalize font-semibold text-gray-700">{{ str_replace('_',' ',$pay->method) }}</span></p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $pay->paid_at->format('d M Y, H:i') }}
                    @if($pay->reference_no) &bull; Ref: <span class="font-mono text-gray-700">{{ $pay->reference_no }}</span> @endif
                    @if($pay->recorder) &bull; Processed by {{ $pay->recorder->name }} @endif
                </p>
            </div>
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded">
                Verified
            </span>
        </div>
        @empty
        <p class="text-gray-400 text-xs py-2">No payments recorded yet for this invoice.</p>
        @endforelse

        @if(in_array(auth()->user()->role, ['admin','staff']) && $invoice->balance > 0)
        <form method="POST"
            action="/{{ auth()->user()->role === 'admin' ? 'admin' : 'staff' }}/invoices/{{ $invoice->id }}/payments"
            class="flex gap-3 items-end flex-wrap mt-5 pt-5 border-t border-gray-100 no-print">
            @csrf
            <div class="w-36">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Amount (RM) *</label>
                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->balance }}"
                    value="{{ $invoice->balance }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
            </div>
            <div class="w-44">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Payment Method *</label>
                <select name="method" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div class="flex-1 min-w-44">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Reference No.</label>
                <input type="text" name="reference_no" placeholder="e.g. TXN-89218"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
            </div>
            <button type="submit"
                class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-xs font-semibold hover:bg-emerald-700 transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="check" class="w-3.5 h-3.5"></i> Record Payment
            </button>
        </form>
        @endif
    </div>
</div>

<style>
#payment-section {
    scroll-margin-top: 2rem;
}
#payment-section:target {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2) !important;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 8mm 12mm 8mm 12mm !important;
    }
    .invoice-sheet, .payment-history-sheet {
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