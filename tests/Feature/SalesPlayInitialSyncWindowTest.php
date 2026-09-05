<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
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
 * The real API rejects a first sync that asks for an open-ended range —
 * it answers "The requested date range is not supported" on created_at_min
 * and the whole sync fails, so an account could never complete its very
 * first sync. These cover the concrete start date that replaced it, and
 * that a rejected sync is reported rather than turned into a 500.
 */
class SalesPlayInitialSyncWindowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: SalesplayAccount}
     */
    private function makeAccount(?CarbonInterface $lastSyncedAt): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create([
            'company_id' => $company->id,
            'last_synced_at' => $lastSyncedAt,
        ]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $account];
    }

    private function spyClient(): SalesPlayApiClientInterface
    {
        return new class implements SalesPlayApiClientInterface
        {
            public ?CarbonInterface $seenSince = null;

            public bool $wasCalled = false;

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                $this->wasCalled = true;
                $this->seenSince = $since;

                return new SalesPlayReceiptPage(items: [], hasMore: false, nextCursor: null);
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

    public function test_a_first_sync_asks_for_a_concrete_start_date_not_all_of_history(): void
    {
        [, $account] = $this->makeAccount(lastSyncedAt: null);
        $client = $this->spyClient();

        (new SalesPlaySyncService($client, initialSyncMonths: 12))->sync($account);

        $this->assertTrue($client->wasCalled);
        $this->assertNotNull($client->seenSince, 'A first sync must name a start date the API accepts.');
        $this->assertTrue(
            $client->seenSince->isAfter(now()->subMonths(13)),
            'A first sync must not reach further back than the configured window.'
        );
        $this->assertTrue($client->seenSince->isBefore(now()));
    }

    public function test_the_first_sync_window_is_configurable(): void
    {
        [, $account] = $this->makeAccount(lastSyncedAt: null);
        $client = $this->spyClient();

        (new SalesPlaySyncService($client, initialSyncMonths: 3))->sync($account);

        $this->assertTrue($client->seenSince->isAfter(now()->subMonths(4)));
        $this->assertTrue($client->seenSince->isBefore(now()->subMonths(2)));
    }

    public function test_an_incremental_sync_still_resumes_from_the_last_synced_point(): void
    {
        $lastSyncedAt = now()->subDays(2);
        [, $account] = $this->makeAccount(lastSyncedAt: $lastSyncedAt);
        $client = $this->spyClient();

        (new SalesPlaySyncService($client, initialSyncMonths: 12))->sync($account);

        $this->assertSame(
            $lastSyncedAt->toDateTimeString(),
            $client->seenSince->toDateTimeString(),
            'An account that has synced before must not be pushed back to the initial window.'
        );
    }

    /**
     * On QUEUE_CONNECTION=sync, dispatch() runs the job inline, so a
     * rejected sync throws inside the web request. It must be reported like
     * any other failed sync, never rendered as a 500.
     */
    public function test_a_failing_first_sync_is_reported_instead_of_erroring_the_page(): void
    {
        config(['queue.default' => 'sync']);

        [$user, $account] = $this->makeAccount(lastSyncedAt: null);

        $this->app->bind(SalesPlayApiClientInterface::class, fn () => new class implements SalesPlayApiClientInterface
        {
            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                throw new SalesPlayApiException('SalesPlay API returned HTTP 401: date range not supported.');
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
        });

        $response = $this->actingAs($user)->post(route('sync.store'));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertSame('failed', $account->refresh()->last_sync_status);
    }

    public function test_an_admin_sees_the_failure_instead_of_a_500_too(): void
    {
        config(['queue.default' => 'sync']);

        [, $account] = $this->makeAccount(lastSyncedAt: null);
        $admin = User::factory()->create(['company_id' => null, 'role' => 'admin']);

        $this->app->bind(SalesPlayApiClientInterface::class, fn () => new class implements SalesPlayApiClientInterface
        {
            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                throw new SalesPlayApiException('SalesPlay API returned HTTP 401: date range not supported.');
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
        });

        $this->actingAs($admin)
            ->post(route('admin.salesplay-accounts.sync', $account))
            ->assertRedirect(route('admin.salesplay-accounts.index'))
            ->assertSessionHas('status');

        $this->assertSame('failed', $account->refresh()->last_sync_status);
    }

    public function test_the_configured_window_is_wired_through_the_container(): void
    {
        config(['services.salesplay.initial_sync_months' => 3]);

        $client = $this->spyClient();
        $this->app->bind(SalesPlayApiClientInterface::class, fn () => $client);

        [, $account] = $this->makeAccount(lastSyncedAt: null);

        $this->app->make(SalesPlaySyncService::class)->sync($account);

        $this->assertTrue($client->seenSince->isAfter(now()->subMonths(4)));
        $this->assertTrue($client->seenSince->isBefore(now()->subMonths(2)));
    }
}
