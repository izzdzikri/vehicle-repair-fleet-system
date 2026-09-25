@extends('layouts.app')
@section('page-title', 'Inventory')

@section('content')

<div x-data="{
    showAdd: false,
    showEdit: false,
    showScanner: false,
    scanTarget: 'search',
    part: {},
    openEdit(p) {
        this.part = p;
        this.showEdit = true;
    },
    openScanner(target) {
        this.scanTarget = target;
        this.showScanner = true;
        this.$nextTick(() => startBarcodeScanner(target));
    },
    closeScanner() {
        this.showScanner = false;
        stopBarcodeScanner();
    }
}">

    <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
        <h2 class="text-lg font-semibold text-gray-700">
            Spare Parts
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $totalParts }} items</span>
            @if($lowCount)
            <span class="text-xs bg-red-100 text-red-600 px-3 py-1 rounded-full font-medium ml-2">
                ⚠ {{ $lowCount }} low stock
            </span>
            @endif
        </h2>
        @if(auth()->user()->hasPermission('inventory.manage'))
        <button @click="showAdd = true"
            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Part
        </button>
        @endif
    </div>

    {{-- Search + Barcode Scan --}}
    <form method="GET" id="inventory-search-form" class="mb-4 flex gap-2 flex-wrap">
        <div class="relative flex-1 max-w-md">
            <input type="text" name="search" id="search-input" value="{{ $search }}"
                placeholder="Search by name, part number, or brand..."
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
        </div>
        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Search</button>
        <button type="button" @click="openScanner('search')"
            class="flex items-center gap-2 border border-blue-500 text-blue-600 px-3 py-2 rounded text-sm hover:bg-blue-50 transition"
            title="Scan barcode / QR code">
            <i data-lucide="scan-barcode" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Scan</span>
        </button>
        @if($search)
        <a href="{{ url()->current() }}" class="px-4 py-2 rounded text-sm border text-gray-600 hover:bg-gray-50">Clear</a>
        @endif
    </form>

    {{-- Parts Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-500 border-b">
                    <th class="px-4 py-3">Part</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Part No.</th>
                    <th class="px-4 py-3">Unit Price</th>
                    <th class="px-4 py-3">Stock</th>
                    @if(auth()->user()->hasPermission('inventory.manage'))
                    <th class="px-4 py-3 w-12">Actions</th>
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
                    @if(auth()->user()->hasPermission('inventory.manage'))
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
                                        id:           {{ $part->id }},
                                        name:         '{{ addslashes($part->name) }}',
                                        brand:        '{{ addslashes($part->brand ?? '') }}',
                                        part_number:  '{{ addslashes($part->part_number) }}',
                                        category:     '{{ $part->category }}',
                                        unit_price:   '{{ $part->unit_price }}',
                                        stock:        '{{ $part->stock }}',
                                        min_stock:    '{{ $part->min_stock }}'
                                    })"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-yellow-700 hover:bg-yellow-50">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                                </button>
                                <form method="POST" action="/inventory/spare-parts/{{ $part->id }}"
                                    onsubmit="return confirmSubmit(event, {title:'Delete part?', message:'This will permanently delete {{ addslashes($part->name) }} ({{ addslashes($part->part_number) }}) from inventory. This cannot be undone.', confirmLabel:'Delete Part'})">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-t">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
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
    </div>
    @if($parts->hasPages())
    <div class="px-4 py-3 border-t">
        {{ $parts->links() }}
    </div>
    @endif
    </div>

    @if(auth()->user()->hasPermission('inventory.manage'))
    {{-- Add Modal --}}
    <div x-show="showAdd" x-transition.opacity
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6" @click.outside="showAdd = false">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-semibold text-gray-700">Add New Part</h3>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="/inventory/spare-parts" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
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
                    <div class="flex gap-2">
                        <input type="text" name="part_number" id="add-part-number" value="{{ old('part_number') }}"
                            placeholder="e.g. CO-5W30-4L"
                            class="flex-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                        <button type="button" @click="openScanner('add-part')" title="Scan barcode for part number"
                            class="border border-blue-400 text-blue-600 px-2.5 rounded hover:bg-blue-50 transition">
                            <i data-lucide="scan-barcode" class="w-4 h-4"></i>
                        </button>
                    </div>
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
                <div class="md:col-span-2 flex gap-3 pt-2">
                    <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        Add Part
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
                <h3 class="text-lg font-semibold text-gray-700">Edit Part</h3>
                <button @click="showEdit = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/inventory/spare-parts/' + part.id">
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
        {{-- Barcode / QR Scanner Modal --}}
    <div x-show="showScanner" x-transition.opacity
        class="fixed inset-0 bg-black/70 z-[60] flex items-center justify-center p-4"
        style="display:none"
        @keydown.escape.window="closeScanner()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                        <i data-lucide="scan-barcode" class="w-5 h-5 text-blue-600"></i>
                        Barcode / QR Scanner
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Point your camera at a barcode or QR code</p>
                </div>
                <button @click="closeScanner()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div id="barcode-scanner-container" class="w-full bg-black" style="height: 280px;"></div>
            <div class="px-5 py-3 bg-gray-50 border-t">
                <p class="text-xs text-gray-500 text-center" id="scanner-status">Initialising camera…</p>
            </div>
        </div>
    </div>

</div>

{{-- html5-qrcode CDN --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let _qrScanner = null;

function startBarcodeScanner(target) {
    const container = document.getElementById('barcode-scanner-container');
    const status    = document.getElementById('scanner-status');

    if (_qrScanner) {
        try { _qrScanner.clear(); } catch(e) {}
        _qrScanner = null;
    }

    container.innerHTML = '';

    _qrScanner = new Html5Qrcode('barcode-scanner-container');

    Html5Qrcode.getCameras().then(cameras => {
        if (!cameras || cameras.length === 0) {
            status.textContent = 'No camera found.';
            return;
        }

        const cameraId = cameras[cameras.length - 1].id; // prefer rear camera
        status.textContent = 'Camera ready — scan your barcode or QR code.';

        _qrScanner.start(
            cameraId,
            { fps: 10, qrbox: { width: 240, height: 160 } },
            (decodedText) => {
                handleScanResult(decodedText.trim(), target);
            },
            () => {} // ignore parse errors
        ).catch(err => {
            status.textContent = 'Camera error: ' + err;
        });
    }).catch(err => {
        status.textContent = 'Camera permission denied or unavailable.';
    });
}

function stopBarcodeScanner() {
    if (_qrScanner) {
        _qrScanner.stop().catch(() => {}).finally(() => {
            _qrScanner = null;
        });
    }
}

function handleScanResult(value, target) {
    stopBarcodeScanner();

    // Close modal via Alpine
    const wrapper = document.querySelector('[x-data]');
    if (wrapper && wrapper.__x) {
        wrapper.__x.$data.showScanner = false;
    } else {
        // Fallback for Alpine v3
        const el = document.querySelector('[x-data]');
        if (el._x_dataStack) el._x_dataStack[0].showScanner = false;
    }

    if (target === 'search') {
        // Fill search box and auto-submit
        const input = document.getElementById('search-input');
        if (input) {
            input.value = value;
            document.getElementById('inventory-search-form').submit();
        }
    } else if (target === 'add-part') {
        // Fill the part number field in the Add modal
        const pn = document.getElementById('add-part-number');
        if (pn) pn.value = value;
    }
}
</script>

@endsection