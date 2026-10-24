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

    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Spare Parts & Inventory</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                    {{ $totalParts }} cataloged items
                </span>
                @if($lowCount)
                <span class="text-xs font-semibold px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    {{ $lowCount }} low stock alerts
                </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">Monitor component stock levels, barcodes, and workshop replenishment</p>
        </div>
        @if(auth()->user()->hasPermission('inventory.manage'))
        <button @click="showAdd = true"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add New Part
        </button>
        @endif
    </div>

    {{-- Search + Barcode Scan --}}
    <form method="GET" id="inventory-search-form" class="mb-5 flex gap-2 flex-wrap items-center">
        <div class="relative flex-1 max-w-md">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="search" id="search-input" value="{{ $search }}"
                placeholder="Search by name, part number, or brand..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
        </div>
        <button type="submit" class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">
            Search
        </button>
        <button type="button" @click="openScanner('search')"
            class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-3.5 py-2.5 rounded-xl text-xs font-semibold hover:bg-gray-50 shadow-xs transition"
            title="Scan barcode / QR code">
            <i data-lucide="scan-barcode" class="w-4 h-4 text-blue-600"></i>
            <span>Scan Barcode</span>
        </button>
        @if($search)
        <a href="{{ url()->current() }}" class="px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50">Clear</a>
        @endif
    </form>

    {{-- Parts Table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <th class="px-5 py-3.5">Part Specification</th>
                    <th class="px-5 py-3.5">Category</th>
                    <th class="px-5 py-3.5">SKU / Part No.</th>
                    <th class="px-5 py-3.5">Unit Price</th>
                    <th class="px-5 py-3.5">Stock Level</th>
                    @if(auth()->user()->hasPermission('inventory.manage'))
                    <th class="px-5 py-3.5 text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($parts as $part)
                @php $low = $part->stock <= $part->min_stock; @endphp
                <tr class="hover:bg-gray-50/60 transition {{ $low ? 'bg-rose-50/30' : '' }}">
                    <td class="px-5 py-4">
                        <p class="font-bold text-sm text-gray-900">{{ $part->name }}</p>
                        @if($part->brand)
                        <p class="text-xs font-medium text-gray-500 mt-0.5">{{ $part->brand }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs font-semibold bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md border border-gray-200">
                            {{ $part->category }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="font-mono text-xs font-bold text-gray-800 bg-gray-50 px-2.5 py-1 rounded border border-gray-200">
                            {{ $part->part_number }}
                        </span>
                    </td>
                    <td class="px-5 py-4 font-bold text-sm text-gray-900">
                        RM {{ number_format($part->unit_price, 2) }}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm {{ $low ? 'text-rose-600' : 'text-gray-900' }}">
                                {{ $part->stock }}
                            </span>
                            <span class="text-xs text-gray-400 font-medium">/ min {{ $part->min_stock }}</span>
                            @if($low)
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded">
                                Low Stock
                            </span>
                            @endif
                        </div>
                    </td>
                    @if(auth()->user()->hasPermission('inventory.manage'))
                    <td class="px-5 py-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <button type="button"
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
                                class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                <i data-lucide="pencil" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span>Edit</span>
                            </button>
                            <form method="POST" action="/inventory/spare-parts/{{ $part->id }}" class="inline"
                                onsubmit="return confirmSubmit(event, {title:'Delete part?', message:'This will permanently delete {{ addslashes($part->name) }} ({{ addslashes($part->part_number) }}) from inventory. This cannot be undone.', confirmLabel:'Delete Part'})">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="text-xs font-semibold bg-white border border-gray-200 text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
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
    @endif

    {{-- Barcode / QR Scanner Modal — available to everyone, not just
         inventory.manage holders, since the "Scan" button next to the
         search bar (usable by anyone) opens it too. --}}
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
            <div class="px-5 py-3 bg-gray-50 border-t space-y-2">
                <p class="text-xs text-gray-500 text-center" id="scanner-status">Initialising camera…</p>
                <div class="flex gap-2 pt-1 border-t">
                    <input type="text" id="inventory-laser-input"
                        placeholder="Or scan with USB laser scanner..."
                        class="flex-1 border rounded px-3 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onkeydown="if(event.key==='Enter'){ event.preventDefault(); handleScanResult(this.value.trim(), window._currentScanTarget || 'search'); }">
                    <button type="button" onclick="handleScanResult(document.getElementById('inventory-laser-input').value.trim(), window._currentScanTarget || 'search')"
                        class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-semibold hover:bg-blue-700">
                        Select
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- html5-qrcode CDN --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let _qrScanner = null;
window._currentScanTarget = 'search';

function playInventoryBeep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
    } catch(e) {}
}

function startBarcodeScanner(target) {
    window._currentScanTarget = target;
    const container = document.getElementById('barcode-scanner-container');
    const status    = document.getElementById('scanner-status');
    const laserInput = document.getElementById('inventory-laser-input');
    if (laserInput) {
        laserInput.value = '';
        setTimeout(() => laserInput.focus(), 250);
    }

    if (_qrScanner) {
        try { _qrScanner.clear(); } catch(e) {}
        _qrScanner = null;
    }

    container.innerHTML = '';

    if (typeof Html5Qrcode === 'undefined') {
        status.textContent = 'Scanner library loading. You can use handheld input below.';
        return;
    }

    _qrScanner = new Html5Qrcode('barcode-scanner-container');

    Html5Qrcode.getCameras().then(cameras => {
        if (!cameras || cameras.length === 0) {
            status.textContent = 'No camera found. You can use handheld input below.';
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
            status.textContent = 'Camera unavailable. Use handheld scanner below.';
        });
    }).catch(err => {
        status.textContent = 'Camera permission denied or unavailable. Use handheld scanner below.';
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
    if (!value) return;
    playInventoryBeep();
    stopBarcodeScanner();

    // Close modal via Alpine
    const wrapper = document.querySelector('[x-data]');
    if (wrapper && wrapper.__x) {
        wrapper.__x.$data.showScanner = false;
    } else {
        // Fallback for Alpine v3
        const el = document.querySelector('[x-data]');
        if (el && el._x_dataStack) el._x_dataStack[0].showScanner = false;
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