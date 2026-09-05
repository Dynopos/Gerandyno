<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PnlExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, array{category: string, total: float}>  $expensesByCategory
     */
    public function __construct(
        private readonly float $totalSales,
        private readonly float $totalTax,
        private readonly float $netSales,
        private readonly Collection $expensesByCategory,
        private readonly float $totalExpenses,
        private readonly float $netProfit,
    ) {}

    public function collection(): Collection
    {
        $rows = collect([
            ['label' => __('app.reports.pnl.total_sales'), 'amount' => $this->totalSales],
            ['label' => __('app.reports.pnl.tax_collected'), 'amount' => -$this->totalTax],
            ['label' => __('app.reports.pnl.net_sales'), 'amount' => $this->netSales],
        ]);

        foreach ($this->expensesByCategory as $expense) {
            $rows->push(['label' => '- '.$expense['category'], 'amount' => -$expense['total']]);
        }

        return $rows
            ->push(['label' => __('app.reports.pnl.total_expenses'), 'amount' => -$this->totalExpenses])
            ->push(['label' => __('app.reports.pnl.net_profit'), 'amount' => $this->netProfit]);
    }

    public function headings(): array
    {
        return [
            __('app.reports.pnl.line'),
            __('app.reports.pnl.amount'),
        ];
    }

    public function map($row): array
    {
        return [
            $row['label'],
            (float) $row['amount'],
        ];
    }
}
