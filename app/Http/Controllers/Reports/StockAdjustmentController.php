<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function create(): View
    {
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('reports.inventory.adjustment', [
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('company_id', $request->user()->company_id),
            ],
            'quantity' => ['required', 'numeric', 'min:0'],
            'adjusted_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        StockAdjustment::create($validated + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('reports.inventory.index')
            ->with('status', __('app.reports.inventory.adjustment_saved', ['product' => $product->name]));
    }
}
