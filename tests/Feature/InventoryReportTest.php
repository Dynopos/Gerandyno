<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesplayAccount;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerUser(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_stock_balance_lists_products_with_stock_on_hand(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Sugar 1kg',
            'stock_on_hand' => 42,
        ]);

        $response = $this->actingAs($user)->get('/reports/inventory/stock');

        $response->assertOk();
        $response->assertSee('Sugar 1kg');
        $response->assertSee('42');
    }

    public function test_stock_balance_is_blocked_for_admin_without_company(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/inventory/stock')->assertForbidden();
    }

    public function test_stock_ins_index_lists_records(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        StockIn::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'supplier_name' => 'Acme Supplies',
            'total' => 150,
        ]);

        $response = $this->actingAs($user)->get('/reports/inventory/stock-ins');

        $response->assertOk();
        $response->assertSee('Acme Supplies');
    }

    public function test_stock_in_show_lists_its_items(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $stockIn = StockIn::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'supplier_name' => 'Acme Supplies',
        ]);

        StockInItem::factory()->create([
            'stock_in_id' => $stockIn->id,
            'product_name' => 'Sugar 1kg',
        ]);

        $response = $this->actingAs($user)->get("/reports/inventory/stock-ins/{$stockIn->id}");

        $response->assertOk();
        $response->assertSee('Sugar 1kg');
    }

    public function test_stock_in_show_does_not_leak_another_companys_record(): void
    {
        [$userA] = $this->makeCustomerUser();
        [, $companyB, $accountB] = $this->makeCustomerUser();

        $stockInB = StockIn::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
        ]);

        $response = $this->actingAs($userA)->get("/reports/inventory/stock-ins/{$stockInB->id}");

        $response->assertNotFound();
    }
}
