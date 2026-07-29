<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_customer_cannot_trigger_a_manual_sync(): void
    {
        $company = Company::factory()->create();
        $customer = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);

        $this->actingAs($customer)->post(route('admin.salesplay-accounts.sync', $account))->assertForbidden();
    }

    public function test_customer_cannot_access_the_admin_salesplay_accounts_panel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.salesplay-accounts.index'))->assertForbidden();
    }
}
