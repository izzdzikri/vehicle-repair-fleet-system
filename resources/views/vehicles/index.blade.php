@extends('layouts.app')
@section('page-title', 'Vehicles')

@section('content')

@php
    $base = auth()->user()->role === 'admin' ? '/admin' :
           (auth()->user()->role === 'corporate' ? '/client' : '/customer');
    $isAdmin = auth()->user()->role === 'admin';
@endphp

<div x-data="{ ownershipFilter: 'all' }">

<div class="flex justify-between items-center mb-6 gap-4 flex-wrap">
    <div>
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Fleet & Client Vehicles</h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                {{ $vehicles->total() }} registered vehicles
            </span>
        </div>
        <p class="text-sm text-gray-500 mt-1">Vehicle registrations, ownership categories (Corporate Fleet vs Individual), odometer telemetry, and service logs</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <form method="GET" action="{{ $base }}/vehicles" class="flex gap-2">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search plate, brand, owner, fleet..."
                    class="pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm w-64 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
            </div>
            <button type="submit"
                class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
                Search
            </button>
            @if($search)
            <a href="{{ $base }}/vehicles" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 flex items-center">Clear</a>
            @endif
        </form>
        <a href="{{ $base }}/vehicles/create"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Vehicle
        </a>
    </div>
</div>

{{-- Ownership Category Filter Tabs --}}
<div class="flex items-center gap-2 mb-4">
    <button type="button" @click="ownershipFilter = 'all'"
        :class="ownershipFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
        <span>All Vehicles</span>
    </button>
    <button type="button" @click="ownershipFilter = 'fleet'"
        :class="ownershipFilter === 'fleet' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-amber-50 hover:text-amber-700'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
        <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
        <span>Corporate Fleets</span>
    </button>
    <button type="button" @click="ownershipFilter = 'individual'"
        :class="ownershipFilter === 'individual' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-gray-200 hover:bg-emerald-50 hover:text-emerald-700'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
        <i data-lucide="user" class="w-3.5 h-3.5"></i>
        <span>Individual Customers</span>
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                <th class="px-5 py-3.5">Plate Number</th>
                <th class="px-5 py-3.5">Make & Model</th>
                <th class="px-5 py-3.5">Year</th>
                <th class="px-5 py-3.5">Mileage</th>
                <th class="px-5 py-3.5">Ownership (Fleet / Individual)</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($vehicles as $v)
            @php
                $isFleet = $v->isFleet();
                $ownershipType = $isFleet ? 'fleet' : 'individual';
            @endphp
            <tr class="hover:bg-gray-50/60 transition"
                x-show="ownershipFilter === 'all' || ownershipFilter === '{{ $ownershipType }}'">
                <td class="px-5 py-4">
                    <a href="{{ $base }}/vehicles/{{ $v->id }}"
                        class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2.5 py-1 rounded border border-gray-200 hover:bg-blue-50 hover:text-blue-700 transition inline-block">
                        {{ $v->plate_number }}
                    </a>
                </td>
                <td class="px-5 py-4">
                    <p class="font-bold text-sm text-gray-900">{{ $v->brand }} {{ $v->model }}</p>
                </td>
                <td class="px-5 py-4 text-xs font-medium text-gray-600">{{ $v->year }}</td>
                <td class="px-5 py-4 text-sm font-bold text-gray-900">{{ number_format($v->mileage) }} km</td>
                <td class="px-5 py-4">
                    @if($isFleet)
                    <div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 uppercase tracking-wider inline-flex items-center gap-1">
                                <i data-lucide="building-2" class="w-3 h-3 text-amber-600"></i> Fleet
                            </span>
                            <span class="text-sm font-bold text-gray-900">{{ $v->company->name ?? ($v->owner->company->name ?? 'Corporate Account') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">PIC: <span class="font-medium text-gray-700">{{ $v->owner->name ?? '—' }}</span></p>
                    </div>
                    @else
                    <div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase tracking-wider inline-flex items-center gap-1">
                                <i data-lucide="user" class="w-3 h-3 text-emerald-600"></i> Individual
                            </span>
                            <span class="text-sm font-bold text-gray-900">{{ $v->owner->name ?? 'Unassigned' }}</span>
                        </div>
                        @if($v->owner && $v->owner->contact_no)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $v->owner->contact_no }}</p>
                        @endif
                    </div>
                    @endif
                </td>
                <td class="px-5 py-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        <a href="{{ $base }}/vehicles/{{ $v->id }}"
                            class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition">
                            View
                        </a>
                        <a href="{{ $base }}/vehicles/{{ $v->id }}/edit"
                            class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition">
                            Edit
                        </a>
                        <form method="POST" action="{{ $base }}/vehicles/{{ $v->id }}"
                            class="inline"
                            onsubmit="return confirmSubmit(event, {title:'Delete vehicle?', message:'This will permanently delete {{ addslashes($v->plate_number) }} ({{ addslashes($v->brand) }} {{ addslashes($v->model) }}) and its service history. This cannot be undone.', confirmLabel:'Delete Vehicle'})">
                            @csrf @method('DELETE')
                            <button class="text-xs font-semibold bg-white border border-gray-200 text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg shadow-xs transition">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-12 text-center">
                    <x-empty-state icon="car" title="No vehicles registered yet" subtitle="Add your first vehicle using the Add Vehicle button above." />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($vehicles->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $vehicles->links() }}
    </div>
    @endif
</div>

</div>
@endsection