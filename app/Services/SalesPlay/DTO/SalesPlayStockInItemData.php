<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlayStockInItemData
{
    public function __construct(
        public ?string $salesplayProductId,
        public string $productName,
        public float $quantity,
        public float $unitCost,
        public float $total,
    ) {}
}
