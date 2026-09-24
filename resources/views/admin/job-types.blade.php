@extends('layouts.app')
@section('page-title', 'Job Types & Pricing')

@section('content')

<div x-data="{
    showAdd: false,
    showEdit: false,
    jt: {},
    openEdit(j) {
        this.jt = j;
        this.showEdit = true;
    }
}">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-700">
            Job Types & Pricing
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $jobTypes->count() }} total</span>
        </h2>
        <button @click="showAdd = true"
            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Job Type
        </button>
    </div>

    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
        ℹ Set a km and/or month interval on a schedule-based service (e.g. Oil Change) to have it automatically
        predicted on each vehicle's Maintenance Alerts once it's due — see the "Run Predictions Now" button on that page.
        Leave both blank for wear/fault-triggered services (e.g. Brake Pad Replacement) that aren't on a fixed schedule.
    </div>

    {{-- Job Types Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-500 border-b">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Est. Time</th>
                    <th class="px-4 py-3">Base Price</th>
                    <th class="px-4 py-3">Predictive Interval</th>
                    <th class="px-4 py-3 w-16">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobTypes as $jt)
                @php
                    $mins  = $jt->estimated_minutes;
                    $hours = $mins >= 60
                        ? floor($mins/60).'h '.($mins%60 ? ($mins%60).'m' : '')
                        : $mins.'m';
                @endphp
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $jt->name }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                            {{ $jt->category }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $hours }}</td>
                    <td class="px-4 py-3 font-medium text-gray-700">RM {{ number_format($jt->base_price, 2) }}</td>
                    <td class="px-4 py-3">
                        @if($jt->isIntervalTracked())
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                            🔮 {{ $jt->interval_label }}
                        </span>
                        @else
                        <span class="text-xs text-gray-400">Not tracked</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" class="p-1.5 rounded hover:bg-gray-100 text-gray-500">
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                            <div x-show="open" x-transition
                                class="absolute right-0 mt-1 w-36 bg-white rounded-lg shadow-lg border py-1 z-20"
                                style="display:none">
                                <button type="button"
                                    @click="open = false; openEdit({
                                        id:                 {{ $jt->id }},
                                        name:               '{{ addslashes($jt->name) }}',
                                        category:           '{{ $jt->category }}',
                                        estimated_minutes:  '{{ $jt->estimated_minutes }}',
                                        base_price:         '{{ $jt->base_price }}',
                                        description:        '{{ addslashes($jt->description ?? '') }}',
                                        interval_km:        '{{ $jt->interval_km }}',
                                        interval_months:    '{{ $jt->interval_months }}'
                                    })"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-yellow-700 hover:bg-yellow-50">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                                </button>
                                <form method="POST" action="/pricing/job-types/{{ $jt->id }}"
                                    onsubmit="return confirmSubmit(event, {title:'Delete job type?', message:'This will permanently delete {{ addslashes($jt->name) }}. Existing job cards referencing it will keep their history, but it will no longer be selectable.', confirmLabel:'Delete Job Type'})">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-t">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-400">No job types found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Modal --}}
    <div x-show="showAdd" x-transition.opacity
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6" @click.outside="showAdd = false">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-semibold text-gray-700">Add Job Type</h3>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="/pricing/job-types" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="e.g. Oil Change"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="category"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                        <option value="Maintenance">Maintenance</option>
                        @foreach(['Engine','Brakes','Tyres','Electrical','Cooling','Transmission','Suspension','Steering','General','Diagnostics'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Time (minutes) *</label>
                    <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes', 60) }}" min="1"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Price (RM) *</label>
                    <input type="number" name="base_price" value="{{ old('base_price', 0) }}" min="0" step="0.50"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Predictive Interval — km <span class="font-normal text-gray-400">(optional)</span>
                    </label>
                    <input type="number" name="interval_km" value="{{ old('interval_km') }}" min="1"
                        placeholder="e.g. 5000"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Predictive Interval — months <span class="font-normal text-gray-400">(optional)</span>
                    </label>
                    <input type="number" name="interval_months" value="{{ old('interval_months') }}" min="1"
                        placeholder="e.g. 6"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <textarea name="description" rows="2"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        placeholder="Brief description of this service...">{{ old('description') }}</textarea>
                </div>
                <div class="md:col-span-2 flex gap-3 pt-2">
                    <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        Add Job Type
                    </button>
                    <button type="button" @click="showAdd = false"
                        class="px-5 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEdit"
        x-transition.opacity
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        style="display:none">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6" @click.outside="showEdit = false">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-semibold text-gray-700">Edit Job Type</h3>
                <button @click="showEdit = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/pricing/job-types/' + jt.id">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Job Name *</label>
                        <input type="text" name="name" :value="jt.name"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select name="category"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                            @foreach(['Engine','Brakes','Tyres','Electrical','Cooling','Transmission','Suspension','Steering','General','Diagnostics','Maintenance'] as $cat)
                            <option value="{{ $cat }}" :selected="jt.category === '{{ $cat }}'">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Est. Time (minutes) *</label>
                        <input type="number" name="estimated_minutes" :value="jt.estimated_minutes" min="1"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Base Price (RM) *</label>
                        <input type="number" name="base_price" :value="jt.base_price" min="0" step="0.50"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Interval — km <span class="font-normal text-gray-400">(optional)</span>
                        </label>
                        <input type="number" name="interval_km" :value="jt.interval_km" min="1"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Interval — months <span class="font-normal text-gray-400">(optional)</span>
                        </label>
                        <input type="number" name="interval_months" :value="jt.interval_months" min="1"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="2"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            x-text="jt.description"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        Save Changes
                    </button>
                    <button type="button" @click="showEdit = false"
                        class="px-5 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>{{-- end x-data wrapper --}}

@endsection