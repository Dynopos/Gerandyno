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
        private readonly Collection $expensesByCategory,
        private readonly float $totalExpenses,
        private readonly float $netProfit,
    ) {}

    public function collection(): Collection
    {
        $rows = collect([
            ['label' => __('app.reports.pnl.total_sales'), 'amount' => $this->totalSales],
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
