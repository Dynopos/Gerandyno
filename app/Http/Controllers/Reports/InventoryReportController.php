<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Reports\ReportPeriodResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReportController extends Controller
{
    /**
     * One row per product: stock received ("stock in", from SalesPlay GRN
     * sync) and sold ("stock out", from receipt items) within the selected
     * date range, plus the balance at the start and end of that range.
     *
     * SalesPlay only ever reports the *current* stock-on-hand (a live
     * snapshot, not a history), so the end-of-range balance is derived by
     * unwinding any movements that happened after the range's end date from
     * that live figure, and the start-of-range balance by further unwinding
     * the movements within the range itself. For the default "today" range
     * this collapses back to today's live balance, same as before.
     */
    public function index(Request $request): View
    {
        $period = ReportPeriodResolver::resolve($request);

        $products = Product::query()
            ->withSum(['stockInItems as stock_in' => fn (Builder $q) => $q->whereHas(
                'stockIn', fn (Builder $sq) => $sq->whereBetween('received_at', [$period->start, $period->end])
            )], 'quantity')
            ->withSum(['receiptItems as stock_out' => fn (Builder $q) => $q->whereHas(
                'receipt', fn (Builder $sq) => $sq->whereBetween('transaction_date', [$period->start, $period->end])
            )], 'quantity')
            ->withSum(['stockInItems as stock_in_after_range' => fn (Builder $q) => $q->whereHas(
                'stockIn', fn (Builder $sq) => $sq->where('received_at', '>', $period->end)
            )], 'quantity')
            ->withSum(['receiptItems as stock_out_after_range' => fn (Builder $q) => $q->whereHas(
                'receipt', fn (Builder $sq) => $sq->where('transaction_date', '>', $period->end)
            )], 'quantity')
            ->when($request->string('q')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('barcode', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('reports.inventory.index', [
            'period' => $period,
            'products' => $products,
            'search' => $request->string('q')->toString(),
            'filterOptions' => ReportPeriodResolver::options(),
        ]);
    }
}
