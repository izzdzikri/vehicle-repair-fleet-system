<?php
namespace App\Http\Controllers;

use App\Models\SparePart;
use Illuminate\Http\Request;

class SparePartController extends Controller
{
    public function index() {
        $parts = SparePart::orderBy('category')->orderBy('name')->get();
        return view('inventory.index', compact('parts'));
    }

    public function store(Request $request) {
        $request->validate([
            'name'        => 'required|string|max:100',
            'brand'       => 'nullable|string|max:60',
            'part_number' => 'required|string|max:50|unique:spare_parts,part_number',
            'category'    => 'required|string|max:50',
            'unit_price'  => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'min_stock'   => 'required|integer|min:1',
        ]);

        SparePart::create($request->only([
            'name','brand','part_number','category','unit_price','stock','min_stock'
        ]));

        return back()->with('success', 'Part added successfully.');
    }

    public function update(Request $request, SparePart $sparePart) {
        $request->validate([
            'name'        => 'required|string|max:100',
            'brand'       => 'nullable|string|max:60',
            'part_number' => 'required|string|max:50|unique:spare_parts,part_number,' . $sparePart->id,
            'category'    => 'required|string|max:50',
            'unit_price'  => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'min_stock'   => 'required|integer|min:1',
        ]);

        $sparePart->update($request->only([
            'name','brand','part_number','category','unit_price','stock','min_stock'
        ]));

        return back()->with('success', 'Part updated successfully.');
    }

    public function destroy(SparePart $sparePart) {
        // Safety check — don't delete if used in any job card
        if ($sparePart->jobCardParts()->count() > 0) {
            return back()->with('error', 'Cannot delete — this part is used in existing job cards.');
        }
        $sparePart->delete();
        return back()->with('success', 'Part deleted.');
    }
}