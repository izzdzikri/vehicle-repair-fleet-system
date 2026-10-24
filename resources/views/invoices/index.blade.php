@extends('layouts.app')
@section('page-title', 'Invoices')

@section('content')

@if(session('success'))
<div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-medium text-emerald-800 flex items-center gap-2">
    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs font-medium text-rose-800 flex items-center gap-2">
    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
    <span>{{ session('error') }}</span>
</div>
@endif

<div class="flex justify-between items-center mb-6 flex-wrap gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Billing & Invoices</h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                {{ $invoices->total() }} issued invoices
            </span>
        </div>
        <p class="text-sm text-gray-500 mt-1">Manage completed job billings, tax computations, and customer payments</p>
    </div>
</div>

@if(in_array(auth()->user()->role, ['admin','staff']))
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6">
    <div class="mb-4">
        <h2 class="text-base font-bold text-gray-900 tracking-tight">Generate Invoice from Completed Job</h2>
        <p class="text-xs text-gray-500 mt-0.5">Quickly issue formal billings for repaired vehicles</p>
    </div>
    @if($pendingJobCards->isEmpty())
    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-center">
        <p class="text-xs font-medium text-gray-500">No completed job cards are currently awaiting invoicing.</p>
    </div>
    @else
    <form method="POST"
        action="/{{ auth()->user()->role === 'admin' ? 'admin' : 'staff' }}/invoices/generate"
        class="flex gap-3 items-end flex-wrap">
        @csrf
        <div class="flex-1 min-w-64">
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Completed Job Card *</label>
            <select name="job_card_id"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
                <option value="">Select completed job</option>
                @foreach($pendingJobCards as $jc)
                <option value="{{ $jc->id }}">
                    #{{ $jc->id }} — {{ $jc->vehicle->plate_number ?? '?' }}
                    — {{ $jc->appointment && $jc->appointment->is_walkin ? ($jc->appointment->walkin_name ?? 'Walk-in') : ($jc->appointment->user->name ?? '?') }}
                    — RM {{ number_format($jc->total_cost, 2) }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="w-32">
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Tax % (SST)</label>
            <input type="number" name="tax_rate" value="0" min="0" max="100" step="0.01"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
        </div>
        <div class="w-44">
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Due Date</label>
            <input type="date" name="due_date"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
        </div>
        <button type="submit"
            class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
            Generate Invoice
        </button>
    </form>
    @endif
</div>
@endif

{{-- Search --}}
<form method="GET" class="mb-5 flex gap-2 max-w-md">
    <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
        <input type="text" name="search" value="{{ $search }}"
            placeholder="Search invoice number or customer..."
            class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
    </div>
    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">Search</button>
    @if($search)
    <a href="{{ url()->current() }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 flex items-center">Clear</a>
    @endif
</form>

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                <th class="px-5 py-3.5">Invoice #</th>
                <th class="px-5 py-3.5">Billed To</th>
                <th class="px-5 py-3.5">Vehicle</th>
                <th class="px-5 py-3.5">Gross Total</th>
                <th class="px-5 py-3.5">Paid Amount</th>
                <th class="px-5 py-3.5">Status</th>
                <th class="px-5 py-3.5">Issued Date</th>
                <th class="px-5 py-3.5 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @php
                $prefix = match(auth()->user()->role) {
                    'admin' => 'admin', 'staff' => 'staff', 'coordinator' => 'coordinator',
                    'corporate' => 'client', default => 'customer',
                };
            @endphp
            @forelse($invoices as $inv)
            <tr class="hover:bg-gray-50/60 transition">
                <td class="px-5 py-4">
                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2.5 py-1 rounded border border-gray-200">
                        {{ $inv->invoice_number }}
                    </span>
                </td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $inv->customer_name }}</td>
                <td class="px-5 py-4 font-mono text-xs font-bold text-gray-700">{{ $inv->jobCard->vehicle->plate_number ?? '—' }}</td>
                <td class="px-5 py-4 font-bold text-sm text-gray-900">RM {{ number_format($inv->total, 2) }}</td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-700">RM {{ number_format($inv->amount_paid, 2) }}</td>
                <td class="px-5 py-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                        {{ $inv->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          ($inv->status === 'partial' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                          'bg-rose-50 text-rose-700 border-rose-200') }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $inv->status === 'paid' ? 'bg-emerald-500' : ($inv->status === 'partial' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                        {{ ucfirst($inv->status) }}
                    </span>
                </td>
                <td class="px-5 py-4 text-xs font-medium text-gray-500">{{ $inv->created_at->format('d M Y') }}</td>
                <td class="px-5 py-4 text-right">
                    <a href="/{{ $prefix }}/invoices/{{ $inv->id }}"
                        class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition">
                        View &rarr;
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-12 text-center">
                    <x-empty-state icon="receipt" title="No invoices found" subtitle="Generate an invoice from a completed job card above." />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($invoices->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection