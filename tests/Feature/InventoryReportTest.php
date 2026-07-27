<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
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

    public function test_index_shows_opening_balance_stock_in_stock_out_and_balance(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Sugar 1kg',
            'stock_on_hand' => 32,
        ]);

        $stockIn = StockIn::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
        ]);

        StockInItem::factory()->create([
            'stock_in_id' => $stockIn->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 20,
        ]);

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
        ]);

        ReceiptItem::factory()->create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 8,
        ]);

        // opening balance = balance(32) - stock_in(20) + stock_out(8) = 20
        $response = $this->actingAs($user)->get('/reports/inventory');

        $response->assertOk();
        $response->assertSee('Sugar 1kg');
        $response->assertSeeInOrder(['20', '20', '8', '32']);
    }

    public function test_index_is_blocked_for_admin_without_company(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/inventory')->assertForbidden();
    }

    public function test_index_search_filters_by_product_name(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        Product::factory()->create(['company_id' => $company->id, 'name' => 'Sugar 1kg']);
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Flour 1kg']);

        $response = $this->actingAs($user)->get('/reports/inventory?q=Sugar');

        $response->assertOk();
        $response->assertSee('Sugar 1kg');
        $response->assertDontSee('Flour 1kg');
    }
}
