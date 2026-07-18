<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonthlyExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, array{label: string, month: int, total: float, transactions: int}>  $months
     */
    public function __construct(
        private readonly Collection $months,
    ) {}

    public function collection(): Collection
    {
        return $this->months;
    }

    public function headings(): array
    {
        return [
            __('app.reports.monthly.month'),
            __('app.reports.monthly.transactions'),
            __('app.reports.monthly.total_sales_col'),
        ];
    }

    public function map($month): array
    {
        return [
            $month['label'],
            $month['transactions'],
            (float) $month['total'],
        ];
    }
}
