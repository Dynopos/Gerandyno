<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class YearlyExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, array{label: string, year: int, total: float, transactions: int}>  $years
     */
    public function __construct(
        private readonly Collection $years,
    ) {}

    public function collection(): Collection
    {
        return $this->years;
    }

    public function headings(): array
    {
        return [
            __('app.reports.yearly.year'),
            __('app.reports.yearly.transactions'),
            __('app.reports.yearly.total_sales'),
        ];
    }

    public function map($year): array
    {
        return [
            $year['label'],
            $year['transactions'],
            (float) $year['total'],
        ];
    }
}
