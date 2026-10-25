@extends('layouts.app')
@section('page-title', 'Suppliers')

@section('content')

<div x-data="{ showAdd: false, showEdit: false, supplier: {}, openEdit(s) { this.supplier = s; this.showEdit = true; } }">

    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Parts Vendors & Suppliers</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                    {{ $suppliers->count() }} registered suppliers
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Vendor directory, contact persons, and purchase order records</p>
        </div>
        <button @click="showAdd = true"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add New Supplier
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <th class="px-5 py-3.5">Company / Vendor</th>
                    <th class="px-5 py-3.5">Contact Person</th>
                    <th class="px-5 py-3.5">Phone Number</th>
                    <th class="px-5 py-3.5">Email Address</th>
                    <th class="px-5 py-3.5">Purchase Orders</th>
                    <th class="px-5 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($suppliers as $s)
                <tr class="hover:bg-gray-50/60 transition">
                    <td class="px-5 py-4 font-bold text-sm text-gray-900">{{ $s->name }}</td>
                    <td class="px-5 py-4 text-sm font-medium text-gray-700">{{ $s->contact_person ?? '—' }}</td>
                    <td class="px-5 py-4 text-xs font-medium text-gray-600 font-mono">{{ $s->phone ?? '—' }}</td>
                    <td class="px-5 py-4 text-xs font-medium text-gray-600">{{ $s->email ?? '—' }}</td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-md text-xs font-semibold">
                            {{ $s->purchase_orders_count }} orders
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <button type="button"
                                @click="openEdit({
                                    id: {{ $s->id }},
                                    name: '{{ addslashes($s->name) }}',
                                    contact_person: '{{ addslashes($s->contact_person ?? '') }}',
                                    phone: '{{ addslashes($s->phone ?? '') }}',
                                    email: '{{ addslashes($s->email ?? '') }}',
                                    address: '{{ addslashes($s->address ?? '') }}'
                                })"
                                class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                <i data-lucide="pencil" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span>Edit</span>
                            </button>
                            <form method="POST" action="/admin/suppliers/{{ $s->id }}" class="inline"
                                onsubmit="return confirmSubmit(event, {title:'Delete supplier?', message:'This will permanently delete {{ addslashes($s->name) }}. This cannot be undone.', confirmLabel:'Delete Supplier'})">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="text-xs font-semibold bg-white border border-gray-200 text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center">
                        <x-empty-state icon="truck" title="No suppliers registered" subtitle="Add your first supplier using the button above." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Add Modal --}}
    <div x-show="showAdd" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg p-6" @click.outside="showAdd = false">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-800 flex items-center justify-center">
                        <i data-lucide="truck" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Add Supplier</h3>
                </div>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="/admin/suppliers" class="grid grid-cols-2 gap-4">
                @csrf
                <div class="col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Company Name *</label>
                    <input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Contact Person</label>
                    <input type="text" name="contact_person" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Address</label>
                    <input type="text" name="address" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2 flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAdd = false" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-slate-900 text-white px-5 py-2 rounded-xl hover:bg-slate-800 text-xs font-bold shadow-xs transition">
                        Add Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEdit" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg p-6" @click.outside="showEdit = false">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-800 flex items-center justify-center">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Edit Supplier</h3>
                </div>
                <button @click="showEdit = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" :action="'/admin/suppliers/' + supplier.id">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Company Name *</label>
                        <input type="text" name="name" :value="supplier.name"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Contact Person</label>
                        <input type="text" name="contact_person" :value="supplier.contact_person"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Phone</label>
                        <input type="text" name="phone" :value="supplier.phone"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Email</label>
                        <input type="email" name="email" :value="supplier.email"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Address</label>
                        <input type="text" name="address" :value="supplier.address"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 mt-5 pt-3 border-t border-gray-100">
                    <button type="button" @click="showEdit = false" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-slate-900 text-white px-5 py-2 rounded-xl hover:bg-slate-800 text-xs font-bold shadow-xs transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection