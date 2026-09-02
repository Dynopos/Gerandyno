<?php

namespace App\Support\Reports;

use App\Models\Expense;
use App\Models\Receipt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds the one-week picture of a company's sales that the AI insight page
 * summarises: this week against last week, day by day, and the top
 * products / categories / payment methods within it.
 *
 * Reuses SalesReportService for every aggregate it already computes; the
 * only thing added here is the per-day breakdown, which the dashboard's
 * month-shaped dailySeries() can't express for an arbitrary week.
 */
final class WeeklySalesReportService
{
    private const TOP_LIMIT = 5;

    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    /**
     * Monday-to-Sunday week containing $reference.
     */
    public function build(int $companyId, CarbonImmutable $reference): WeeklySalesSnapshot
    {
        $weekStart = $reference->startOfWeek();
        $weekEnd = $weekStart->endOfWeek();
        $previousStart = $weekStart->subWeek();
        $previousEnd = $previousStart->endOfWeek();

        $totalSales = $this->reports->totalBetween($weekStart, $weekEnd);
        $transactions = $this->reports->countBetween($weekStart, $weekEnd);
        $previousTotalSales = $this->reports->totalBetween($previousStart, $previousEnd);
        $previousTransactions = $this->reports->countBetween($previousStart, $previousEnd);

        $dailySeries = $this->dailySeries($weekStart);
        $daysWithSales = $dailySeries->filter(fn (array $day) => $day['transactions'] > 0);

        $totalExpenses = (float) Expense::whereBetween('expense_date', [$weekStart, $weekEnd])->sum('amount');

        return new WeeklySalesSnapshot(
            weekStart: $weekStart,
            weekEnd: $weekEnd,
            totalSales: $totalSales,
            transactions: $transactions,
            averageBasket: $transactions > 0 ? $totalSales / $transactions : 0.0,
            previousTotalSales: $previousTotalSales,
            previousTransactions: $previousTransactions,
            deltaPercent: $previousTotalSales > 0.0
                ? (($totalSales - $previousTotalSales) / $previousTotalSales) * 100
                : null,
            dailySeries: $dailySeries,
            topProducts: $this->reports->productSales($companyId, $weekStart, $weekEnd)->take(self::TOP_LIMIT)->values(),
            topCategories: $this->reports->categorySales($companyId, $weekStart, $weekEnd, self::TOP_LIMIT),
            paymentMix: $this->reports->paymentTypeSales($companyId, $weekStart, $weekEnd),
            // Both are taken from days that actually traded — a shop closed on
            // Sunday should not be told its "quietest day" is the day it shuts.
            bestDay: $daysWithSales->sortByDesc('total')->first(),
            quietestDay: $daysWithSales->count() > 1 ? $daysWithSales->sortBy('total')->first() : null,
            totalExpenses: $totalExpenses,
            netProfit: $totalSales - $totalExpenses,
        );
    }

    /**
     * Totals + transaction counts for each of the seven days, zero-filled
     * for days with no sales.
     *
     * @return Collection<int, array{label: string, date: string, total: float, transactions: int}>
     */
    private function dailySeries(CarbonImmutable $weekStart): Collection
    {
        $grouped = Receipt::whereBetween('transaction_date', [$weekStart, $weekStart->endOfWeek()])
            ->select('transaction_date', 'total')
            ->get()
            ->groupBy(fn (Receipt $r) => $r->transaction_date->format('Y-m-d'));

        return collect(range(0, 6))->map(function (int $offset) use ($weekStart, $grouped) {
            $date = $weekStart->addDays($offset);
            $rows = $grouped->get($date->format('Y-m-d'));

            return [
                'label' => $date->translatedFormat('l'),
                'date' => $date->format('Y-m-d'),
                'total' => (float) ($rows?->sum('total') ?? 0),
                'transactions' => $rows?->count() ?? 0,
            ];
        });
    }
}
