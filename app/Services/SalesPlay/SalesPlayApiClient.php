<?php

namespace App\Services\SalesPlay;

use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\DTO\SalesPlayCustomerData;
use App\Services\SalesPlay\DTO\SalesPlayPaymentData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptItemData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptPage;
use App\Services\SalesPlay\DTO\SalesPlayStockInData;
use App\Services\SalesPlay\DTO\SalesPlayStockInItemData;
use App\Services\SalesPlay\DTO\SalesPlayStockInPage;
use App\Services\SalesPlay\DTO\SalesPlayStockLevelData;
use App\Services\SalesPlay\DTO\SalesPlayStockLevelPage;
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
    /**
     * Keeps each page's query cheap on SalesPlay's end — a full-history
     * first sync with no limit risked the server timing out trying to
     * return decades of receipts in one response.
     */
    private const PAGE_SIZE = 100;

    /**
     * SalesPlay is a Malaysian POS product and its created_at_min/max filters
     * appear to be interpreted in local Malaysia time, not UTC — sending UTC
     * timestamps silently cut off same-day receipts created less than 8
     * hours before the request (verified by comparing our sync results
     * against SalesPlay's own Backoffice dashboard for "today").
     */
    private const API_TIMEZONE = 'Asia/Kuala_Lumpur';

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
                ->withHeaders($this->headers($apiToken))
                ->timeout($this->timeout)
                ->send('GET', '/receipts', ['json' => array_filter([
                    'shop_id' => $shopId,
                    'created_at_min' => $since->clone()->timezone(self::API_TIMEZONE)->format('Y-m-d H:i:s'),
                    'created_at_max' => now()->timezone(self::API_TIMEZONE)->format('Y-m-d H:i:s'),
                    'limit' => self::PAGE_SIZE,
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

    public function fetchStockLevels(
        string $shopId,
        string $apiToken,
        ?string $cursor,
    ): SalesPlayStockLevelPage {
        try {
            $response = Http::baseUrl(rtrim($this->baseUrl, '/'))
                ->withHeaders($this->headers($apiToken))
                ->timeout($this->timeout)
                ->send('GET', '/inventory', ['json' => array_filter([
                    'shop_id' => $shopId,
                    'limit' => self::PAGE_SIZE,
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

        $payload = $response->json();
        $levels = $payload['inventory_levels'] ?? [];
        $nextCursor = $payload['cursor'] ?? null;

        return new SalesPlayStockLevelPage(
            items: array_values(array_map(fn (array $level) => $this->mapStockLevel($level), $levels)),
            hasMore: $levels !== [] && $nextCursor !== null && $nextCursor !== $cursor,
            nextCursor: $nextCursor,
        );
    }

    /**
     * The /grn endpoint has never returned a non-empty grn_list against the
     * real API during development (the test shop has no manual stock-in
     * entries yet), so these item/field names are a best guess based on
     * SalesPlay's "create GRN" request body shape rather than a confirmed
     * response. Mapping is deliberately defensive (nullable fallbacks) so a
     * wrong guess produces incomplete data instead of a crash — this should
     * be verified and corrected once a real shop has GRN data to inspect.
     *
     * created_at_min/max filtering is not sent because the real filter field
     * name for GRN dates is unconfirmed; instead every sync pages through
     * the full grn list and relies on idempotent storage to skip records
     * already synced.
     */
    public function fetchStockIns(
        string $shopId,
        string $apiToken,
        ?CarbonInterface $since,
        ?string $cursor,
    ): SalesPlayStockInPage {
        try {
            $response = Http::baseUrl(rtrim($this->baseUrl, '/'))
                ->withHeaders($this->headers($apiToken))
                ->timeout($this->timeout)
                ->send('GET', '/grn', ['json' => array_filter([
                    'shop_id' => $shopId,
                    'limit' => self::PAGE_SIZE,
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

        $payload = $response->json();
        $grns = $payload['grn_list'] ?? [];
        $nextCursor = $payload['cursor'] ?? null;

        return new SalesPlayStockInPage(
            items: array_values(array_map(fn (array $grn) => $this->mapStockIn($grn), $grns)),
            hasMore: $grns !== [] && $nextCursor !== null && $nextCursor !== $cursor,
            nextCursor: $nextCursor,
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $apiToken): array
    {
        return [
            'Token' => "Bearer {$apiToken}",
            'User-Agent' => 'DynoPOS-CloudReport/1.0',
            // SalesPlay's server uses Apache content negotiation and only has a
            // text/html-typed variant registered; asking strictly for
            // application/json (the Laravel default) makes Apache itself
            // reject the request with a 406 before the app ever sees it.
            'Accept' => '*/*',
        ];
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
            customer: $this->mapCustomer($receipt['customer'] ?? []),
            items: $items,
            payments: array_map(fn (array $payment) => $this->mapPayment($payment), $receipt['payments'] ?? []),
            raw: $receipt,
        );
    }

    /**
     * The API nests the customer as a single-element array. SalesPlay
     * assigns every receipt a customer — walk-in sales get its generic
     * default profile (name "N/A") rather than leaving this empty.
     *
     * @param  array<int, array<string, mixed>>  $customers
     */
    private function mapCustomer(array $customers): ?SalesPlayCustomerData
    {
        $customer = $customers[0] ?? null;

        if ($customer === null || ! isset($customer['id'])) {
            return null;
        }

        return new SalesPlayCustomerData(
            salesplayCustomerId: (string) $customer['id'],
            name: $customer['name'] ?? 'N/A',
            email: $customer['email'] ?: null,
            phone: $customer['phone_number'] ?: null,
            address: $customer['address'] ?: null,
            city: $customer['city'] ?: null,
            region: $customer['region'] ?: null,
            postalCode: $customer['postal_code'] ?: null,
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

    /**
     * @param  array<string, mixed>  $level
     */
    private function mapStockLevel(array $level): SalesPlayStockLevelData
    {
        return new SalesPlayStockLevelData(
            salesplayProductId: (string) $level['product_id'],
            productCode: $level['product_code'] ?? null,
            quantityOnHand: (float) ($level['in_stock'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $grn
     */
    private function mapStockIn(array $grn): SalesPlayStockInData
    {
        $items = array_map(fn (array $item) => $this->mapStockInItem($item), $grn['items'] ?? []);
        $total = round(array_sum(array_map(fn (SalesPlayStockInItemData $item) => $item->total, $items)), 2);

        return new SalesPlayStockInData(
            salesplayGrnId: (string) ($grn['id'] ?? $grn['grn_id'] ?? $grn['grn_number']),
            supplierName: $grn['supplier_name'] ?? null,
            invoiceNo: $grn['supplier_invoice_no'] ?? null,
            receivedAt: isset($grn['grn_date']) ? Carbon::parse($grn['grn_date']) : now(),
            total: (float) ($grn['grn_total'] ?? $total),
            items: $items,
            raw: $grn,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mapStockInItem(array $item): SalesPlayStockInItemData
    {
        $quantity = (float) ($item['qty'] ?? $item['quantity'] ?? 0);
        $unitCost = (float) ($item['unit_cost'] ?? 0);

        return new SalesPlayStockInItemData(
            salesplayProductId: isset($item['product_id']) ? (string) $item['product_id'] : null,
            productName: $item['product_name'] ?? 'Unknown product',
            quantity: $quantity,
            unitCost: $unitCost,
            total: (float) ($item['total_unit_cost'] ?? round($quantity * $unitCost, 2)),
        );
    }
}
