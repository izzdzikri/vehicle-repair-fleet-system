@extends('layouts.app')
@section('page-title', 'Companies')

@section('content')

<div x-data="{ showAdd: false }">

    <div class="flex justify-between items-center mb-6 flex-wrap gap-2">
        <div>
            <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Corporate Accounts & Companies</h2>
            <p class="text-xs font-medium text-gray-500 mt-0.5">Manage registered corporate client organizations and fleet contacts</p>
        </div>
        <button @click="showAdd = true"
            class="bg-blue-600 text-white px-4 py-2.5 rounded-lg text-xs font-bold hover:bg-blue-700 flex items-center gap-2 shadow-xs transition">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Corporate Client
        </button>
    </div>

    {{-- Company List --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4">Company Name</th>
                        <th class="py-3 px-4">Registration No</th>
                        <th class="py-3 px-4">Primary Contact / PIC</th>
                        <th class="py-3 px-4">Phone Number</th>
                        <th class="py-3 px-4">Active PICs</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($companies as $company)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="py-3.5 px-4 font-bold text-gray-900">{{ $company->name }}</td>
                        <td class="py-3.5 px-4">
                            <span class="font-mono text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                {{ $company->registration_no ?? '—' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-gray-800 font-medium">{{ $company->person_in_charge ?? '—' }}</td>
                        <td class="py-3.5 px-4 text-xs font-mono text-gray-600">{{ $company->phone ?? '—' }}</td>
                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-xs font-bold">
                                {{ $company->representatives_count }} user(s)
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <a href="/admin/companies/{{ $company->id }}"
                                class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200 transition">
                                View <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i data-lucide="building" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                            <p class="text-sm font-semibold text-gray-600">No companies registered yet</p>
                            <p class="text-xs text-gray-400">Click the button above to register your first corporate account.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Company Modal --}}
    <div x-show="showAdd" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg p-6" @click.outside="showAdd = false">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                        <i data-lucide="building" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Add New Corporate Client</h3>
                </div>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="/admin/companies" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Company Name *</label>
                    <input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="e.g. Acme Logistics Sdn Bhd" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Registration Number</label>
                    <input type="text" name="registration_no" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="e.g. 202101012345">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Person In Charge</label>
                    <input type="text" name="person_in_charge" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="e.g. En. Rahman">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Contact Phone</label>
                    <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="03-12345678">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Billing Email</label>
                    <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="billing@acme.com">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Registered Office Address</label>
                    <input type="text" name="address" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="e.g. Suite 12-A, Menara Acme, KL">
                </div>
                <div class="md:col-span-2 flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAdd = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-xs font-bold shadow-xs transition">
                        Save Company
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
