<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlayReceiptItemData
{
    public function __construct(
        public ?string $salesplayProductId,
        public string $productName,
        public float $quantity,
        public float $unitPrice,
        public float $discount,
        public float $total,
    ) {}
}
