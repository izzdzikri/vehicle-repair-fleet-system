@extends('layouts.app')
@section('page-title', 'Purchase Orders')

@section('content')

<div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
    ℹ Draft purchase orders are auto-generated whenever a spare part's stock drops to or below its minimum threshold.
    Review, assign a supplier, mark ordered, then mark received once stock arrives.
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">
            Purchase Orders <span class="text-sm font-normal text-gray-400 ml-1">{{ $orders->count() }} total</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[800px]">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Part</th>
                <th class="px-4 py-3">Qty</th>
                <th class="px-4 py-3">Supplier</th>
                <th class="px-4 py-3">Unit Cost</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Source</th>
                <th class="px-4 py-3 w-12">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $po)
            <tr class="border-b hover:bg-gray-50" x-data="{ open: false, show: false }" @click.outside="open = false">
                <td class="px-4 py-3 font-medium">
                    {{ $po->sparePart->name ?? '—' }}
                    <p class="text-xs text-gray-400">Stock: {{ $po->sparePart->stock ?? '—' }} / min {{ $po->sparePart->min_stock ?? '—' }}</p>
                </td>
                <td class="px-4 py-3">{{ $po->quantity }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $po->supplier->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $po->unit_cost ? 'RM '.number_format($po->unit_cost,2) : '—' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $po->status === 'received' ? 'bg-green-100 text-green-700' :
                          ($po->status === 'ordered' ? 'bg-blue-100 text-blue-700' :
                          ($po->status === 'cancelled' ? 'bg-gray-100 text-gray-500' : 'bg-yellow-100 text-yellow-700')) }}">
                        {{ ucfirst($po->status) }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($po->auto_generated)
                    <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Auto</span>
                    @else
                    <span class="text-xs text-gray-400">Manual</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if(in_array($po->status, ['draft', 'ordered']))
                    <div class="relative inline-block text-left">
                        <button @click="open = !open" class="p-1.5 rounded hover:bg-gray-100 text-gray-500">
                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                        </button>
                        <div x-show="open" x-transition
                            class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border py-1 z-20"
                            style="display:none">
                            @if($po->status === 'draft')
                            <button type="button" @click="open = false; show = true"
                                class="w-full flex items-center gap-2 px-4 py-2 text-sm text-blue-700 hover:bg-blue-50">
                                <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i> Mark Ordered
                            </button>
                            <form method="POST" action="/admin/purchase-orders/{{ $po->id }}/cancel">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-t">
                                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Cancel
                                </button>
                            </form>
                            @elseif($po->status === 'ordered')
                            <form method="POST" action="/admin/purchase-orders/{{ $po->id }}/receive">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-green-700 hover:bg-green-50">
                                    <i data-lucide="package-check" class="w-3.5 h-3.5"></i> Mark Received
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                    @if($po->status === 'draft')
                    <div x-show="show" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none">
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6" @click.outside="show = false">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Order {{ $po->sparePart->name ?? '' }}</h3>
                            <form method="POST" action="/admin/purchase-orders/{{ $po->id }}/order" class="space-y-3">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier *</label>
                                    <select name="supplier_id" class="w-full border rounded px-3 py-2 text-sm" required>
                                        <option value="">Select supplier</option>
                                        @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (RM)</label>
                                    <input type="number" name="unit_cost" step="0.01" min="0" class="w-full border rounded px-3 py-2 text-sm">
                                </div>
                                <div class="flex gap-3 pt-2">
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Confirm Order</button>
                                    <button type="button" @click="show = false" class="px-4 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                    @else
                    <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="py-8 text-center text-gray-400">No purchase orders. Stock levels are healthy.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection