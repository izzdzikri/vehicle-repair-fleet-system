@extends('layouts.app')
@section('page-title', 'Company Detail')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <a href="/admin/companies" class="text-sm text-blue-600 hover:underline">← Back to Companies</a>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-1">{{ $company->name }}</h2>
        <p class="text-sm text-gray-500 mb-6">{{ $company->registration_no ?? 'No registration number' }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 rounded p-4">
                <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Person In Charge</p>
                <p class="text-sm font-medium">{{ $company->person_in_charge ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded p-4">
                <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Phone</p>
                <p class="text-sm font-medium">{{ $company->phone ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded p-4">
                <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Email</p>
                <p class="text-sm font-medium">{{ $company->email ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded p-4">
                <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Address</p>
                <p class="text-sm font-medium">{{ $company->address ?? '—' }}</p>
            </div>
        </div>

        <h3 class="text-lg font-semibold text-gray-700 mb-3">
            Representatives
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $company->representatives->count() }} total</span>
        </h3>

        @if($company->representatives->count())
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2 pr-4">Name</th>
                    <th class="pb-2 pr-4">Email</th>
                    <th class="pb-2 pr-4">Contact</th>
                    <th class="pb-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($company->representatives as $rep)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 pr-4">
                        <a href="/admin/users/{{ $rep->id }}"
                            class="font-medium text-blue-600 hover:underline">{{ $rep->name }}</a>
                    </td>
                    <td class="py-2 pr-4 text-gray-500">{{ $rep->email }}</td>
                    <td class="py-2 pr-4 text-gray-500">{{ $rep->contact_no ?? '—' }}</td>
                    <td class="py-2">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $rep->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($rep->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-gray-400 text-sm">No representatives linked to this company yet.</p>
        @endif
    </div>

</div>
@endsection