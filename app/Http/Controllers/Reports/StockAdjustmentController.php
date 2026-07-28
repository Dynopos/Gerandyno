<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function create(Product $product): View
    {
        return view('reports.inventory.adjustment', [
            'product' => $product,
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0'],
            'adjusted_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        StockAdjustment::create($validated + [
            'product_id' => $product->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('reports.inventory.index')
            ->with('status', __('app.reports.inventory.adjustment_saved', ['product' => $product->name]));
    }
}
