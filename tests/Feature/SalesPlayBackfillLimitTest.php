<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\DTO\SalesPlayReceiptPage;
use App\Services\SalesPlay\DTO\SalesPlayShiftPage;
use App\Services\SalesPlay\DTO\SalesPlayStockInPage;
use App\Services\SalesPlay\DTO\SalesPlayStockLevelPage;
use App\Services\SalesPlay\Exceptions\SalesPlayApiException;
use App\Services\SalesPlay\SalesPlaySyncService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SalesPlay hides past months behind its own subscription, so history that
 * DynoPOS has not already captured can become permanently unreachable. That
 * makes a full backfill (during a month the merchant is subscribed) worth
 * finishing — the page cap exists to stop a runaway pagination cursor, not
 * to cut a legitimate backfill short, and one outlet alone can ring up
 * several thousand receipts a month.
 */
class SalesPlayBackfillLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A client whose cursor never terminates — what a broken cursor looks
     * like, and what the cap is actually guarding against.
     */
    private function endlessClient(): SalesPlayApiClientInterface
    {
        return new class implements SalesPlayApiClientInterface
        {
            public int $calls = 0;

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                $this->calls++;

                return new SalesPlayReceiptPage(items: [], hasMore: true, nextCursor: 'next-'.$this->calls);
            }

            public function fetchStockLevels(?string $shopId, string $apiToken, ?string $cursor): SalesPlayStockLevelPage
            {
                return new SalesPlayStockLevelPage(items: [], hasMore: false, nextCursor: null);
            }

            public function fetchStockIns(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayStockInPage
            {
                return new SalesPlayStockInPage(items: [], hasMore: false, nextCursor: null);
            }

            public function fetchShifts(string $apiToken, ?string $cursor): SalesPlayShiftPage
            {
                return new SalesPlayShiftPage(items: [], hasMore: false, nextCursor: null);
            }
        };
    }

    private function account(): SalesplayAccount
    {
        return SalesplayAccount::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'last_synced_at' => null,
        ]);
    }

    public function test_a_runaway_cursor_is_still_stopped(): void
    {
        $client = $this->endlessClient();

        $this->expectException(SalesPlayApiException::class);

        (new SalesPlaySyncService($client, maxPages: 3))->sync($this->account());
    }

    public function test_the_cap_is_configurable_rather_than_fixed_in_code(): void
    {
        $client = $this->endlessClient();

        try {
            (new SalesPlaySyncService($client, maxPages: 7))->sync($this->account());
        } catch (SalesPlayApiException) {
            // Expected — this test is about where it stops, not that it does.
        }

        // Paged right up to the configured cap before giving up, so raising
        // the cap really does buy a longer backfill.
        $this->assertSame(7, $client->calls);
    }

    public function test_the_configured_cap_is_wired_through_the_container(): void
    {
        config(['services.salesplay.max_sync_pages' => 4]);

        $client = $this->endlessClient();
        $this->app->bind(SalesPlayApiClientInterface::class, fn () => $client);

        try {
            $this->app->make(SalesPlaySyncService::class)->sync($this->account());
        } catch (SalesPlayApiException) {
            // Expected.
        }

        $this->assertSame(4, $client->calls);
    }

    /**
     * The default has to clear a real shop's backfill: a busy outlet runs
     * several thousand receipts a month, and SalesPlay pages 100 at a time.
     */
    public function test_the_default_cap_covers_a_busy_shops_full_history(): void
    {
        $receiptsPerMonth = 4200;
        $monthsOfHistory = 30;
        $receiptsPerPage = 100;

        $pagesNeeded = (int) ceil($receiptsPerMonth * $monthsOfHistory / $receiptsPerPage);

        $this->assertGreaterThan(
            $pagesNeeded,
            (int) config('services.salesplay.max_sync_pages'),
            'The default page cap must not abort a legitimate full backfill.'
        );
    }
}
