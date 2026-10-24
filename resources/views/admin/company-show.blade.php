@extends('layouts.app')
@section('page-title', 'Company Detail')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <a href="/admin/companies"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Companies
    </a>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-start justify-between mb-6 pb-4 border-b border-gray-100 flex-wrap gap-2">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $company->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Reg No:</span>
                    <span class="font-mono text-xs font-bold bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">
                        {{ $company->registration_no ?? 'Unregistered' }}
                    </span>
                </div>
            </div>
            <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-xs font-bold">
                {{ $company->representatives->count() }} active PIC(s)
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50/70 border border-gray-200/80 rounded-xl p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Primary PIC</p>
                <p class="text-sm font-bold text-gray-900">{{ $company->person_in_charge ?? '—' }}</p>
            </div>
            <div class="bg-gray-50/70 border border-gray-200/80 rounded-xl p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Direct Phone</p>
                <p class="text-sm font-bold text-gray-900 font-mono">{{ $company->phone ?? '—' }}</p>
            </div>
            <div class="bg-gray-50/70 border border-gray-200/80 rounded-xl p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Billing Email</p>
                <p class="text-sm font-bold text-gray-900 font-mono">{{ $company->email ?? '—' }}</p>
            </div>
            <div class="bg-gray-50/70 border border-gray-200/80 rounded-xl p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Physical Address</p>
                <p class="text-sm font-medium text-gray-700">{{ $company->address ?? '—' }}</p>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-bold text-gray-900">Registered Representatives & PICs</h3>
                <span class="text-xs font-semibold text-gray-500">{{ $company->representatives->count() }} total users</span>
            </div>

            @if($company->representatives->count())
            <div class="overflow-x-auto border border-gray-200/80 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/75 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Representative Name</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4 text-right">Account Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($company->representatives as $rep)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 font-bold">
                                <a href="/admin/users/{{ $rep->id }}"
                                    class="text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1">
                                    {{ $rep->name }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-xs font-mono text-gray-600">{{ $rep->email }}</td>
                            <td class="py-3 px-4 text-xs font-mono text-gray-600">{{ $rep->contact_no ?? '—' }}</td>
                            <td class="py-3 px-4 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold
                                    {{ $rep->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ ucfirst($rep->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-gray-400 text-sm py-4 text-center">No corporate representatives linked to this company record yet.</p>
            @endif
        </div>
    </div>

</div>
@endsection
