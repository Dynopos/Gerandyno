<?php

namespace App\Services\SalesPlay\DTO;

use Carbon\CarbonInterface;

final readonly class SalesPlayStockInData
{
    /**
     * @param  array<int, SalesPlayStockInItemData>  $items
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $salesplayGrnId,
        public ?string $supplierName,
        public ?string $invoiceNo,
        public CarbonInterface $receivedAt,
        public float $total,
        public array $items,
        public array $raw,
    ) {}
}
