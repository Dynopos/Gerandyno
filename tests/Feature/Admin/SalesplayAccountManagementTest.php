<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesplayAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_manage_salesplay_accounts(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/salesplay-accounts')->assertForbidden();
    }

    public function test_admin_sees_accounts_from_every_company(): void
    {
        $admin = User::factory()->admin()->create();
        SalesplayAccount::factory()->create(['shop_name' => 'Cawangan A']);
        SalesplayAccount::factory()->create(['shop_name' => 'Cawangan B']);

        $response = $this->actingAs($admin)->get('/admin/salesplay-accounts');

        $response->assertOk();
        $response->assertSee('Cawangan A');
        $response->assertSee('Cawangan B');
    }

    public function test_admin_can_create_an_account_and_the_token_is_stored_encrypted(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->post('/admin/salesplay-accounts', [
            'company_id' => $company->id,
            'shop_name' => 'Cawangan Baharu',
            'salesplay_shop_id' => 'SP-100',
            'api_token' => 'token-rahsia',
            'status' => 'active',
        ])->assertRedirect();

        $account = SalesplayAccount::where('shop_name', 'Cawangan Baharu')->firstOrFail();

        $this->assertSame('token-rahsia', $account->api_token);
        $this->assertNotSame('token-rahsia', $account->getRawOriginal('api_token'));
        $this->assertArrayNotHasKey('api_token', $account->toArray());
    }

    public function test_the_token_is_never_rendered_back_into_the_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['api_token' => 'token-rahsia']);

        $this->actingAs($admin)
            ->get("/admin/salesplay-accounts/{$account->id}/edit")
            ->assertOk()
            ->assertDontSee('token-rahsia');
    }

    public function test_a_blank_token_on_update_keeps_the_existing_one(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['api_token' => 'token-lama']);

        $this->actingAs($admin)->put("/admin/salesplay-accounts/{$account->id}", [
            'company_id' => $account->company_id,
            'shop_name' => 'Nama Baharu',
            'salesplay_shop_id' => $account->salesplay_shop_id,
            'api_token' => '',
            'status' => 'active',
        ])->assertRedirect();

        $account->refresh();

        $this->assertSame('Nama Baharu', $account->shop_name);
        $this->assertSame('token-lama', $account->api_token);
    }

    public function test_a_filled_token_on_update_replaces_the_existing_one(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['api_token' => 'token-lama']);

        $this->actingAs($admin)->put("/admin/salesplay-accounts/{$account->id}", [
            'company_id' => $account->company_id,
            'shop_name' => $account->shop_name,
            'salesplay_shop_id' => $account->salesplay_shop_id,
            'api_token' => 'token-baharu',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('token-baharu', $account->refresh()->api_token);
    }

    public function test_admin_can_disable_sync_for_an_account(): void
    {
        $admin = User::factory()->admin()->create();
        $account = SalesplayAccount::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->put("/admin/salesplay-accounts/{$account->id}", [
            'company_id' => $account->company_id,
            'shop_name' => $account->shop_name,
            'salesplay_shop_id' => $account->salesplay_shop_id,
            'status' => 'inactive',
        ])->assertRedirect();

        $this->assertFalse($account->refresh()->isActive());
    }

    public function test_shop_id_must_be_unique_within_a_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        SalesplayAccount::factory()->create(['company_id' => $company->id, 'salesplay_shop_id' => 'SP-1']);

        $this->actingAs($admin)->post('/admin/salesplay-accounts', [
            'company_id' => $company->id,
            'shop_name' => 'Duplikat',
            'salesplay_shop_id' => 'SP-1',
            'api_token' => 'token',
            'status' => 'active',
        ])->assertSessionHasErrors('salesplay_shop_id');
    }
}
