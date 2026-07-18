<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlayPaymentData
{
    public function __construct(
        public string $paymentMethod,
        public float $amount,
    ) {}
}
