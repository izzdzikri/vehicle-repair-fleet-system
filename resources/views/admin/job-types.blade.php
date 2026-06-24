@extends('layouts.app')
@section('page-title', 'Job Types')

@section('content')

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
    class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">
    {{ session('success') }}
</div>
@endif

<div x-data="{
    showEdit: false,
    jt: {},
    openEdit(j) {
        this.jt = j;
        this.showEdit = true;
    }
}">

    {{-- Add Form --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Add Job Type</h2>
        <form method="POST" action="/admin/job-types"
            class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                <textarea name="description" rows="2"
                    class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="Brief description of this service...">{{ old('description') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                    Add Job Type
                </button>
            </div>
        </form>
    </div>

    {{-- Job Types Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-700">
                All Job Types
                <span class="text-sm font-normal text-gray-400 ml-2">{{ $jobTypes->count() }} total</span>
            </h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-500 border-b">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Est. Time</th>
                    <th class="px-4 py-3">Base Price</th>
                    <th class="px-4 py-3">Action</th>
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
                        <button
                            @click="openEdit({
                                id:                 {{ $jt->id }},
                                name:               '{{ addslashes($jt->name) }}',
                                category:           '{{ $jt->category }}',
                                estimated_minutes:  '{{ $jt->estimated_minutes }}',
                                base_price:         '{{ $jt->base_price }}',
                                description:        '{{ addslashes($jt->description ?? '') }}'
                            })"
                            class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded hover:bg-yellow-200 mr-1">
                            Edit
                        </button>
                        <form method="POST" action="/admin/job-types/{{ $jt->id }}" class="inline"
                            onsubmit="return confirm('Delete {{ addslashes($jt->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-gray-400">No job types found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
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

            <form method="POST" :action="'/admin/job-types/' + jt.id">
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