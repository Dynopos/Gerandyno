<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\StockAdjustment;
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

    private function firstProductRow($response): array
    {
        $product = $response->viewData('products')->first();

        return [
            (float) $product->opening_balance,
            (float) ($product->stock_in ?? 0),
            (float) ($product->stock_out ?? 0),
            (float) $product->closing_balance,
        ];
    }

    public function test_balance_defaults_to_zero_when_product_has_no_adjustment_or_movements(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        Product::factory()->create(['company_id' => $company->id, 'name' => 'Sugar 1kg']);

        $response = $this->actingAs($user)->get('/reports/inventory?filter=today');

        $response->assertOk();
        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->firstProductRow($response));
    }

    public function test_balance_goes_negative_when_sold_without_any_recorded_stock_in(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Sugar 1kg']);

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        ReceiptItem::factory()->create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($user)->get('/reports/inventory?filter=today');

        $response->assertOk();
        // No adjustment and no stock-in ever recorded, so selling 5 units
        // correctly goes negative: there is nothing to prove those 5 units
        // were ever received.
        $this->assertSame([0.0, 0.0, 5.0, -5.0], $this->firstProductRow($response));
    }

    public function test_balance_uses_the_latest_stock_adjustment_as_its_baseline(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Sugar 1kg']);

        StockAdjustment::factory()->create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'adjusted_at' => now()->subDays(25),
        ]);

        $stockIn = StockIn::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'received_at' => now()->subDays(5),
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
            'transaction_date' => now(),
        ]);

        ReceiptItem::factory()->create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 8,
        ]);

        $from = now()->subDays(20)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($user)->get("/reports/inventory?filter=custom&from={$from}&to={$to}");

        $response->assertOk();
        // opening (just before the range) = adjustment baseline 50, no
        // movements yet at that point = 50
        // closing = 50 + stock_in(20) - stock_out(8) = 62
        $this->assertSame([50.0, 20.0, 8.0, 62.0], $this->firstProductRow($response));
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
