@extends('layouts.app')
@section('page-title', 'Purchase Orders')

@section('content')

<div x-data="{
    activeTab: 'active',
    showAddModal: false,
    orderModal: { show: false, id: null, partName: '', supplierId: '', unitCost: '' },
    openOrderModal(po) {
        this.orderModal.id = po.id;
        this.orderModal.partName = po.spare_part ? po.spare_part.name : 'Component';
        this.orderModal.supplierId = po.supplier_id || '';
        this.orderModal.unitCost = po.unit_cost || '';
        this.orderModal.show = true;
    }
}">

    {{-- Top Executive KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Active Orders</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $stats['active_count'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Pending dispatch or delivery</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Delivered & Stocked</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-emerald-700 tracking-tight">{{ $stats['received_count'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Completed replenishments</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Replenishment Spend</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($stats['total_spend'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Total received order value</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Registered Vendors</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="truck" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $suppliers->count() }}</p>
            <a href="/admin/suppliers" class="text-xs text-purple-700 hover:text-purple-800 font-semibold mt-1 inline-flex items-center gap-1">
                Manage Suppliers &rarr;
            </a>
        </div>
    </div>

    {{-- Header with Tab Navigation & Create PO Button --}}
    <div class="flex justify-between items-center mb-5 flex-wrap gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Procurement & Purchase Orders</h2>
            <p class="text-sm text-gray-500 mt-0.5">Manage automated inventory replenishment, dispatch supplier orders, and generate goods receipt invoices.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" @click="showAddModal = true"
                class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-xs transition">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Create Manual PO
            </button>
        </div>
    </div>

    {{-- Two Distinct Tables / Tabs: Active Orders vs Completed History --}}
    <div class="flex items-center gap-2 mb-4 border-b border-gray-200 pb-2">
        <button type="button" @click="activeTab = 'active'"
            :class="activeTab === 'active' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-gray-50'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
            <span>Active Orders</span>
            <span class="text-[10px] px-2 py-0.5 rounded-full"
                :class="activeTab === 'active' ? 'bg-slate-800 text-white' : 'bg-gray-100 text-gray-700'">
                {{ $activeOrders->count() }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'completed'"
            :class="activeTab === 'completed' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-gray-50'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
            <span>Completed Orders & Receipt History</span>
            <span class="text-[10px] px-2 py-0.5 rounded-full"
                :class="activeTab === 'completed' ? 'bg-slate-800 text-white' : 'bg-gray-100 text-gray-700'">
                {{ $completedOrders->count() }}
            </span>
        </button>
    </div>

    {{-- TAB 1: ACTIVE PURCHASE ORDERS (Draft & Ordered) --}}
    <div x-show="activeTab === 'active'" class="space-y-4">
        {{-- Automated Low-Stock Batch Replenishment Card --}}
        @php
            $draftOrders = $activeOrders->where('status', 'draft');
        @endphp
        @if($draftOrders->count() > 0)
        <div class="p-4 bg-gradient-to-r from-slate-900 to-slate-800 rounded-xl text-white shadow-xs flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center shrink-0 text-amber-400">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-bold tracking-tight">Auto-Replenishment: {{ $draftOrders->count() }} Draft Requisition(s) Ready</h4>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-amber-400/20 text-amber-300 border border-amber-400/30">Action Required</span>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">Low-stock spare parts were identified and prepared for order. Bulk dispatch them directly to assigned vendors.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.purchase-orders.batch-order') }}" class="flex items-center gap-2 flex-wrap"
                onsubmit="return confirmSubmit(event, {title:'Dispatch All Draft POs?', message:'This will mark all {{ $draftOrders->count() }} draft purchase orders as Ordered and dispatch to suppliers.', confirmLabel:'Dispatch All Drafts'})">
                @csrf
                @if($suppliers->count() > 0)
                <select name="supplier_id" class="text-xs bg-slate-800 border border-slate-700 text-white rounded-lg px-3 py-2 focus:ring-1 focus:ring-blue-400">
                    <option value="">Default Vendor</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
                @endif
                <button type="submit"
                    class="bg-white hover:bg-slate-100 text-slate-900 px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Quick Order All Drafts ({{ $draftOrders->count() }})</span>
                </button>
            </form>
        </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
                <div>
                    <h3 class="text-base font-bold text-gray-900 tracking-tight">Active Requisitions Awaiting Fulfillment</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Orders in Draft status or waiting for supplier delivery. Completing an order automatically moves it to History.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                    {{ $activeOrders->count() }} pending action
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3.5">PO Ref & Component</th>
                            <th class="px-5 py-3.5">Quantity</th>
                            <th class="px-5 py-3.5">Assigned Vendor</th>
                            <th class="px-5 py-3.5">Estimated Unit Cost</th>
                            <th class="px-5 py-3.5">Requisition Stage</th>
                            <th class="px-5 py-3.5">Source</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($activeOrders as $po)
                        @php
                            $isLow = $po->sparePart && ($po->sparePart->stock <= $po->sparePart->min_stock);
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-gray-800 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                        {{ $po->po_number }}
                                    </span>
                                    <p class="font-bold text-sm text-gray-900">{{ $po->sparePart->name ?? '—' }}</p>
                                </div>
                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span>Stock:</span>
                                    {{-- FIXED: Accurate color coding based on actual threshold --}}
                                    <strong class="{{ $isLow ? 'text-rose-600 font-bold' : 'text-emerald-700 font-bold' }}">
                                        {{ $po->sparePart->stock ?? '—' }}
                                    </strong>
                                    <span>/ min {{ $po->sparePart->min_stock ?? '—' }}</span>
                                    @if(!$isLow && $po->sparePart)
                                        <span class="text-[10px] text-emerald-800 font-bold bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded">Sufficient</span>
                                    @elseif($isLow)
                                        <span class="text-[10px] text-rose-800 font-bold bg-rose-50 border border-rose-200 px-1.5 py-0.2 rounded">Low Stock</span>
                                    @endif
                                    @if($po->sparePart && $po->sparePart->part_number)
                                        <span class="text-gray-400">&bull;</span>
                                        <span class="font-mono text-gray-600">{{ $po->sparePart->part_number }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 font-bold text-sm text-gray-900">{{ $po->quantity }} units</td>
                            <td class="px-5 py-4 text-xs font-semibold text-gray-800">
                                @if($po->supplier)
                                    <span class="text-gray-900 font-bold">{{ $po->supplier->name }}</span>
                                @else
                                    <span class="text-amber-700 italic">Unassigned Vendor</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs font-bold text-gray-900">
                                {{ $po->unit_cost ? 'RM '.number_format($po->unit_cost, 2) : 'Pending Quote' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                                    {{ $po->status === 'ordered' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $po->status === 'ordered' ? 'bg-blue-500' : 'bg-amber-500' }}"></span>
                                    {{ ucfirst($po->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($po->auto_generated)
                                    <span class="text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded">Auto</span>
                                @else
                                    <span class="text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200 px-2 py-0.5 rounded">Manual</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($po->status === 'draft')
                                    @if($po->supplier_id)
                                    <form method="POST" action="/admin/purchase-orders/{{ $po->id }}/order" class="inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="supplier_id" value="{{ $po->supplier_id }}">
                                        <input type="hidden" name="unit_cost" value="{{ $po->unit_cost ?? $po->sparePart?->unit_price ?? 0 }}">
                                        <button type="submit"
                                            class="text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white px-2.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1">
                                            <i data-lucide="send" class="w-3 h-3"></i> Quick Order
                                        </button>
                                    </form>
                                    @endif
                                    <button type="button" @click="openOrderModal({
                                            id: {{ $po->id }},
                                            spare_part: { name: '{{ addslashes($po->sparePart->name ?? '') }}' },
                                            supplier_id: '{{ $po->supplier_id }}',
                                            unit_cost: '{{ $po->unit_cost }}'
                                        })"
                                        class="text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                                        <span>{{ $po->supplier_id ? 'Edit & Order' : 'Assign Vendor & Order' }}</span>
                                    </button>
                                    <form method="POST" action="/admin/purchase-orders/{{ $po->id }}/cancel" class="inline"
                                        onsubmit="return confirmSubmit(event, {title:'Cancel requisition?', message:'Cancel this purchase order?', confirmLabel:'Cancel PO'})">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="text-xs font-semibold bg-white border border-gray-200 text-rose-600 hover:bg-rose-50 px-2.5 py-1.5 rounded-lg shadow-xs transition">
                                            Cancel
                                        </button>
                                    </form>
                                    @elseif($po->status === 'ordered')
                                    <form method="POST" action="/admin/purchase-orders/{{ $po->id }}/receive" class="inline"
                                        onsubmit="return confirmSubmit(event, {title:'Receive Shipment?', message:'Verify that {{ $po->quantity }} units have been delivered to store. This will automatically update warehouse stock and move this order to Completed History with a printable invoice.', confirmLabel:'Confirm Receipt'})">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                            <i data-lucide="package-check" class="w-3.5 h-3.5"></i>
                                            <span>Mark Received</span>
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/purchase-orders/{{ $po->id }}/cancel" class="inline"
                                        onsubmit="return confirmSubmit(event, {title:'Cancel ordered PO?', message:'Void this placed purchase order?', confirmLabel:'Void Order'})">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="text-xs font-semibold bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 px-2.5 py-1.5 rounded-lg shadow-xs transition">
                                            Cancel
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <x-empty-state icon="package-check" title="No active purchase orders" subtitle="All parts are fully stocked, or completed orders have moved to the History tab." />
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: COMPLETED ORDERS & RECEIPT HISTORY --}}
    <div x-show="activeTab === 'completed'" class="space-y-4" style="display:none">
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
                <div>
                    <h3 class="text-base font-bold text-gray-900 tracking-tight">Completed Orders & Goods Receipt History</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Archived records of delivered components, supplier invoices, and voided purchase orders.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                    {{ $completedOrders->count() }} records
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3.5">PO Ref & Component</th>
                            <th class="px-5 py-3.5">Quantity</th>
                            <th class="px-5 py-3.5">Delivered Vendor</th>
                            <th class="px-5 py-3.5">Unit Price</th>
                            <th class="px-5 py-3.5">Total Value (RM)</th>
                            <th class="px-5 py-3.5">Receipt Date</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Invoices & Receipts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($completedOrders as $po)
                        @php
                            $isLow = $po->sparePart && ($po->sparePart->stock <= $po->sparePart->min_stock);
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition {{ $po->status === 'cancelled' ? 'opacity-60 bg-gray-50/40' : '' }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-gray-800 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                        {{ $po->po_number }}
                                    </span>
                                    <p class="font-bold text-sm text-gray-900">{{ $po->sparePart->name ?? '—' }}</p>
                                </div>
                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span>Current Stock:</span>
                                    <strong class="{{ $isLow ? 'text-rose-600 font-bold' : 'text-emerald-700 font-bold' }}">
                                        {{ $po->sparePart->stock ?? '—' }}
                                    </strong>
                                    <span>/ min {{ $po->sparePart->min_stock ?? '—' }}</span>
                                    @if(!$isLow && $po->sparePart)
                                        <span class="text-[10px] text-emerald-800 font-bold bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded">Sufficient</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 font-bold text-sm text-gray-900">{{ $po->quantity }} units</td>
                            <td class="px-5 py-4 text-xs font-semibold text-gray-800">
                                {{ $po->supplier->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-xs font-medium text-gray-600">
                                {{ $po->unit_cost ? 'RM '.number_format($po->unit_cost, 2) : '—' }}
                            </td>
                            <td class="px-5 py-4 font-bold text-sm text-gray-900">
                                RM {{ number_format($po->total_cost, 2) }}
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-600 font-medium">
                                @if($po->received_at)
                                    <span class="font-semibold text-gray-900">{{ $po->received_at->format('d M Y') }}</span>
                                    <span class="text-gray-400 block text-[11px]">{{ $po->received_at->format('H:i') }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($po->status === 'received')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Received
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border bg-gray-100 text-gray-600 border-gray-200">
                                    Cancelled
                                </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($po->status === 'received')
                                    {{-- INVOICE PRINT BUTTON --}}
                                    <a href="/admin/purchase-orders/{{ $po->id }}/invoice"
                                        class="text-xs font-bold bg-white border border-gray-200 text-gray-800 hover:bg-gray-50 hover:text-blue-600 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                        <i data-lucide="printer" class="w-3.5 h-3.5 text-blue-600"></i>
                                        <span>Print Invoice</span>
                                    </a>
                                    @else
                                    <span class="text-xs text-gray-400 italic">No receipt (Voided)</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center">
                                <x-empty-state icon="archive" title="No completed purchase orders" subtitle="Delivered orders will be moved here with printable supplier invoices." />
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL: ASSIGN VENDOR & SUBMIT ORDER --}}
    <div x-show="orderModal.show" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md p-6 text-left" @click.outside="orderModal.show = false">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-100">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-800 flex items-center justify-center">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 tracking-tight">Dispatch Order to Vendor</h3>
                    <p class="text-xs text-gray-500" x-text="'Component: ' + orderModal.partName"></p>
                </div>
            </div>

            <form :action="'/admin/purchase-orders/' + orderModal.id + '/order'" method="POST" class="space-y-4">
                @csrf @method('PATCH')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Select Supplier *</label>
                    <select name="supplier_id" x-model="orderModal.supplierId" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Choose preferred vendor...</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->phone ?? $s->email ?? 'No contact' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Agreed Unit Cost (RM)</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" x-model="orderModal.unitCost" placeholder="e.g. 45.00"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="orderModal.show = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-800 shadow-xs transition flex items-center gap-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Confirm & Dispatch Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: CREATE MANUAL PURCHASE ORDER --}}
    <div x-show="showAddModal" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg p-6 text-left" @click.outside="showAddModal = false">
            <div class="flex items-center gap-3 mb-5 pb-3 border-b border-gray-100">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-800 flex items-center justify-center">
                    <i data-lucide="package-plus" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 tracking-tight">Create Manual Purchase Requisition</h3>
                    <p class="text-xs text-gray-500">Requisition new stock from suppliers for workshop inventory</p>
                </div>
            </div>

            <form action="/admin/purchase-orders" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Spare Part Item *</label>
                    <select name="spare_part_id" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select spare part...</option>
                        @foreach($spareParts as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (Current: {{ $p->stock }} / Min: {{ $p->min_stock }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Quantity to Order *</label>
                        <input type="number" name="quantity" min="1" value="10" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Unit Cost (RM)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" placeholder="e.g. 50.00" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Vendor / Supplier (Optional)</label>
                    <select name="supplier_id" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="">Assign later or select now...</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAddModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-800 shadow-xs transition flex items-center gap-1.5">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Create Requisition
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
