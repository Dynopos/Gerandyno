<?php

namespace App\Services\SalesPlay;

use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\DTO\SalesPlayCustomerData;
use App\Services\SalesPlay\DTO\SalesPlayPaymentData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptItemData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptPage;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Fake SalesPlay API client used until the real endpoint is confirmed
 * (bound automatically by SalesPlayServiceProvider whenever
 * services.salesplay.base_url is empty). Generates realistic-looking
 * receipts with items and payments so the sync pipeline, dashboard, and
 * reports can be built and tested end-to-end without a live API.
 *
 * Simulates a first full sync returning several pages of receipts, and
 * incremental syncs (when $since is set) returning a handful of new
 * receipts, mirroring how a real paginated "since" API would behave.
 */
class SalesPlayMockApiClient implements SalesPlayApiClientInterface
{
    private const ITEMS_PER_PAGE = 5;

    private const FIRST_SYNC_PAGES = 3;

    public function fetchReceipts(
        string $shopId,
        string $apiToken,
        ?CarbonInterface $since,
        ?string $cursor,
    ): SalesPlayReceiptPage {
        $page = $cursor !== null ? (int) $cursor : 1;
        $totalPages = $since === null ? self::FIRST_SYNC_PAGES : 1;
        $countThisPage = $since === null ? self::ITEMS_PER_PAGE : random_int(0, 3);

        $items = [];
        for ($i = 0; $i < $countThisPage; $i++) {
            $items[] = $this->fakeReceipt($shopId, $since);
        }

        $hasMore = $page < $totalPages;

        return new SalesPlayReceiptPage(
            items: $items,
            hasMore: $hasMore,
            nextCursor: $hasMore ? (string) ($page + 1) : null,
        );
    }

    private function fakeReceipt(string $shopId, ?CarbonInterface $since): SalesPlayReceiptData
    {
        $transactionDate = $since
            ? Carbon::instance(fake()->dateTimeBetween($since, 'now'))
            : Carbon::instance(fake()->dateTimeBetween('-90 days', 'now'));

        $items = collect(range(1, random_int(1, 4)))
            ->map(fn () => $this->fakeItem())
            ->all();

        $subtotal = round(array_sum(array_map(fn (SalesPlayReceiptItemData $item) => $item->total, $items)), 2);
        $tax = round($subtotal * 0.06, 2);
        $total = round($subtotal + $tax, 2);

        return new SalesPlayReceiptData(
            salesplayReceiptId: 'mock-'.$shopId.'-'.Str::uuid(),
            receiptNumber: (string) fake()->unique()->numberBetween(100000, 999999),
            transactionDate: $transactionDate,
            subtotal: $subtotal,
            discount: 0.0,
            tax: $tax,
            total: $total,
            paymentStatus: 'paid',
            customer: $this->fakeCustomer(),
            items: $items,
            payments: [
                new SalesPlayPaymentData(
                    paymentMethod: fake()->randomElement(['cash', 'card', 'ewallet']),
                    amount: $total,
                ),
            ],
            raw: ['source' => 'mock'],
        );
    }

    private function fakeCustomer(): SalesPlayCustomerData
    {
        return new SalesPlayCustomerData(
            salesplayCustomerId: (string) fake()->numberBetween(1, 30),
            name: fake()->name(),
            email: fake()->safeEmail(),
            phone: fake()->phoneNumber(),
            address: fake()->streetAddress(),
            city: fake()->city(),
            region: fake()->state(),
            postalCode: fake()->postcode(),
        );
    }

    private function fakeItem(): SalesPlayReceiptItemData
    {
        $quantity = random_int(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 80);

        return new SalesPlayReceiptItemData(
            salesplayProductId: (string) fake()->numberBetween(1000, 1050),
            productName: fake()->words(2, true),
            quantity: $quantity,
            unitPrice: $unitPrice,
            discount: 0.0,
            total: round($quantity * $unitPrice, 2),
        );
    }
}
