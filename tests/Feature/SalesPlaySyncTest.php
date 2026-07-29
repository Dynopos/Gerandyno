<?php

namespace Tests\Feature;

use App\Jobs\SyncSalesPlayAccountJob;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\StockIn;
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
use App\Services\SalesPlay\SalesPlaySyncService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fills in the stock-related interface methods with empty pages so the
 * anonymous fake clients below only need to define fetchReceipts()
 * behaviour relevant to what each test is actually checking.
 */
trait FakesEmptySalesPlayStockData
{
    public function fetchStockLevels(?string $shopId, string $apiToken, ?string $cursor): SalesPlayStockLevelPage
    {
        return new SalesPlayStockLevelPage(items: [], hasMore: false, nextCursor: null);
    }

    public function fetchStockIns(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayStockInPage
    {
        return new SalesPlayStockInPage(items: [], hasMore: false, nextCursor: null);
    }
}

/**
 * Fills in fetchReceipts() with an empty page so the anonymous fake clients
 * used by the stock-focused tests only need to define stock behaviour.
 */
trait FakesEmptySalesPlayReceipts
{
    public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
    {
        return new SalesPlayReceiptPage(items: [], hasMore: false, nextCursor: null);
    }
}

class SalesPlaySyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeReceipt(string $id): SalesPlayReceiptData
    {
        return new SalesPlayReceiptData(
            salesplayReceiptId: $id,
            receiptNumber: '1000',
            transactionDate: now(),
            subtotal: 100,
            discount: 0,
            tax: 6,
            total: 106,
            paymentStatus: 'paid',
            customer: new SalesPlayCustomerData(
                salesplayCustomerId: 'cust-1',
                name: 'Ali Bin Abu',
                email: 'ali@example.com',
                phone: '012-3456789',
                address: null,
                city: null,
                region: null,
                postalCode: null,
            ),
            items: [
                new SalesPlayReceiptItemData(
                    salesplayProductId: 'p-1',
                    productName: 'Nasi Lemak',
                    quantity: 2,
                    unitPrice: 50,
                    discount: 0,
                    total: 100,
                ),
            ],
            payments: [
                new SalesPlayPaymentData(paymentMethod: 'cash', amount: 106),
            ],
            raw: ['id' => $id],
        );
    }

    public function test_sync_stores_new_receipts_with_items_and_payments(): void
    {
        $account = SalesplayAccount::factory()->create(['last_synced_at' => null]);

        $fake = new class($this->makeReceipt('rcpt-1'), $this->makeReceipt('rcpt-2')) implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayStockData;

            private array $receipts;

            public function __construct(SalesPlayReceiptData ...$receipts)
            {
                $this->receipts = $receipts;
            }

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                return new SalesPlayReceiptPage(items: $this->receipts, hasMore: false, nextCursor: null);
            }
        };

        $result = (new SalesPlaySyncService($fake))->sync($account);

        $this->assertSame(2, $result->synced);
        $this->assertSame(0, $result->skipped);
        $this->assertSame(2, Receipt::withoutGlobalScopes()->count());
        $this->assertSame(2, ReceiptItem::count());
        $this->assertSame(2, Payment::count());

        $account->refresh();
        $this->assertNotNull($account->last_synced_at);
        $this->assertSame('success', $account->last_sync_status);
    }

    public function test_sync_succeeds_for_an_account_with_no_shop_id_set(): void
    {
        // salesplay_shop_id is optional at creation (the merchant may not
        // have found it yet), so the sync layer must tolerate null instead
        // of crashing with a TypeError against a non-nullable parameter.
        $account = SalesplayAccount::factory()->create(['last_synced_at' => null, 'salesplay_shop_id' => null]);

        $fake = new class implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayStockData;

            public ?string $receivedShopId = 'not called yet';

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                $this->receivedShopId = $shopId;

                return new SalesPlayReceiptPage(items: [], hasMore: false, nextCursor: null);
            }
        };

        $result = (new SalesPlaySyncService($fake))->sync($account);

        $this->assertSame(0, $result->synced);
        $this->assertNull($fake->receivedShopId);

        $account->refresh();
        $this->assertSame('success', $account->last_sync_status);
    }

    public function test_sync_skips_receipts_that_already_exist(): void
    {
        $account = SalesplayAccount::factory()->create(['last_synced_at' => null]);

        Receipt::factory()->create([
            'company_id' => $account->company_id,
            'salesplay_account_id' => $account->id,
            'salesplay_receipt_id' => 'rcpt-1',
        ]);

        $fake = new class($this->makeReceipt('rcpt-1')) implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayStockData;

            public function __construct(private SalesPlayReceiptData $receipt) {}

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                return new SalesPlayReceiptPage(items: [$this->receipt], hasMore: false, nextCursor: null);
            }
        };

        $result = (new SalesPlaySyncService($fake))->sync($account);

        $this->assertSame(0, $result->synced);
        $this->assertSame(1, $result->skipped);
        $this->assertSame(1, Receipt::withoutGlobalScopes()->where('salesplay_receipt_id', 'rcpt-1')->count());
    }

    public function test_sync_follows_pagination_across_multiple_pages(): void
    {
        $account = SalesplayAccount::factory()->create(['last_synced_at' => null]);

        $page1 = [$this->makeReceipt('rcpt-1'), $this->makeReceipt('rcpt-2')];
        $page2 = [$this->makeReceipt('rcpt-3')];

        $fake = new class($page1, $page2) implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayStockData;

            public function __construct(private array $page1, private array $page2) {}

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                if ($cursor === null) {
                    return new SalesPlayReceiptPage(items: $this->page1, hasMore: true, nextCursor: 'page-2');
                }

                return new SalesPlayReceiptPage(items: $this->page2, hasMore: false, nextCursor: null);
            }
        };

        $result = (new SalesPlaySyncService($fake))->sync($account);

        $this->assertSame(3, $result->synced);
        $this->assertSame(3, Receipt::withoutGlobalScopes()->count());
    }

    public function test_sync_marks_account_failed_and_does_not_advance_last_synced_at_on_error(): void
    {
        $originalSyncedAt = now()->subDay()->startOfSecond();
        $account = SalesplayAccount::factory()->create(['last_synced_at' => $originalSyncedAt]);

        $fake = new class implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayStockData;

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                throw new SalesPlayApiException('SalesPlay is down');
            }
        };

        $job = new SyncSalesPlayAccountJob($account);

        try {
            $job->handle(new SalesPlaySyncService($fake));
            $this->fail('Expected SalesPlayApiException to be rethrown.');
        } catch (SalesPlayApiException) {
            // expected
        }

        $account->refresh();
        $this->assertSame('failed', $account->last_sync_status);
        $this->assertNotNull($account->last_sync_error);
        $this->assertTrue($account->last_synced_at->equalTo($originalSyncedAt));
    }

    public function test_command_isolates_failure_of_one_account_from_another(): void
    {
        $goodCompany = Company::factory()->create();
        $badCompany = Company::factory()->create();

        $goodAccount = SalesplayAccount::factory()->create([
            'company_id' => $goodCompany->id,
            'salesplay_shop_id' => 'good-shop',
            'last_synced_at' => null,
        ]);

        $badAccount = SalesplayAccount::factory()->create([
            'company_id' => $badCompany->id,
            'salesplay_shop_id' => 'bad-shop',
            'last_synced_at' => null,
        ]);

        $fake = new class($this->makeReceipt('rcpt-good')) implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayStockData;

            public function __construct(private SalesPlayReceiptData $receipt) {}

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                if ($shopId === 'bad-shop') {
                    throw new SalesPlayApiException('boom');
                }

                return new SalesPlayReceiptPage(items: [$this->receipt], hasMore: false, nextCursor: null);
            }
        };

        $this->app->bind(SalesPlayApiClientInterface::class, fn () => $fake);

        $this->artisan('salesplay:sync', ['--now' => true])->assertSuccessful();

        $goodAccount->refresh();
        $badAccount->refresh();

        $this->assertSame('success', $goodAccount->last_sync_status);
        $this->assertSame('failed', $badAccount->last_sync_status);
        $this->assertSame(1, Receipt::withoutGlobalScopes()->where('salesplay_account_id', $goodAccount->id)->count());
        $this->assertSame(0, Receipt::withoutGlobalScopes()->where('salesplay_account_id', $badAccount->id)->count());
    }

    public function test_sync_refreshes_product_stock_levels(): void
    {
        $account = SalesplayAccount::factory()->create(['last_synced_at' => null]);

        $existingProduct = Product::withoutGlobalScopes()->create([
            'company_id' => $account->company_id,
            'salesplay_product_id' => 'p-1',
            'name' => 'Existing Product',
            'stock_on_hand' => 0,
        ]);

        $fake = new class implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayReceipts;

            public function fetchStockLevels(?string $shopId, string $apiToken, ?string $cursor): SalesPlayStockLevelPage
            {
                return new SalesPlayStockLevelPage(
                    items: [
                        new SalesPlayStockLevelData(salesplayProductId: 'p-1', productCode: '1000', quantityOnHand: 42),
                        new SalesPlayStockLevelData(salesplayProductId: 'p-2', productCode: '1001', quantityOnHand: 7),
                    ],
                    hasMore: false,
                    nextCursor: null,
                );
            }

            public function fetchStockIns(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayStockInPage
            {
                return new SalesPlayStockInPage(items: [], hasMore: false, nextCursor: null);
            }
        };

        (new SalesPlaySyncService($fake))->sync($account);

        $existingProduct->refresh();
        $this->assertSame('42.00', $existingProduct->stock_on_hand);
        $this->assertNotNull($existingProduct->stock_synced_at);

        $newProduct = Product::withoutGlobalScopes()->where('salesplay_product_id', 'p-2')->first();
        $this->assertNotNull($newProduct);
        $this->assertSame('7.00', $newProduct->stock_on_hand);
    }

    public function test_sync_stores_stock_ins_and_skips_ones_already_synced(): void
    {
        $account = SalesplayAccount::factory()->create(['last_synced_at' => null]);

        StockIn::withoutGlobalScopes()->create([
            'company_id' => $account->company_id,
            'salesplay_account_id' => $account->id,
            'salesplay_grn_id' => 'grn-existing',
            'received_at' => now(),
            'total' => 10,
        ]);

        $stockInData = new SalesPlayStockInData(
            salesplayGrnId: 'grn-new',
            supplierName: 'Acme Supplies',
            invoiceNo: 'INV-001',
            receivedAt: now(),
            total: 150.0,
            items: [
                new SalesPlayStockInItemData(
                    salesplayProductId: 'p-1',
                    productName: 'Sugar 1kg',
                    quantity: 10,
                    unitCost: 15.0,
                    total: 150.0,
                ),
            ],
            raw: ['id' => 'grn-new'],
        );

        $duplicateStockInData = new SalesPlayStockInData(
            salesplayGrnId: 'grn-existing',
            supplierName: null,
            invoiceNo: null,
            receivedAt: now(),
            total: 10,
            items: [],
            raw: [],
        );

        $fake = new class($stockInData, $duplicateStockInData) implements SalesPlayApiClientInterface
        {
            use FakesEmptySalesPlayReceipts;

            public function __construct(
                private SalesPlayStockInData $new,
                private SalesPlayStockInData $duplicate,
            ) {}

            public function fetchStockLevels(?string $shopId, string $apiToken, ?string $cursor): SalesPlayStockLevelPage
            {
                return new SalesPlayStockLevelPage(items: [], hasMore: false, nextCursor: null);
            }

            public function fetchStockIns(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayStockInPage
            {
                return new SalesPlayStockInPage(items: [$this->new, $this->duplicate], hasMore: false, nextCursor: null);
            }
        };

        (new SalesPlaySyncService($fake))->sync($account);

        $this->assertSame(2, StockIn::withoutGlobalScopes()->where('salesplay_account_id', $account->id)->count());

        $stored = StockIn::withoutGlobalScopes()->where('salesplay_grn_id', 'grn-new')->first();
        $this->assertNotNull($stored);
        $this->assertSame('Acme Supplies', $stored->supplier_name);
        $this->assertSame(1, $stored->items()->count());
        $this->assertSame('Sugar 1kg', $stored->items()->first()->product_name);
    }
}
