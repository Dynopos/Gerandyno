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

    public function test_index_shows_opening_balance_stock_in_stock_out_and_balance_for_todays_range(): void
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
            'received_at' => now(),
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

        // opening balance = balance(32) - stock_in(20) + stock_out(8) = 20
        $response = $this->actingAs($user)->get('/reports/inventory?filter=today');

        $response->assertOk();
        $response->assertSee('Sugar 1kg');
        $response->assertSeeInOrder(['20', '20', '8', '32']);
    }

    public function test_index_excludes_movements_outside_the_selected_date_range(): void
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
            'received_at' => now()->subYear(),
        ]);

        StockInItem::factory()->create([
            'stock_in_id' => $stockIn->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 20,
        ]);

        // Today's range: no movement, so opening/in/out/closing all reduce
        // to the same figure (0 in/out, balance stays 32).
        $response = $this->actingAs($user)->get('/reports/inventory?filter=today');

        $response->assertOk();
        $response->assertSeeInOrder(['32', '0', '0', '32']);
    }

    public function test_index_reconstructs_balance_for_a_past_date_range(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Sugar 1kg',
            'stock_on_hand' => 50,
        ]);

        // Received 10 days ago (inside the custom range below).
        $stockIn = StockIn::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'received_at' => now()->subDays(10),
        ]);

        StockInItem::factory()->create([
            'stock_in_id' => $stockIn->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 15,
        ]);

        // Sold today, i.e. after the custom range's end date — this pushes
        // the live current balance (50) up relative to what it was at the
        // end of the range, so the report must unwind it back out.
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

        $from = now()->subDays(20)->format('Y-m-d');
        $to = now()->subDays(5)->format('Y-m-d');

        // The sale happened after the range's end date, so at the end of
        // the range that stock hadn't been sold yet — closing balance is
        // reconstructed as *higher* than today's current balance (50).
        // closing (end of range) = 50 - stock_in_after(0) + stock_out_after(5) = 55
        // opening (start of range) = 55 - stock_in_in_range(15) + stock_out_in_range(0) = 40
        $response = $this->actingAs($user)->get("/reports/inventory?filter=custom&from={$from}&to={$to}");

        $response->assertOk();
        $response->assertSeeInOrder(['40', '15', '0', '55']);
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
