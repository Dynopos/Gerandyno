<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockIn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReportController extends Controller
{
    public function stock(Request $request): View
    {
        $products = Product::query()
            ->when($request->string('q')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('barcode', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('reports.inventory.stock', [
            'products' => $products,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function stockIns(Request $request): View
    {
        $stockIns = StockIn::query()
            ->withCount('items')
            ->when($request->string('q')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $q) => $q->where('supplier_name', 'like', $term)->orWhere('invoice_no', 'like', $term));
            })
            ->orderByDesc('received_at')
            ->paginate(20)
            ->withQueryString();

        return view('reports.inventory.stock-ins.index', [
            'stockIns' => $stockIns,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function showStockIn(StockIn $stockIn): View
    {
        $this->authorize('view', $stockIn);

        $stockIn->load('items');

        return view('reports.inventory.stock-ins.show', [
            'stockIn' => $stockIn,
        ]);
    }
}
