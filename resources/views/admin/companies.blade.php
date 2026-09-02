@extends('layouts.app')
@section('page-title', 'Companies')

@section('content')

<div x-data="{ showAdd: false }">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">
            Companies
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $companies->count() }} total</span>
        </h2>
        <button @click="showAdd = true"
            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Company
        </button>
    </div>

    {{-- Company List --}}
    <div class="bg-white rounded-lg shadow p-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2 pr-4">Company Name</th>
                    <th class="pb-2 pr-4">Reg No</th>
                    <th class="pb-2 pr-4">Person In Charge</th>
                    <th class="pb-2 pr-4">Phone</th>
                    <th class="pb-2 pr-4">Representatives</th>
                    <th class="pb-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 pr-4 font-medium">{{ $company->name }}</td>
                    <td class="py-3 pr-4 text-gray-500">{{ $company->registration_no ?? '—' }}</td>
                    <td class="py-3 pr-4">{{ $company->person_in_charge ?? '—' }}</td>
                    <td class="py-3 pr-4 text-gray-500">{{ $company->phone ?? '—' }}</td>
                    <td class="py-3 pr-4">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                            {{ $company->representatives_count }} users
                        </span>
                    </td>
                    <td class="py-3">
                        <a href="/admin/companies/{{ $company->id }}"
                            class="text-blue-600 hover:underline text-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-6 text-center text-gray-400">No companies yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Company Modal --}}
    <div x-show="showAdd" x-transition.opacity
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6" @click.outside="showAdd = false">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-semibold text-gray-700">Add Company</h3>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="/admin/companies" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Registration No</label>
                    <input type="text" name="registration_no" class="w-full border rounded px-3 py-2 text-sm" placeholder="e.g. 123456-A">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Person In Charge</label>
                    <input type="text" name="person_in_charge" class="w-full border rounded px-3 py-2 text-sm">
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
                <div class="md:col-span-2 flex gap-3 pt-2">
                    <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        Add Company
                    </button>
                    <button type="button" @click="showAdd = false"
                        class="px-5 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection