<?php
namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SparePart;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index() {
        $this->autoGenerate();

        $orders     = PurchaseOrder::with(['sparePart', 'supplier', 'creator'])->latest()->get();
        $suppliers  = Supplier::orderBy('name')->get();
        $spareParts = SparePart::orderBy('name')->get();

        $activeOrders    = $orders->whereIn('status', ['draft', 'ordered']);
        $completedOrders = $orders->whereIn('status', ['received', 'cancelled']);

        $stats = [
            'total'          => $orders->count(),
            'active_count'   => $activeOrders->count(),
            'received_count' => $orders->where('status', 'received')->count(),
            'total_spend'    => (float) $orders->where('status', 'received')->sum(fn($po) => $po->quantity * ($po->unit_cost ?? 0)),
        ];

        return view('purchase-orders.index', compact('orders', 'suppliers', 'spareParts', 'activeOrders', 'completedOrders', 'stats'));
    }

    private function autoGenerate(): void {
        $lowStockParts = SparePart::whereColumn('stock', '<=', 'min_stock')->get();

        foreach ($lowStockParts as $part) {
            $alreadyQueued = PurchaseOrder::where('spare_part_id', $part->id)
                ->whereIn('status', ['draft', 'ordered'])
                ->exists();

            if (!$alreadyQueued) {
                $suggestedQty = max($part->max_stock - $part->stock, $part->min_stock);

                PurchaseOrder::create([
                    'spare_part_id'  => $part->id,
                    'quantity'       => $suggestedQty,
                    'status'         => 'draft',
                    'auto_generated' => true,
                    'created_by'     => auth()->id(),
                ]);
            }
        }
    }

    public function store(Request $request) {
        $request->validate([
            'spare_part_id' => 'required|exists:spare_parts,id',
            'quantity'      => 'required|integer|min:1',
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'unit_cost'     => 'nullable|numeric|min:0',
        ]);

        PurchaseOrder::create([
            'spare_part_id' => $request->spare_part_id,
            'quantity'      => $request->quantity,
            'supplier_id'   => $request->supplier_id,
            'unit_cost'     => $request->unit_cost,
            'status'        => 'draft',
            'created_by'    => auth()->id(),
        ]);

        return back()->with('success', 'Purchase order created.');
    }

    public function markOrdered(Request $request, PurchaseOrder $purchaseOrder) {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'unit_cost'   => 'nullable|numeric|min:0',
        ]);

        $purchaseOrder->update([
            'supplier_id' => $request->supplier_id,
            'unit_cost'   => $request->unit_cost,
            'status'      => 'ordered',
            'ordered_at'  => now(),
        ]);

        return back()->with('success', 'Marked as ordered.');
    }

    public function markReceived(PurchaseOrder $purchaseOrder) {
        if ($purchaseOrder->status !== 'ordered') {
            return back()->with('error', 'Only ordered purchase orders can be received.');
        }

        $purchaseOrder->update([
            'status'      => 'received',
            'received_at' => now(),
        ]);

        $purchaseOrder->sparePart->increment('stock', $purchaseOrder->quantity);

        return back()->with('success', "Stock received — {$purchaseOrder->sparePart->name} +{$purchaseOrder->quantity} units. Moved to Completed History (Invoice ready to print).");
    }

    public function cancel(PurchaseOrder $purchaseOrder) {
        $purchaseOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Purchase order cancelled.');
    }

    public function invoice(PurchaseOrder $purchaseOrder) {
        $purchaseOrder->load(['sparePart', 'supplier', 'creator']);
        return view('purchase-orders.invoice', compact('purchaseOrder'));
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder) {
        $purchaseOrder->load(['sparePart', 'supplier', 'creator']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchase-orders.pdf', compact('purchaseOrder'))
            ->setPaper('a4', 'portrait');

        $filename = 'PurchaseOrder-' . $purchaseOrder->po_number . '.pdf';
        return $pdf->download($filename);
    }
}