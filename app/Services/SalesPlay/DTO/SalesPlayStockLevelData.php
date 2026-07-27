<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlayStockLevelData
{
    public function __construct(
        public string $salesplayProductId,
        public ?string $productCode,
        public float $quantityOnHand,
    ) {}
}
