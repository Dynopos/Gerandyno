<?php

namespace App\Exports;

use App\Models\Receipt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Receipt>  $receipts
     */
    public function __construct(
        private readonly Collection $receipts,
    ) {}

    public function collection(): Collection
    {
        return $this->receipts;
    }

    public function headings(): array
    {
        return [
            __('app.reports.sales.date'),
            __('app.reports.sales.receipt_number'),
            __('app.receipt.subtotal'),
            __('app.receipt.discount'),
            __('app.receipt.tax'),
            __('app.reports.sales.total'),
            __('app.reports.sales.payment_method'),
        ];
    }

    public function map($receipt): array
    {
        return [
            $receipt->transaction_date->format('Y-m-d H:i'),
            $receipt->receipt_number ?? '#'.$receipt->id,
            (float) $receipt->subtotal,
            (float) $receipt->discount,
            (float) $receipt->tax,
            (float) $receipt->total,
            $receipt->payments->pluck('payment_method')->map(fn ($m) => ucfirst($m))->implode(', '),
        ];
    }
}
