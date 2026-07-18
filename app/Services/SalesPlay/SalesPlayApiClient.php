<?php

namespace App\Services\SalesPlay;

use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\DTO\SalesPlayPaymentData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptItemData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptPage;
use App\Services\SalesPlay\Exceptions\SalesPlayApiException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * HTTP client for the real SalesPlay API.
 *
 * IMPORTANT: the exact endpoint path and request/response shape below are
 * provisional best-guesses (a conventional paginated REST resource), NOT
 * confirmed against real SalesPlay API documentation. Nothing in the app
 * routes through this class today: SalesPlayServiceProvider only binds it
 * when `services.salesplay.base_url` is configured, otherwise the mock
 * client (SalesPlayMockApiClient) is used. Once the real API docs are
 * available, only this class needs to change — update the request in
 * fetchReceipts() and the field mapping in mapReceipt()/mapItem()/mapPayment().
 */
class SalesPlayApiClient implements SalesPlayApiClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiVersion,
        private readonly int $timeout,
    ) {}

    public function fetchReceipts(
        string $shopId,
        string $apiToken,
        ?CarbonInterface $since,
        ?string $cursor,
    ): SalesPlayReceiptPage {
        try {
            $response = Http::baseUrl(rtrim($this->baseUrl, '/'))
                ->withToken($apiToken)
                ->timeout($this->timeout)
                ->acceptJson()
                ->get("/{$this->apiVersion}/shops/{$shopId}/receipts", array_filter([
                    'since' => $since?->toIso8601String(),
                    'cursor' => $cursor,
                ]));
        } catch (Throwable $e) {
            throw new SalesPlayApiException(
                "SalesPlay API request failed for shop [{$shopId}]: {$e->getMessage()}", previous: $e
            );
        }

        if ($response->failed()) {
            throw new SalesPlayApiException(
                "SalesPlay API returned HTTP {$response->status()} for shop [{$shopId}]: {$response->body()}"
            );
        }

        return $this->mapResponseToPage($response->json());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapResponseToPage(array $payload): SalesPlayReceiptPage
    {
        $items = array_map(
            fn (array $receipt) => $this->mapReceipt($receipt),
            $payload['data'] ?? []
        );

        return new SalesPlayReceiptPage(
            items: $items,
            hasMore: (bool) ($payload['has_more'] ?? false),
            nextCursor: $payload['next_cursor'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $receipt
     */
    private function mapReceipt(array $receipt): SalesPlayReceiptData
    {
        return new SalesPlayReceiptData(
            salesplayReceiptId: (string) $receipt['id'],
            receiptNumber: $receipt['receipt_number'] ?? null,
            transactionDate: Carbon::parse($receipt['transaction_date']),
            subtotal: (float) ($receipt['subtotal'] ?? 0),
            discount: (float) ($receipt['discount'] ?? 0),
            tax: (float) ($receipt['tax'] ?? 0),
            total: (float) ($receipt['total'] ?? 0),
            paymentStatus: $receipt['payment_status'] ?? null,
            items: array_map(fn (array $item) => $this->mapItem($item), $receipt['items'] ?? []),
            payments: array_map(fn (array $payment) => $this->mapPayment($payment), $receipt['payments'] ?? []),
            raw: $receipt,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mapItem(array $item): SalesPlayReceiptItemData
    {
        return new SalesPlayReceiptItemData(
            salesplayProductId: isset($item['product_id']) ? (string) $item['product_id'] : null,
            productName: $item['name'] ?? 'Unknown product',
            quantity: (float) ($item['quantity'] ?? 1),
            unitPrice: (float) ($item['unit_price'] ?? 0),
            discount: (float) ($item['discount'] ?? 0),
            total: (float) ($item['total'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function mapPayment(array $payment): SalesPlayPaymentData
    {
        return new SalesPlayPaymentData(
            paymentMethod: $payment['method'] ?? 'unknown',
            amount: (float) ($payment['amount'] ?? 0),
        );
    }
}
