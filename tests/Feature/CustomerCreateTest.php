<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCreateTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Kedai Ali',
            'shop_name' => 'Kedai Ali Cawangan 1',
            'salesplay_shop_id' => 'shop-001',
            'api_token' => 'token-abc',
        ], $overrides);
    }

    public function test_admin_can_create_a_company_and_salesplay_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload());

        $response->assertRedirect('/admin/customers/create');

        $this->assertDatabaseHas('companies', ['name' => 'Kedai Ali']);
        $this->assertDatabaseHas('salesplay_accounts', ['salesplay_shop_id' => 'shop-001', 'shop_name' => 'Kedai Ali Cawangan 1']);
    }

    public function test_it_does_not_create_a_login_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/customers', $this->payload());

        $company = Company::where('name', 'Kedai Ali')->firstOrFail();
        $this->assertSame(0, User::where('company_id', $company->id)->count());
    }

    public function test_shop_id_is_optional(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload(['salesplay_shop_id' => '']));

        $response->assertRedirect('/admin/customers/create');
        $this->assertDatabaseHas('salesplay_accounts', ['shop_name' => 'Kedai Ali Cawangan 1', 'salesplay_shop_id' => null]);
    }

    public function test_shop_id_must_be_unique_when_provided(): void
    {
        $admin = User::factory()->admin()->create();
        SalesplayAccount::factory()->create(['salesplay_shop_id' => 'shop-001']);

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload());

        $response->assertSessionHasErrors(['salesplay_shop_id']);
        $this->assertDatabaseMissing('companies', ['name' => 'Kedai Ali']);
    }

    public function test_required_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', []);

        $response->assertSessionHasErrors(['company_name', 'shop_name', 'api_token']);
    }

    public function test_non_admin_is_forbidden_from_creating_customers(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $this->actingAs($user)->get('/admin/customers/create')->assertForbidden();
    }
}
