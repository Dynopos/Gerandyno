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
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminSalesplayAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_salesplay_accounts_index_across_companies(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['shop_name' => 'Kedai Demo Shop']);

        $response = $this->actingAs($admin)->get(route('admin.salesplay-accounts.index'));

        $response->assertOk();
        $response->assertSee('Kedai Demo Shop');
    }

    public function test_admin_can_create_a_salesplay_account_with_an_encrypted_token(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.salesplay-accounts.store'), [
            'company_id' => $company->id,
            'shop_name' => 'New Shop',
            'salesplay_shop_id' => 'sp-12345',
            'api_token' => 'plaintext-secret-token',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.salesplay-accounts.index'));

        $account = SalesplayAccount::where('shop_name', 'New Shop')->firstOrFail();
        $this->assertSame('plaintext-secret-token', $account->api_token);
        $this->assertStringNotContainsString(
            'plaintext-secret-token',
            $this->getConnection()->table('salesplay_accounts')->find($account->id)->api_token,
        );
    }

    public function test_admin_can_update_a_salesplay_account_without_changing_the_token(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create([
            'shop_name' => 'Old Name',
            'api_token' => 'original-token',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.salesplay-accounts.update', $account), [
            'company_id' => $account->company_id,
            'shop_name' => 'Updated Name',
            'salesplay_shop_id' => $account->salesplay_shop_id,
            'api_token' => '',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.salesplay-accounts.index'));

        $account->refresh();
        $this->assertSame('Updated Name', $account->shop_name);
        $this->assertSame('original-token', $account->api_token);
    }

    public function test_admin_can_delete_a_salesplay_account(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.salesplay-accounts.destroy', $account));

        $response->assertRedirect(route('admin.salesplay-accounts.index'));
        $this->assertDatabaseMissing('salesplay_accounts', ['id' => $account->id]);
    }

    public function test_admin_can_trigger_a_manual_sync(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['shop_name' => 'Kedai Manual', 'last_synced_at' => null]);

        $response = $this->actingAs($admin)->post(route('admin.salesplay-accounts.sync', $account));

        $response->assertRedirect(route('admin.salesplay-accounts.index'));
        $response->assertSessionHas('status');

        $account->refresh();
        $this->assertNotNull($account->last_synced_at);
        $this->assertSame('success', $account->last_sync_status);
    }

    public function test_sync_shows_an_in_progress_message_instead_of_a_false_failure_when_already_locked(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['shop_name' => 'Kedai Sibuk', 'last_synced_at' => null]);

        $lock = Cache::lock("salesplay-sync-account-{$account->id}", 300);
        $this->assertTrue($lock->get());

        try {
            $response = $this->actingAs($admin)->post(route('admin.salesplay-accounts.sync', $account));

            $response->assertRedirect(route('admin.salesplay-accounts.index'));
            $response->assertSessionHas('status', __('app.admin.salesplay_accounts.sync_in_progress', ['name' => 'Kedai Sibuk']));
        } finally {
            $lock->release();
        }

        // Skipped, not failed — the account's own status must stay untouched.
        $account->refresh();
        $this->assertNull($account->last_synced_at);
        $this->assertNull($account->last_sync_status);
    }

    public function test_resync_resets_last_synced_at_so_the_sync_service_does_a_full_historical_fetch(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['shop_name' => 'Kedai Lengkap', 'last_synced_at' => now()->subDays(10)]);

        $fake = new class implements SalesPlayApiClientInterface
        {
            public bool $wasCalled = false;

            public ?CarbonInterface $capturedSince = null;

            public function fetchReceipts(?string $shopId, string $apiToken, ?CarbonInterface $since, ?string $cursor): SalesPlayReceiptPage
            {
                $this->wasCalled = true;
                $this->capturedSince = $since;

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

        $this->app->instance(SalesPlayApiClientInterface::class, $fake);

        $response = $this->actingAs($admin)->post(route('admin.salesplay-accounts.resync', $account));

        $response->assertRedirect(route('admin.salesplay-accounts.index'));

        $this->assertTrue($fake->wasCalled);
        $this->assertNull($fake->capturedSince);

        $account->refresh();
        $this->assertSame('success', $account->last_sync_status);
    }

    public function test_customer_cannot_trigger_a_manual_sync(): void
    {
        $company = Company::factory()->create();
        $customer = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);

        $this->actingAs($customer)->post(route('admin.salesplay-accounts.sync', $account))->assertForbidden();
    }

    public function test_customer_cannot_trigger_a_resync(): void
    {
        $company = Company::factory()->create();
        $customer = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);

        $this->actingAs($customer)->post(route('admin.salesplay-accounts.resync', $account))->assertForbidden();
    }

    public function test_customer_cannot_access_the_admin_salesplay_accounts_panel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.salesplay-accounts.index'))->assertForbidden();
    }
}
