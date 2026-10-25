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

    <div class="flex justify-between items-center mb-5 flex-wrap gap-2">
        <div>
            <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Standard Job Types & Labor Rates</h2>
            <p class="text-xs font-medium text-gray-500 mt-0.5">Define service catalog, labor baseline prices, and predictive maintenance trigger cycles</p>
        </div>
        <button @click="showAdd = true"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-lg text-xs font-bold hover:bg-slate-800 flex items-center gap-2 shadow-xs transition">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Job Type
        </button>
    </div>

    <div class="mb-5 p-4 bg-blue-50/70 border border-blue-200/80 rounded-xl text-xs text-blue-900 flex items-start gap-3">
        <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0 mt-0.5"></i>
        <div class="space-y-0.5 leading-relaxed">
            <span class="font-bold">Predictive Maintenance Intervals:</span>
            Specify a target kilometer threshold or month interval on scheduled services (e.g., Engine Oil Change, Spark Plugs) to automatically generate predictive alerts on fleet and customer dashboards when vehicles become due.
        </div>
    </div>

    {{-- Job Types Table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4">Service Name</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Estimated Duration</th>
                        <th class="py-3 px-4">Base Labor Rate</th>
                        <th class="py-3 px-4">Predictive Schedule</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($jobTypes as $jt)
                    @php
                        $mins  = $jt->estimated_minutes;
                        $hours = $mins >= 60
                            ? floor($mins/60).'h '.($mins%60 ? ($mins%60).'m' : '')
                            : $mins.'m';
                    @endphp
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="py-3.5 px-4 font-bold text-gray-900">{{ $jt->name }}</td>
                        <td class="py-3.5 px-4">
                            <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full font-bold">
                                {{ $jt->category }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs font-medium text-gray-600 font-mono">{{ $hours }}</td>
                        <td class="py-3.5 px-4 font-bold text-gray-900">RM {{ number_format($jt->base_price, 2) }}</td>
                        <td class="py-3.5 px-4">
                            @if($jt->isIntervalTracked())
                            <span class="text-xs bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-0.5 rounded-full font-bold inline-flex items-center gap-1">
                                <i data-lucide="sparkles" class="w-3 h-3 text-purple-600"></i> {{ $jt->interval_label }}
                            </span>
                            @else
                            <span class="text-xs text-gray-400 font-medium">Wear-triggered only</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button"
                                    @click="openEdit({
                                        id:                 {{ $jt->id }},
                                        name:               '{{ addslashes($jt->name) }}',
                                        category:           '{{ $jt->category }}',
                                        estimated_minutes:  '{{ $jt->estimated_minutes }}',
                                        base_price:         '{{ $jt->base_price }}',
                                        description:        '{{ addslashes($jt->description ?? '') }}',
                                        interval_km:        '{{ $jt->interval_km }}',
                                        interval_months:    '{{ $jt->interval_months }}'
                                    })"
                                    class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5 text-gray-400"></i> Edit
                                </button>
                                <form method="POST" action="/pricing/job-types/{{ $jt->id }}" class="inline"
                                    onsubmit="return confirmSubmit(event, {title:'Delete job type?', message:'This will permanently delete {{ addslashes($jt->name) }}. Existing job cards referencing it will keep their history, but it will no longer be selectable.', confirmLabel:'Delete Job Type'})">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-xs font-semibold bg-white border border-gray-200 text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i data-lucide="wrench" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                            <p class="text-sm font-semibold text-gray-600">No job types configured yet</p>
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
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                        <i data-lucide="wrench" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Add Service Job Type</h3>
                </div>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="/pricing/job-types" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Job Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="e.g. Engine Oil Flush & Filter"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Category *</label>
                    <select name="category"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                        required>
                        <option value="Maintenance">Maintenance</option>
                        @foreach(['Engine','Brakes','Tyres','Electrical','Cooling','Transmission','Suspension','Steering','General','Diagnostics'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Estimated Duration (mins) *</label>
                    <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes', 60) }}" min="1"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Base Labor Price (RM) *</label>
                    <input type="number" name="base_price" value="{{ old('base_price', 0) }}" min="0" step="0.50"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Interval — Kilometers
                    </label>
                    <input type="number" name="interval_km" value="{{ old('interval_km') }}" min="1"
                        placeholder="e.g. 5000 or 10000"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Interval — Months
                    </label>
                    <input type="number" name="interval_months" value="{{ old('interval_months') }}" min="1"
                        placeholder="e.g. 6 or 12"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Description (Optional)</label>
                    <textarea name="description" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        placeholder="Scope of work, standard checklist items, or warranty..."></textarea>
                </div>
                <div class="md:col-span-2 flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAdd = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-slate-900 text-white px-5 py-2 rounded-lg hover:bg-slate-800 text-xs font-bold shadow-xs transition">
                        Create Job Type
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEdit"
        x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg p-6" @click.outside="showEdit = false">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Edit Job Type</h3>
                </div>
                <button @click="showEdit = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/pricing/job-types/' + jt.id">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Job Name *</label>
                        <input type="text" name="name" :value="jt.name"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Category *</label>
                        <select name="category"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white"
                            required>
                            @foreach(['Engine','Brakes','Tyres','Electrical','Cooling','Transmission','Suspension','Steering','General','Diagnostics','Maintenance'] as $cat)
                            <option value="{{ $cat }}" :selected="jt.category === '{{ $cat }}'">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Est. Time (minutes) *</label>
                        <input type="number" name="estimated_minutes" :value="jt.estimated_minutes" min="1"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Base Price (RM) *</label>
                        <input type="number" name="base_price" :value="jt.base_price" min="0" step="0.50"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Interval — Kilometers
                        </label>
                        <input type="number" name="interval_km" :value="jt.interval_km" min="1"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Interval — Months
                        </label>
                        <input type="number" name="interval_months" :value="jt.interval_months" min="1"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Description</label>
                        <textarea name="description" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            x-text="jt.description"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 mt-5 pt-3 border-t border-gray-100">
                    <button type="button" @click="showEdit = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-slate-900 text-white px-5 py-2 rounded-lg hover:bg-slate-800 text-xs font-bold shadow-xs transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
