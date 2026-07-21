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
 * HTTP client for the real SalesPlay Developer API, confirmed against
 * https://api.salesplaypos.com/v1.0 (see SalesPlay's official Postman
 * collection and API docs). Notable quirks of the real API, as opposed to
 * a conventional REST API:
 *
 * - Auth is a custom `Token: Bearer <token>` header, not the standard
 *   `Authorization` header. The token is generated per-merchant from the
 *   SalesPlay Backoffice (Integrations > Access token) — it is not an
 *   OAuth access token, even though the API also exposes an unrelated
 *   OAuth 2.0 authorization-code flow for a different integration path.
 * - GET /receipts takes its filters as a JSON request body rather than
 *   query parameters. `created_at_min` is a required field.
 * - Pagination is cursor-based via a top-level `cursor` field in every
 *   response; there is no explicit "has more pages" flag, so we keep
 *   paging until a page comes back empty or the cursor stops advancing.
 * - Receipts have no separate opaque ID: `receipt_number` (e.g. "10-0003")
 *   is the identifier, and it is only unique per shop.
 */
class SalesPlayApiClient implements SalesPlayApiClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout,
    ) {}

    public function fetchReceipts(
        string $shopId,
        string $apiToken,
        ?CarbonInterface $since,
        ?string $cursor,
    ): SalesPlayReceiptPage {
        $since ??= Carbon::create(2000, 1, 1);

        try {
            $response = Http::baseUrl(rtrim($this->baseUrl, '/'))
                ->withHeaders([
                    'Token' => "Bearer {$apiToken}",
                    'User-Agent' => 'DynoPOS-CloudReport/1.0',
                ])
                ->timeout($this->timeout)
                ->acceptJson()
                ->send('GET', '/receipts', ['json' => array_filter([
                    'shop_id' => $shopId,
                    'created_at_min' => $since->format('Y-m-d H:i:s'),
                    'created_at_max' => now()->format('Y-m-d H:i:s'),
                    'cursor' => $cursor,
                ])]);
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

        return $this->mapResponseToPage($response->json(), previousCursor: $cursor);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapResponseToPage(array $payload, ?string $previousCursor): SalesPlayReceiptPage
    {
        $receipts = $payload['receipts'] ?? [];

        $items = array_values(array_map(
            fn (array $receipt) => $this->mapReceipt($receipt),
            array_filter($receipts, fn (array $receipt) => ($receipt['receipt_delete_status'] ?? false) === false)
        ));

        $nextCursor = $payload['cursor'] ?? null;

        return new SalesPlayReceiptPage(
            items: $items,
            hasMore: $receipts !== [] && $nextCursor !== null && $nextCursor !== $previousCursor,
            nextCursor: $nextCursor,
        );
    }

    /**
     * @param  array<string, mixed>  $receipt
     */
    private function mapReceipt(array $receipt): SalesPlayReceiptData
    {
        $items = array_map(fn (array $item) => $this->mapItem($item), $receipt['line_products'] ?? []);
        $subtotal = round(array_sum(array_map(fn (SalesPlayReceiptItemData $item) => $item->total, $items)), 2);

        return new SalesPlayReceiptData(
            salesplayReceiptId: (string) $receipt['receipt_number'],
            receiptNumber: $receipt['receipt_number'] ?? null,
            transactionDate: Carbon::parse($receipt['receipt_date_time']),
            subtotal: $subtotal,
            discount: (float) ($receipt['total_discount'] ?? 0),
            tax: (float) ($receipt['total_tax'] ?? 0),
            total: (float) ($receipt['total_money'] ?? 0),
            // /receipts has no payment_status field; voids and refunds are separate
            // SalesPlay endpoints (void_receipts, credit_note_and_refund) we don't sync.
            paymentStatus: 'paid',
            items: $items,
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
            productName: $item['product_name'] ?? 'Unknown product',
            quantity: (float) ($item['quantity'] ?? 1),
            unitPrice: (float) ($item['price'] ?? 0),
            discount: (float) ($item['total_discount'] ?? 0),
            total: (float) ($item['total_money'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function mapPayment(array $payment): SalesPlayPaymentData
    {
        return new SalesPlayPaymentData(
            paymentMethod: $payment['payment_type'] ?? 'unknown',
            amount: (float) ($payment['money_amount'] ?? 0),
        );
    }
}
