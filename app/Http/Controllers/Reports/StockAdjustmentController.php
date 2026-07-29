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

    /**
     * Set every product's baseline to 0 as of now, e.g. when a shop wants to
     * start tracking fresh from an empty stock count instead of adjusting
     * products one by one.
     */
    public function resetAll(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;
        $now = now();
        $note = __('app.reports.inventory.reset_note');

        $rows = Product::query()->pluck('id')->map(fn (int $productId) => [
            'company_id' => $companyId,
            'product_id' => $productId,
            'created_by' => $request->user()->id,
            'quantity' => 0,
            'note' => $note,
            'adjusted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($rows->isNotEmpty()) {
            StockAdjustment::insert($rows->all());
        }

        return redirect()->route('reports.inventory.index')
            ->with('status', __('app.reports.inventory.reset_saved'));
    }
}
