<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, array{product_name: string, quantity_sold: float, total_sales: float}>  $products
     */
    public function __construct(
        private readonly Collection $products,
    ) {}

    public function collection(): Collection
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [
            __('app.reports.products.product_name'),
            __('app.reports.products.quantity_sold'),
            __('app.reports.products.total_sales'),
        ];
    }

    public function map($product): array
    {
        return [
            $product['product_name'],
            (float) $product['quantity_sold'],
            (float) $product['total_sales'],
        ];
    }
}
