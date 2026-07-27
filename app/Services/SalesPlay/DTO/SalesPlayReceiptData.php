<?php

namespace App\Services\SalesPlay\DTO;

use Carbon\CarbonInterface;

final readonly class SalesPlayReceiptData
{
    /**
     * @param  array<int, SalesPlayReceiptItemData>  $items
     * @param  array<int, SalesPlayPaymentData>  $payments
     * @param  array<string, mixed>  $raw  The original API payload, stored as-is in receipts.raw_json.
     */
    public function __construct(
        public string $salesplayReceiptId,
        public ?string $receiptNumber,
        public CarbonInterface $transactionDate,
        public float $subtotal,
        public float $discount,
        public float $tax,
        public float $total,
        public ?string $paymentStatus,
        public ?SalesPlayCustomerData $customer,
        public array $items,
        public array $payments,
        public array $raw,
    ) {}
}
