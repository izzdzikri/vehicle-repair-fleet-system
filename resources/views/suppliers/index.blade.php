@extends('layouts.app')
@section('page-title', 'Suppliers')

@section('content')

@if(session('success'))
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">{{ session('success') }}</div>
@endif

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
            </tr>
            @empty
            <tr><td colspan="5" class="py-8 text-center text-gray-400">No suppliers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection