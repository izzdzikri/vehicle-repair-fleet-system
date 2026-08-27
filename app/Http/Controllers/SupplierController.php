<?php
namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index() {
        $suppliers = Supplier::withCount('purchaseOrders')->orderBy('name')->get();
        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request) {
        $request->validate([
            'name'           => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string|max:255',
        ]);

        Supplier::create($request->only(['name','contact_person','phone','email','address']));

        return back()->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier) {
        $request->validate([
            'name'           => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string|max:255',
        ]);

        $supplier->update($request->only(['name','contact_person','phone','email','address']));

        return back()->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier) {
        if ($supplier->purchaseOrders()->count() > 0) {
            return back()->with('error', 'Cannot delete — this supplier has purchase order history.');
        }
        $supplier->delete();
        return back()->with('success', 'Supplier deleted.');
    }
}