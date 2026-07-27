<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReportController extends Controller
{
    /**
     * One row per product: total received ("stock in", from SalesPlay GRN
     * sync), total sold ("stock out", from receipt items — same figure as
     * the Product Report's quantity_sold), and the current stock-on-hand
     * balance as last synced from SalesPlay.
     */
    public function index(Request $request): View
    {
        $products = Product::query()
            ->withSum('stockInItems', 'quantity')
            ->withSum('receiptItems', 'quantity')
            ->when($request->string('q')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('barcode', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('reports.inventory.index', [
            'products' => $products,
            'search' => $request->string('q')->toString(),
        ]);
    }
}
