<?php

namespace App\Support\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Everything the AI insight page knows about one sales week: the headline
 * figures, how they compare to the week before, and the breakdowns
 * (per day, per product, per category, per payment method) the generator
 * turns into advice.
 *
 * This is the only thing sent to the Claude API — plain aggregated numbers
 * and product names, never receipts, customer records, or anything that
 * identifies a buyer.
 */
final readonly class WeeklySalesSnapshot
{
    /**
     * @param  Collection<int, array{label: string, date: string, total: float, transactions: int}>  $dailySeries
     * @param  Collection<int, array{product_name: string, quantity_sold: float, total_sales: float}>  $topProducts
     * @param  Collection<int, array{category: string, total: float, percentage: float}>  $topCategories
     * @param  Collection<int, array{payment_method: string, total: float, transactions: int}>  $paymentMix
     * @param  array{label: string, date: string, total: float, transactions: int}|null  $bestDay
     * @param  array{label: string, date: string, total: float, transactions: int}|null  $quietestDay
     */
    public function __construct(
        public CarbonImmutable $weekStart,
        public CarbonImmutable $weekEnd,
        public float $totalSales,
        public int $transactions,
        public float $averageBasket,
        public float $previousTotalSales,
        public int $previousTransactions,
        public ?float $deltaPercent,
        public Collection $dailySeries,
        public Collection $topProducts,
        public Collection $topCategories,
        public Collection $paymentMix,
        public ?array $bestDay,
        public ?array $quietestDay,
        public float $totalExpenses,
        public float $netProfit,
    ) {}

    public function hasSales(): bool
    {
        return $this->transactions > 0;
    }

    public function periodLabel(): string
    {
        return $this->weekStart->translatedFormat('d M Y').' - '.$this->weekEnd->translatedFormat('d M Y');
    }

    /**
     * The prompt payload. Figures are rounded to sen so the model never has
     * to reason about floating point noise.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'week_start' => $this->weekStart->toDateString(),
            'week_end' => $this->weekEnd->toDateString(),
            'currency' => 'MYR',
            'total_sales' => round($this->totalSales, 2),
            'transactions' => $this->transactions,
            'average_basket' => round($this->averageBasket, 2),
            'previous_week_total_sales' => round($this->previousTotalSales, 2),
            'previous_week_transactions' => $this->previousTransactions,
            'change_vs_previous_week_percent' => $this->deltaPercent === null ? null : round($this->deltaPercent, 1),
            'daily_sales' => $this->dailySeries->all(),
            'best_day' => $this->bestDay,
            'quietest_day' => $this->quietestDay,
            'top_products' => $this->topProducts->all(),
            'top_categories' => $this->topCategories->all(),
            'payment_mix' => $this->paymentMix->all(),
            'total_expenses' => round($this->totalExpenses, 2),
            'net_profit' => round($this->netProfit, 2),
        ];
    }
}
