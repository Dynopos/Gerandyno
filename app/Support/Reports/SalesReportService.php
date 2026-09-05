<?php

namespace App\Support\Reports;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Sales aggregation queries shared by the dashboard and report pages.
 *
 * All Receipt queries here rely on Receipt's CompanyScope global scope for
 * tenant isolation (these run in an authenticated web request, so the scope
 * applies automatically). The one exception is productSales(), which joins
 * the receipts table directly and so needs an explicit company_id filter.
 */
final class SalesReportService
{
    public function totalBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        return (float) Receipt::whereBetween('transaction_date', [$start, $end])->sum('total');
    }

    public function countBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return Receipt::whereBetween('transaction_date', [$start, $end])->count();
    }

    /**
     * Tax collected within a date range.
     *
     * Receipt totals are what the customer actually paid, tax included —
     * that is the money that reached the till. The tax inside it belongs to
     * the government, not the shop, so anything measuring the shop's own
     * earnings (P&L) has to take it back out, and anything being reconciled
     * against SalesPlay's tax-exclusive "Gross sales" needs it stated.
     */
    public function taxBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        return (float) Receipt::whereBetween('transaction_date', [$start, $end])->sum('tax');
    }

    /**
     * Daily totals for every day in the given month, zero-filled for days
     * with no sales.
     *
     * @return Collection<int, array{label: string, date: string, total: float}>
     */
    public function dailySeries(CarbonImmutable $monthStart): Collection
    {
        $monthStart = $monthStart->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        $totals = Receipt::whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->select('transaction_date', 'total')
            ->get()
            ->groupBy(fn (Receipt $r) => $r->transaction_date->format('Y-m-d'))
            ->map(fn (Collection $rows) => (float) $rows->sum('total'));

        return collect(range(1, $monthStart->daysInMonth))->map(function (int $day) use ($monthStart, $totals) {
            $date = $monthStart->addDays($day - 1);

            return [
                'label' => $date->format('j'),
                'date' => $date->format('Y-m-d'),
                'total' => (float) $totals->get($date->format('Y-m-d'), 0),
            ];
        });
    }

    /**
     * Totals + transaction counts for every month in the given year,
     * zero-filled for months with no sales.
     *
     * @return Collection<int, array{label: string, month: int, total: float, transactions: int}>
     */
    public function monthlySeries(int $year): Collection
    {
        $yearStart = CarbonImmutable::create($year, 1, 1)->startOfYear();
        $yearEnd = $yearStart->endOfYear();

        $grouped = Receipt::whereBetween('transaction_date', [$yearStart, $yearEnd])
            ->select('transaction_date', 'total')
            ->get()
            ->groupBy(fn (Receipt $r) => (int) $r->transaction_date->format('n'));

        return collect(range(1, 12))->map(function (int $month) use ($yearStart, $grouped) {
            $rows = $grouped->get($month);

            return [
                'label' => $yearStart->month($month)->translatedFormat('M'),
                'month' => $month,
                'total' => (float) ($rows?->sum('total') ?? 0),
                'transactions' => $rows?->count() ?? 0,
            ];
        });
    }

    /**
     * Totals + transaction counts grouped by calendar year, across all
     * history, most recent year first.
     *
     * @return Collection<int, array{label: string, year: int, total: float, transactions: int}>
     */
    public function yearlySeries(): Collection
    {
        return Receipt::query()
            ->select('transaction_date', 'total')
            ->get()
            ->groupBy(fn (Receipt $r) => (int) $r->transaction_date->format('Y'))
            ->map(fn (Collection $rows, int $year) => [
                'label' => (string) $year,
                'year' => $year,
                'total' => (float) $rows->sum('total'),
                'transactions' => $rows->count(),
            ])
            ->sortKeysDesc()
            ->values();
    }

    /**
     * Quantity sold + total sales per product name within a date range.
     *
     * @return Collection<int, array{product_name: string, quantity_sold: float, total_sales: float}>
     */
    public function productSales(int $companyId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return ReceiptItem::query()
            ->join('receipts', 'receipts.id', '=', 'receipt_items.receipt_id')
            ->where('receipts.company_id', $companyId)
            ->whereBetween('receipts.transaction_date', [$start, $end])
            ->groupBy('receipt_items.product_name')
            ->selectRaw('receipt_items.product_name as product_name, SUM(receipt_items.quantity) as quantity_sold, SUM(receipt_items.total) as total_sales')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'quantity_sold' => (float) $row->quantity_sold,
                'total_sales' => (float) $row->total_sales,
            ]);
    }

    /**
     * Total sales and transaction count per payment method (cash, card,
     * e-wallet, etc.) within a date range.
     *
     * @return Collection<int, array{payment_method: string, total: float, transactions: int}>
     */
    public function paymentTypeSales(int $companyId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return Payment::query()
            ->join('receipts', 'receipts.id', '=', 'payments.receipt_id')
            ->where('receipts.company_id', $companyId)
            ->whereBetween('receipts.transaction_date', [$start, $end])
            ->groupBy('payments.payment_method')
            ->selectRaw('payments.payment_method as payment_method, SUM(payments.amount) as total, COUNT(*) as transactions')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'payment_method' => $row->payment_method,
                'total' => (float) $row->total,
                'transactions' => (int) $row->transactions,
            ]);
    }

    /**
     * Top product categories by sales within a date range, each with its
     * share of the total as a percentage. Items with no linked product (and
     * so no category) are excluded.
     *
     * @return Collection<int, array{category: string, total: float, percentage: float}>
     */
    public function categorySales(int $companyId, CarbonInterface $start, CarbonInterface $end, int $limit = 5): Collection
    {
        $rows = ReceiptItem::query()
            ->join('receipts', 'receipts.id', '=', 'receipt_items.receipt_id')
            ->join('products', 'products.id', '=', 'receipt_items.product_id')
            ->where('receipts.company_id', $companyId)
            ->whereBetween('receipts.transaction_date', [$start, $end])
            ->groupBy('products.category')
            ->selectRaw("COALESCE(NULLIF(products.category, ''), 'Lain-lain') as category, SUM(receipt_items.total) as total")
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $grandTotal = (float) $rows->sum('total');

        return $rows->map(fn ($row) => [
            'category' => $row->category,
            'total' => (float) $row->total,
            'percentage' => $grandTotal > 0 ? round(((float) $row->total / $grandTotal) * 100, 1) : 0.0,
        ]);
    }

    /**
     * Distinct calendar years that have at least one receipt, most recent
     * first. Falls back to the current year when there's no data yet.
     *
     * @return Collection<int, int>
     */
    public function availableYears(): Collection
    {
        $years = Receipt::query()
            ->select('transaction_date')
            ->get()
            ->map(fn (Receipt $r) => (int) $r->transaction_date->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        return $years->isEmpty() ? collect([now()->year]) : $years;
    }
}
