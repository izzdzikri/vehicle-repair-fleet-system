@extends('layouts.app')
@section('page-title', 'Invoices')

@section('content')

@if(session('success'))
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">{{ session('error') }}</div>
@endif

@if(in_array(auth()->user()->role, ['admin','staff']))
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Generate Invoice from Completed Job</h2>
    @if($pendingJobCards->isEmpty())
    <p class="text-sm text-gray-400">No completed job cards awaiting invoicing.</p>
    @else
    <form method="POST"
        action="/{{ auth()->user()->role === 'admin' ? 'admin' : 'staff' }}/invoices/generate"
        class="flex gap-3 items-end flex-wrap">
        @csrf
        <div class="flex-1 min-w-64">
            <label class="block text-sm font-medium text-gray-700 mb-1">Completed Job Card</label>
            <select name="job_card_id"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Select job card</option>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax % (SST)</label>
            <input type="number" name="tax_rate" value="0" min="0" max="100" step="0.01"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
            <input type="date" name="due_date"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit"
            class="bg-blue-600 text-white px-5 py-2 rounded text-sm hover:bg-blue-700">
            Generate Invoice
        </button>
    </form>
    @endif
</div>
@endif

{{-- Search --}}
<form method="GET" class="mb-4 flex gap-2">
    <input type="text" name="search" value="{{ $search }}"
        placeholder="Search by invoice number or customer..."
        class="flex-1 max-w-md border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Search</button>
    @if($search)
    <a href="{{ url()->current() }}" class="px-4 py-2 rounded text-sm border text-gray-600 hover:bg-gray-50">Clear</a>
    @endif
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-700">
            Invoices <span class="text-sm font-normal text-gray-400 ml-1">{{ $invoices->total() }} total</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Invoice #</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Vehicle</th>
                <th class="px-4 py-3">Total</th>
                <th class="px-4 py-3">Paid</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @php
                $prefix = match(auth()->user()->role) {
                    'admin' => 'admin', 'staff' => 'staff',
                    'corporate' => 'client', default => 'customer',
                };
            @endphp
            @forelse($invoices as $inv)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $inv->invoice_number }}</td>
                <td class="px-4 py-3">{{ $inv->customer_name }}</td>
                <td class="px-4 py-3">{{ $inv->jobCard->vehicle->plate_number ?? '—' }}</td>
                <td class="px-4 py-3">RM {{ number_format($inv->total, 2) }}</td>
                <td class="px-4 py-3">RM {{ number_format($inv->amount_paid, 2) }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $inv->status === 'paid' ? 'bg-green-100 text-green-700' :
                          ($inv->status === 'partial' ? 'bg-yellow-100 text-yellow-700' :
                          'bg-red-100 text-red-700') }}">
                        {{ ucfirst($inv->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-400">{{ $inv->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3">
                    <a href="/{{ $prefix }}/invoices/{{ $inv->id }}"
                        class="text-blue-600 hover:underline text-xs font-medium">View</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="py-8 text-center text-gray-400">No invoices yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($invoices->hasPages())
    <div class="px-4 py-3 border-t">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection