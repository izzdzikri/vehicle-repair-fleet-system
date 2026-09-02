@extends('layouts.app')
@section('page-title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    @php
        $backPrefix = match(auth()->user()->role) {
            'admin' => '/admin', 'staff' => '/staff',
            'corporate' => '/client', default => '/customer',
        };
    @endphp
    <div class="flex justify-between items-center no-print flex-wrap gap-2">
        <a href="{{ $backPrefix }}/invoices" class="text-sm text-blue-600 hover:underline">← Back to Invoices</a>
        <div class="flex gap-2">
            @if(in_array(auth()->user()->role, ['admin','staff']))
            <form method="POST" action="{{ $backPrefix }}/invoices/{{ $invoice->id }}/resend">
                @csrf
                <button type="submit" class="flex items-center gap-2 text-sm bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i data-lucide="mail" class="w-4 h-4"></i> Email Invoice
                </button>
            </form>
            @endif
            <button onclick="window.print()" class="flex items-center gap-2 text-sm bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
                <i data-lucide="printer" class="w-4 h-4"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    {{-- Invoice Card --}}
    <div class="bg-white rounded-lg shadow p-8">
        <div class="flex justify-between items-start mb-8 pb-6 border-b">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Teraju Setia Enterprise</h2>
                <p class="text-sm text-gray-400 mt-1">No 14, Jalan Perindustrian 7, Kawasan Perindustrian Senai, 81400 Senai, Johor</p>
                <p class="text-sm text-gray-400">07-5551234</p>
            </div>
            <div class="text-right">
                <p class="text-xl font-bold text-blue-700">{{ $invoice->invoice_number }}</p>
                <span class="px-2 py-1 rounded-full text-xs font-medium mt-1 inline-block
                    {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' :
                      ($invoice->status === 'partial' ? 'bg-yellow-100 text-yellow-700' :
                      'bg-red-100 text-red-700') }}">
                    {{ ucfirst($invoice->status) }}
                </span>
                <p class="text-xs text-gray-400 mt-2">Issued: {{ $invoice->created_at->format('d M Y') }}</p>
                @if($invoice->due_date)
                <p class="text-xs text-gray-400">Due: {{ $invoice->due_date->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8">
            <div>
                <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Billed To</p>
                <p class="font-semibold text-gray-800">{{ $invoice->customer_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Vehicle / Job Card</p>
                <p class="font-semibold text-gray-800">{{ $invoice->jobCard->vehicle->plate_number ?? '—' }}</p>
                <p class="text-sm text-gray-500">Job Card #{{ $invoice->jobCard->id }} &bull; {{ $invoice->jobCard->jobType->name ?? $invoice->jobCard->appointment->service_type ?? '—' }}</p>
            </div>
        </div>

        <table class="w-full text-sm mb-6">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2">Description</th>
                    <th class="pb-2 text-right">Qty</th>
                    <th class="pb-2 text-right">Unit</th>
                    <th class="pb-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->jobCard->parts as $p)
                <tr class="border-b">
                    <td class="py-2">{{ $p->sparePart->name ?? 'Part' }}</td>
                    <td class="py-2 text-right">{{ $p->quantity }}</td>
                    <td class="py-2 text-right">RM {{ number_format($p->unit_price, 2) }}</td>
                    <td class="py-2 text-right">RM {{ number_format($p->quantity * $p->unit_price, 2) }}</td>
                </tr>
                @endforeach
                @foreach($invoice->jobCard->labourCharges as $l)
                <tr class="border-b">
                    <td class="py-2">{{ $l->description }} <span class="text-xs text-gray-400">(Labour)</span></td>
                    <td class="py-2 text-right">1</td>
                    <td class="py-2 text-right">RM {{ number_format($l->charge, 2) }}</td>
                    <td class="py-2 text-right">RM {{ number_format($l->charge, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end">
            <div class="w-64 space-y-1 text-sm">
                <div class="flex justify-between text-gray-500">
                    <span>Subtotal</span><span>RM {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>Tax ({{ $invoice->tax_rate }}%)</span><span>RM {{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-800 text-base pt-2 border-t">
                    <span>Total</span><span>RM {{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="flex justify-between text-green-600">
                    <span>Paid</span><span>RM {{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-red-600 pt-1 border-t">
                    <span>Balance</span><span>RM {{ number_format($invoice->balance, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment History --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Payment History</h3>
        @forelse($invoice->payments as $pay)
        <div class="flex justify-between items-center border-b py-2 text-sm">
            <div>
                <p class="font-medium">RM {{ number_format($pay->amount, 2) }} — {{ ucfirst(str_replace('_',' ',$pay->method)) }}</p>
                <p class="text-xs text-gray-400">
                    {{ $pay->paid_at->format('d M Y H:i') }}
                    @if($pay->reference_no) &bull; Ref: {{ $pay->reference_no }} @endif
                    @if($pay->recorder) &bull; by {{ $pay->recorder->name }} @endif
                </p>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm">No payments recorded yet.</p>
        @endforelse

        @if(in_array(auth()->user()->role, ['admin','staff']) && $invoice->balance > 0)
        <form method="POST"
            action="/{{ auth()->user()->role === 'admin' ? 'admin' : 'staff' }}/invoices/{{ $invoice->id }}/payments"
            class="flex gap-3 items-end flex-wrap mt-4 pt-4 border-t no-print">
            @csrf
            <div class="w-32">
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount (RM)</label>
                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->balance }}"
                    value="{{ $invoice->balance }}"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                <select name="method" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div class="flex-1 min-w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reference No (optional)</label>
                <input type="text" name="reference_no"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="bg-green-600 text-white px-5 py-2 rounded text-sm hover:bg-green-700">
                Record Payment
            </button>
        </form>
        @endif
    </div>
</div>
@endsection