<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ReceiptItem;
use App\Models\StockAdjustment;
use App\Models\StockInItem;
use App\Support\Reports\ReportPeriodResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
     * Balance is tracked as our own ledger, not SalesPlay's live stock
     * figure: it starts from the most recent manual Stock Adjustment (a
     * physical stock take) at or before the point in time being calculated
     * — or 0 if the product has never been adjusted — then adds every
     * stock-in and subtracts every sale recorded since that adjustment.
     * SalesPlay's own inventory numbers aren't trustworthy here because
     * merchants often don't record goods received in SalesPlay at all, so
     * relying on its live figure directly can quietly go negative or sit
     * blank with no way to correct it; the Stock Adjustment action is that
     * correction tool.
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
            ->when($request->string('q')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('barcode', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $products->getCollection()->transform(function (Product $product) use ($period) {
            $product->opening_balance = $this->balanceAsOf($product, $period->start->clone()->subSecond());
            $product->closing_balance = $this->balanceAsOf($product, $period->end);

            return $product;
        });

        return view('reports.inventory.index', [
            'period' => $period,
            'products' => $products,
            'search' => $request->string('q')->toString(),
            'filterOptions' => ReportPeriodResolver::options(),
        ]);
    }

    private function balanceAsOf(Product $product, CarbonInterface $asOf): float
    {
        $adjustment = StockAdjustment::query()
            ->where('product_id', $product->id)
            ->where('adjusted_at', '<=', $asOf)
            ->orderByDesc('adjusted_at')
            ->first();

        $baseline = $adjustment ? (float) $adjustment->quantity : 0.0;
        $since = $adjustment?->adjusted_at ?? Carbon::create(2000, 1, 1);

        $stockIn = (float) StockInItem::query()
            ->where('product_id', $product->id)
            ->whereHas('stockIn', fn (Builder $q) => $q->whereBetween('received_at', [$since, $asOf]))
            ->sum('quantity');

        $stockOut = (float) ReceiptItem::query()
            ->where('product_id', $product->id)
            ->whereHas('receipt', fn (Builder $q) => $q->whereBetween('transaction_date', [$since, $asOf]))
            ->sum('quantity');

        return $baseline + $stockIn - $stockOut;
    }
}
