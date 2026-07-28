<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentTypesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, array{payment_method: string, total: float, transactions: int}>  $paymentTypes
     */
    public function __construct(
        private readonly Collection $paymentTypes,
    ) {}

    public function collection(): Collection
    {
        return $this->paymentTypes;
    }

    public function headings(): array
    {
        return [
            __('app.reports.payment_types.payment_method'),
            __('app.reports.payment_types.transactions'),
            __('app.reports.payment_types.total_sales'),
        ];
    }

    public function map($paymentType): array
    {
        return [
            $paymentType['payment_method'],
            (int) $paymentType['transactions'],
            (float) $paymentType['total'],
        ];
    }
}
