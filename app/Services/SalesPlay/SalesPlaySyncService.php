<?php

namespace App\Services\SalesPlay;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\Shift;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\DTO\SalesPlayCustomerData;
use App\Services\SalesPlay\DTO\SalesPlayReceiptData;
use App\Services\SalesPlay\DTO\SalesPlayShiftData;
use App\Services\SalesPlay\DTO\SalesPlayStockInData;
use App\Services\SalesPlay\DTO\SalesPlayStockLevelData;
use App\Services\SalesPlay\DTO\SalesPlaySyncResult;
use App\Services\SalesPlay\Exceptions\SalesPlayApiException;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
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
                $this->discoverShopId($account, $receiptData);

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
        $this->syncShifts($account);

        return new SalesPlaySyncResult(synced: $synced, skipped: $skipped);
    }

    /**
     * Every SalesPlay receipt carries the shop_id it belongs to, so an
     * account created without one (the merchant couldn't find it in
     * SalesPlay's own dashboard) picks it up automatically as soon as its
     * first receipt syncs — no manual lookup needed. Auth only ever depends
     * on the API token, so this is purely for completeness/future API
     * calls that may want it, not a requirement for syncing to work.
     */
    private function discoverShopId(SalesplayAccount $account, SalesPlayReceiptData $data): void
    {
        if ($account->salesplay_shop_id !== null) {
            return;
        }

        $shopId = $data->raw['shop_id'] ?? null;

        if (! is_string($shopId) || $shopId === '') {
            return;
        }

        $account->update(['salesplay_shop_id' => $shopId]);
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

        try {
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
        } catch (UniqueConstraintViolationException) {
            // Already inserted by another run that raced with this one (the
            // per-account lock should prevent this, but the exists() check
            // above isn't atomic with the insert, so this stays as a
            // last-resort guard) — treat it the same as the exists() check
            // finding it: already synced, nothing more to do.
        }
    }

    /**
     * Syncs shifts (terminal open/close with cash reconciliation). Always a
     * full paginated fetch — see fetchShifts() on the interface for why this
     * can't be incremental like receipts/stock-ins.
     *
     * @throws SalesPlayApiException
     */
    private function syncShifts(SalesplayAccount $account): void
    {
        $cursor = null;
        $page = 0;

        do {
            if (++$page > self::MAX_PAGES) {
                throw new SalesPlayApiException(
                    "SalesPlay shift sync for account [{$account->id}] exceeded ".self::MAX_PAGES.' pages; aborting.'
                );
            }

            $result = $this->client->fetchShifts(
                apiToken: $account->api_token,
                cursor: $cursor,
            );

            foreach ($result->items as $shiftData) {
                $this->storeShift($account, $shiftData);
            }

            $cursor = $result->nextCursor;
        } while ($result->hasMore);
    }

    /**
     * Unlike receipts/stock-ins (immutable once synced), a shift can change
     * after it was first seen — it starts "open" and gets updated in place
     * once the cashier closes it out — so this upserts on every sync instead
     * of skipping records that already exist.
     */
    private function storeShift(SalesplayAccount $account, SalesPlayShiftData $data): void
    {
        $attributes = [
            'company_id' => $account->company_id,
            'pos_device_id' => $data->posDeviceId,
            'opened_at' => $data->openedAt,
            'closed_at' => $data->closedAt,
            'opened_by_employee' => $data->openedByEmployee,
            'closed_by_employee' => $data->closedByEmployee,
            'starting_cash' => $data->startingCash,
            'cash_payments' => $data->cashPayments,
            'cash_refunds' => $data->cashRefunds,
            'paid_in' => $data->paidIn,
            'paid_out' => $data->paidOut,
            'expected_cash' => $data->expectedCash,
            'actual_cash' => $data->actualCash,
            'gross_sales' => $data->grossSales,
            'refunds' => $data->refunds,
            'discounts' => $data->discounts,
            'net_sales' => $data->netSales,
            'tip' => $data->tip,
            'surcharge' => $data->surcharge,
            'raw_json' => $data->raw,
        ];

        try {
            Shift::withoutGlobalScopes()->updateOrCreate(
                [
                    'salesplay_account_id' => $account->id,
                    'salesplay_shift_id' => $data->salesplayShiftId,
                ],
                $attributes
            );
        } catch (UniqueConstraintViolationException) {
            // Already inserted by another run that raced with this one (the
            // per-account lock should prevent this, but this stays as a
            // last-resort guard) — fall back to a plain update.
            Shift::withoutGlobalScopes()
                ->where('salesplay_account_id', $account->id)
                ->where('salesplay_shift_id', $data->salesplayShiftId)
                ->update($attributes);
        }
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

        try {
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
        } catch (UniqueConstraintViolationException) {
            // Already inserted by another run that raced with this one (the
            // per-account lock should prevent this, but the exists() check
            // above isn't atomic with the insert, so this stays as a
            // last-resort guard) — treat it the same as the exists() check
            // finding it: already synced, nothing more to do.
            return false;
        }

        return true;
    }

    private function resolveCustomer(int $companyId, SalesPlayCustomerData $data): Customer
    {
        // A company can run several SalesPlay accounts (multi-branch) whose
        // syncs aren't locked against each other, so two receipts for the
        // same customer can still race here even though single-account
        // races are now prevented — fall back to a plain lookup rather than
        // letting the race abort the receipt this customer belongs to.
        try {
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
        } catch (UniqueConstraintViolationException) {
            return Customer::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('salesplay_customer_id', $data->salesplayCustomerId)
                ->firstOrFail();
        }
    }

    private function resolveProduct(int $companyId, ?string $salesplayProductId, string $productName): ?Product
    {
        if ($salesplayProductId === null) {
            return null;
        }

        try {
            return Product::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'salesplay_product_id' => $salesplayProductId,
                ],
                [
                    'name' => $productName,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            return Product::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('salesplay_product_id', $salesplayProductId)
                ->first();
        }
    }
}
