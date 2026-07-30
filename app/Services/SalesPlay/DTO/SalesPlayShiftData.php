<?php

namespace App\Services\SalesPlay\DTO;

use Carbon\CarbonInterface;

final readonly class SalesPlayShiftData
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $salesplayShiftId,
        public ?string $posDeviceId,
        public ?CarbonInterface $openedAt,
        public ?CarbonInterface $closedAt,
        public ?string $openedByEmployee,
        public ?string $closedByEmployee,
        public float $startingCash,
        public float $cashPayments,
        public float $cashRefunds,
        public float $paidIn,
        public float $paidOut,
        public float $expectedCash,
        public float $actualCash,
        public float $grossSales,
        public float $refunds,
        public float $discounts,
        public float $netSales,
        public float $tip,
        public float $surcharge,
        public array $raw,
    ) {}
}
