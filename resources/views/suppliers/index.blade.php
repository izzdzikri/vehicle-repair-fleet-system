@extends('layouts.app')
@section('page-title', 'Suppliers')

@section('content')

@if(session('success'))
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">{{ session('error') }}</div>
@endif

<div x-data="{ showEdit: false, supplier: {}, openEdit(s) { this.supplier = s; this.showEdit = true; } }">

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Add Supplier</h2>
        <form method="POST" action="/admin/suppliers" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                <input type="text" name="name" class="w-full border rounded px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                <input type="text" name="contact_person" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input type="text" name="address" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700">
                    Add Supplier
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-700">
                All Suppliers <span class="text-sm font-normal text-gray-400 ml-1">{{ $suppliers->count() }} total</span>
            </h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-500 border-b">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Purchase Orders</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $s)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $s->name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $s->contact_person ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $s->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $s->email ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">{{ $s->purchase_orders_count }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <button
                            @click="openEdit({
                                id: {{ $s->id }},
                                name: '{{ addslashes($s->name) }}',
                                contact_person: '{{ addslashes($s->contact_person ?? '') }}',
                                phone: '{{ addslashes($s->phone ?? '') }}',
                                email: '{{ addslashes($s->email ?? '') }}',
                                address: '{{ addslashes($s->address ?? '') }}'
                            })"
                            class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded hover:bg-yellow-200 mr-1">
                            Edit
                        </button>
                        <form method="POST" action="/admin/suppliers/{{ $s->id }}" class="inline"
                            onsubmit="return confirm('Delete {{ addslashes($s->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-8 text-center text-gray-400">No suppliers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEdit" x-transition.opacity
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6" @click.outside="showEdit = false">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-semibold text-gray-700">Edit Supplier</h3>
                <button @click="showEdit = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" :action="'/admin/suppliers/' + supplier.id">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                        <input type="text" name="name" :value="supplier.name"
                            class="w-full border rounded px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                        <input type="text" name="contact_person" :value="supplier.contact_person"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" :value="supplier.phone"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" :value="supplier.email"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" name="address" :value="supplier.address"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        Save Changes
                    </button>
                    <button type="button" @click="showEdit = false" class="px-5 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection