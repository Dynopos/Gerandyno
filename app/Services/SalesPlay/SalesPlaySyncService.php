<?php

namespace App\Services\SalesPlay;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\DTO\SalesPlayCustomerData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptData;
use App\Services\SalesPlay\DTO\SalesPlayStockInData;
use App\Services\SalesPlay\DTO\SalesPlayStockLevelData;
use App\Services\SalesPlay\DTO\SalesPlaySyncResult;
use App\Services\SalesPlay\Exceptions\SalesPlayApiException;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Pulls new receipts for a single SalesPlay account and persists them.
 *
 * Sync is incremental (uses salesplay_accounts.last_synced_at as the "since"
 * cursor) and idempotent (skips any receipt whose salesplay_receipt_id
 * already exists for that account — receipt numbers are only unique per
 * shop, not globally), so it's always safe to re-run — including retrying
 * after a failure, which simply resumes from the last successfully synced
 * point.
 */
class SalesPlaySyncService
{
    /**
     * Safety net against a misbehaving/infinite pagination cursor.
     */
    private const MAX_PAGES = 500;

    public function __construct(
        private readonly SalesPlayApiClientInterface $client,
    ) {}

    /**
     * @throws SalesPlayApiException
     */
    public function sync(SalesplayAccount $account): SalesPlaySyncResult
    {
        $syncStartedAt = now();
        $since = $account->last_synced_at;
        $cursor = null;
        $synced = 0;
        $skipped = 0;
        $page = 0;

        do {
            if (++$page > self::MAX_PAGES) {
                throw new SalesPlayApiException(
                    "SalesPlay sync for account [{$account->id}] exceeded ".self::MAX_PAGES.' pages; aborting.'
                );
            }

            $result = $this->client->fetchReceipts(
                shopId: $account->salesplay_shop_id,
                apiToken: $account->api_token,
                since: $since,
                cursor: $cursor,
            );

            foreach ($result->items as $receiptData) {
                if ($this->storeReceipt($account, $receiptData)) {
                    $synced++;
                } else {
                    $skipped++;
                }
            }

            $cursor = $result->nextCursor;
        } while ($result->hasMore);

        $account->update([
            'last_synced_at' => $syncStartedAt,
            'last_sync_status' => 'success',
            'last_sync_error' => null,
        ]);

        $this->syncStockLevels($account);
        $this->syncStockIns($account, $since);

        return new SalesPlaySyncResult(synced: $synced, skipped: $skipped);
    }

    /**
     * Refreshes every product's current stock-on-hand. Unlike receipts, this
     * is a full snapshot rather than an incremental feed (the API has no
     * "since" concept for stock levels), so every sync pages through the
     * complete list.
     *
     * @throws SalesPlayApiException
     */
    private function syncStockLevels(SalesplayAccount $account): void
    {
        $cursor = null;
        $page = 0;

        do {
            if (++$page > self::MAX_PAGES) {
                throw new SalesPlayApiException(
                    "SalesPlay stock level sync for account [{$account->id}] exceeded ".self::MAX_PAGES.' pages; aborting.'
                );
            }

            $result = $this->client->fetchStockLevels(
                shopId: $account->salesplay_shop_id,
                apiToken: $account->api_token,
                cursor: $cursor,
            );

            foreach ($result->items as $level) {
                $this->storeStockLevel($account, $level);
            }

            $cursor = $result->nextCursor;
        } while ($result->hasMore);
    }

    private function storeStockLevel(SalesplayAccount $account, SalesPlayStockLevelData $data): void
    {
        $product = Product::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $account->company_id,
                'salesplay_product_id' => $data->salesplayProductId,
            ],
            [
                'name' => $data->productCode ?? "Product {$data->salesplayProductId}",
            ]
        );

        $product->update([
            'stock_on_hand' => $data->quantityOnHand,
            'stock_synced_at' => now(),
        ]);
    }

    /**
     * Syncs goods-received notes ("stock in"). Idempotent (skips any GRN
     * whose salesplay_grn_id already exists for that account), same as
     * receipts.
     *
     * @throws SalesPlayApiException
     */
    private function syncStockIns(SalesplayAccount $account, ?CarbonInterface $since): void
    {
        $cursor = null;
        $page = 0;

        do {
            if (++$page > self::MAX_PAGES) {
                throw new SalesPlayApiException(
                    "SalesPlay stock-in sync for account [{$account->id}] exceeded ".self::MAX_PAGES.' pages; aborting.'
                );
            }

            $result = $this->client->fetchStockIns(
                shopId: $account->salesplay_shop_id,
                apiToken: $account->api_token,
                since: $since,
                cursor: $cursor,
            );

            foreach ($result->items as $stockInData) {
                $this->storeStockIn($account, $stockInData);
            }

            $cursor = $result->nextCursor;
        } while ($result->hasMore);
    }

    private function storeStockIn(SalesplayAccount $account, SalesPlayStockInData $data): void
    {
        if (StockIn::withoutGlobalScopes()
            ->where('salesplay_account_id', $account->id)
            ->where('salesplay_grn_id', $data->salesplayGrnId)
            ->exists()) {
            return;
        }

        DB::transaction(function () use ($account, $data): void {
            $stockIn = StockIn::create([
                'company_id' => $account->company_id,
                'salesplay_account_id' => $account->id,
                'salesplay_grn_id' => $data->salesplayGrnId,
                'supplier_name' => $data->supplierName,
                'invoice_no' => $data->invoiceNo,
                'received_at' => $data->receivedAt,
                'total' => $data->total,
                'raw_json' => $data->raw,
            ]);

            foreach ($data->items as $item) {
                $product = $this->resolveProduct($account->company_id, $item->salesplayProductId, $item->productName);

                StockInItem::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id' => $product?->id,
                    'product_name' => $item->productName,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unitCost,
                    'total' => $item->total,
                ]);
            }
        });
    }

    /**
     * Returns true if the receipt was newly stored, false if it was already
     * synced (skipped as a duplicate).
     */
    private function storeReceipt(SalesplayAccount $account, SalesPlayReceiptData $data): bool
    {
        if (Receipt::withoutGlobalScopes()
            ->where('salesplay_account_id', $account->id)
            ->where('salesplay_receipt_id', $data->salesplayReceiptId)
            ->exists()) {
            return false;
        }

        DB::transaction(function () use ($account, $data): void {
            $customer = $data->customer ? $this->resolveCustomer($account->company_id, $data->customer) : null;

            $receipt = Receipt::create([
                'company_id' => $account->company_id,
                'salesplay_account_id' => $account->id,
                'customer_id' => $customer?->id,
                'salesplay_receipt_id' => $data->salesplayReceiptId,
                'receipt_number' => $data->receiptNumber,
                'transaction_date' => $data->transactionDate,
                'subtotal' => $data->subtotal,
                'discount' => $data->discount,
                'tax' => $data->tax,
                'total' => $data->total,
                'payment_status' => $data->paymentStatus,
                'raw_json' => $data->raw,
            ]);

            foreach ($data->items as $item) {
                $product = $this->resolveProduct($account->company_id, $item->salesplayProductId, $item->productName);

                ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'product_id' => $product?->id,
                    'product_name' => $item->productName,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unitPrice,
                    'discount' => $item->discount,
                    'total' => $item->total,
                ]);
            }

            foreach ($data->payments as $payment) {
                Payment::create([
                    'receipt_id' => $receipt->id,
                    'payment_method' => $payment->paymentMethod,
                    'amount' => $payment->amount,
                ]);
            }
        });

        return true;
    }

    private function resolveCustomer(int $companyId, SalesPlayCustomerData $data): Customer
    {
        return Customer::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $companyId,
                'salesplay_customer_id' => $data->salesplayCustomerId,
            ],
            [
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'address' => $data->address,
                'city' => $data->city,
                'region' => $data->region,
                'postal_code' => $data->postalCode,
            ]
        );
    }

    private function resolveProduct(int $companyId, ?string $salesplayProductId, string $productName): ?Product
    {
        if ($salesplayProductId === null) {
            return null;
        }

        return Product::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $companyId,
                'salesplay_product_id' => $salesplayProductId,
            ],
            [
                'name' => $productName,
            ]
        );
    }
}
