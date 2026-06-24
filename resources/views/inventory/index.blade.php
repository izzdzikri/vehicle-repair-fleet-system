@extends('layouts.app')
@section('page-title', 'Inventory')

@section('content')

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
    class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">{{ session('error') }}</div>
@endif

<div x-data="{
    showAdd: false,
    showEdit: false,
    part: {},
    openEdit(p) {
        this.part = p;
        this.showEdit = true;
    }
}">

    {{-- Add Part Form (admin only) --}}
    @if(auth()->user()->role === 'admin')
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <button @click="showAdd = !showAdd"
            class="flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span x-text="showAdd ? 'Cancel' : 'Add New Part'"></span>
        </button>

        <div x-show="showAdd" x-transition class="mt-4">
            <form method="POST" action="/admin/spare-parts"
                class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Part Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="e.g. Engine Oil 5W-30"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand') }}"
                        placeholder="e.g. Castrol"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Part Number *</label>
                    <input type="text" name="part_number" value="{{ old('part_number') }}"
                        placeholder="e.g. CO-5W30-4L"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="category"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                        <option value="">Select</option>
                        @foreach(['Lubricants','Filters','Brakes','Engine','Electrical','Cooling','Transmission','Suspension','Steering','Tyres','Accessories'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (RM) *</label>
                    <input type="number" name="unit_price" value="{{ old('unit_price') }}"
                        step="0.01" min="0" placeholder="0.00"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                        <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Stock *</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', 5) }}" min="1"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        Add Part
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Parts Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-700">
                Spare Parts
                <span class="text-sm font-normal text-gray-400 ml-2">{{ $parts->count() }} items</span>
            </h2>
            @php $lowCount = $parts->filter(fn($p) => $p->stock <= $p->min_stock)->count(); @endphp
            @if($lowCount)
            <span class="text-xs bg-red-100 text-red-600 px-3 py-1 rounded-full font-medium">
                ⚠ {{ $lowCount }} low stock
            </span>
            @endif
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-500 border-b">
                    <th class="px-4 py-3">Part</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Part No.</th>
                    <th class="px-4 py-3">Unit Price</th>
                    <th class="px-4 py-3">Stock</th>
                    @if(auth()->user()->role === 'admin')
                    <th class="px-4 py-3">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($parts as $part)
                @php $low = $part->stock <= $part->min_stock; @endphp
                <tr class="border-b hover:bg-gray-50 {{ $low ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $part->name }}</p>
                        @if($part->brand)
                        <p class="text-xs text-gray-400">{{ $part->brand }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                            {{ $part->category }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $part->part_number }}</td>
                    <td class="px-4 py-3 font-medium">RM {{ number_format($part->unit_price, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="font-bold {{ $low ? 'text-red-600' : 'text-gray-700' }}">
                            {{ $part->stock }}
                        </span>
                        <span class="text-xs text-gray-400">/ min {{ $part->min_stock }}</span>
                        @if($low)
                        <span class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">Low</span>
                        @endif
                    </td>
                    @if(auth()->user()->role === 'admin')
                    <td class="px-4 py-3">
                        <button
                            @click="openEdit({
                                id:           {{ $part->id }},
                                name:         '{{ addslashes($part->name) }}',
                                brand:        '{{ addslashes($part->brand ?? '') }}',
                                part_number:  '{{ addslashes($part->part_number) }}',
                                category:     '{{ $part->category }}',
                                unit_price:   '{{ $part->unit_price }}',
                                stock:        '{{ $part->stock }}',
                                min_stock:    '{{ $part->min_stock }}'
                            })"
                            class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded hover:bg-yellow-200 mr-1">
                            Edit
                        </button>
                        <form method="POST" action="/admin/spare-parts/{{ $part->id }}" class="inline"
                            onsubmit="return confirm('Delete {{ addslashes($part->name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">
                                Delete
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-400">No parts found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div></div>

    {{-- Edit Modal --}}
    @if(auth()->user()->role === 'admin')
    <div x-show="showEdit"
        x-transition.opacity
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        style="display:none">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6" @click.outside="showEdit = false">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-semibold text-gray-700">Edit Part</h3>
                <button @click="showEdit = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/admin/spare-parts/' + part.id">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Part Name *</label>
                        <input type="text" name="name" :value="part.name"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                        <input type="text" name="brand" :value="part.brand"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Part Number *</label>
                        <input type="text" name="part_number" :value="part.part_number"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select name="category"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                            @foreach(['Lubricants','Filters','Brakes','Engine','Electrical','Cooling','Transmission','Suspension','Steering','Tyres','Accessories'] as $cat)
                            <option value="{{ $cat }}" :selected="part.category === '{{ $cat }}'">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (RM) *</label>
                        <input type="number" name="unit_price" :value="part.unit_price"
                            step="0.01" min="0"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                        <input type="number" name="stock" :value="part.stock" min="0"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Stock *</label>
                        <input type="number" name="min_stock" :value="part.min_stock" min="1"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
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
    @endif

</div>{{-- end x-data wrapper --}}

@endsection