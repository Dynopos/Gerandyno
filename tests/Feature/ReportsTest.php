<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_admin_without_company_is_blocked_from_reports(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/sales')->assertForbidden();
    }

    public function test_sales_report_lists_receipts_within_the_selected_period(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        $inRange = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'receipt_number' => 'IN-RANGE',
        ]);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now()->subMonths(3),
            'receipt_number' => 'OUT-OF-RANGE',
        ]);

        $response = $this->actingAs($user)->get('/reports/sales?filter=this_month');

        $response->assertOk();
        $response->assertSee('IN-RANGE');
        $response->assertDontSee('OUT-OF-RANGE');
    }

    public function test_customer_can_view_own_receipt_detail(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'receipt_number' => 'MY-RECEIPT',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales.show', $receipt));

        $response->assertOk();
        $response->assertSee('MY-RECEIPT');
    }

    public function test_customer_cannot_view_another_companys_receipt_detail(): void
    {
        [$userA] = $this->makeCustomer();
        [, $companyB, $accountB] = $this->makeCustomer();

        $receiptB = Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
        ]);

        $response = $this->actingAs($userA)->get(route('reports.sales.show', $receiptB));

        $response->assertNotFound();
    }

    public function test_monthly_report_zero_fills_months_with_no_sales(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now()->startOfYear(),
            'total' => 250,
        ]);

        $response = $this->actingAs($user)->get('/reports/monthly?year='.now()->year);

        $response->assertOk();
        $response->assertSee('RM 250.00');
    }

    public function test_yearly_report_groups_by_calendar_year(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now()->subYears(2),
            'total' => 500,
        ]);

        $response = $this->actingAs($user)->get('/reports/yearly');

        $response->assertOk();
        $response->assertSee((string) now()->subYears(2)->year);
        $response->assertSee('RM 500.00');
    }

    public function test_products_report_aggregates_quantity_and_total_per_product_within_period(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Nasi Lemak']);

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        ReceiptItem::factory()->create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'product_name' => 'Nasi Lemak',
            'quantity' => 3,
            'unit_price' => 10,
            'total' => 30,
        ]);

        $response = $this->actingAs($user)->get('/reports/products?filter=this_month');

        $response->assertOk();
        $response->assertSee('Nasi Lemak');
        $response->assertSee('RM 30.00');
    }

    public function test_products_report_does_not_leak_another_companys_product_sales(): void
    {
        [$userA] = $this->makeCustomer();
        [, $companyB, $accountB] = $this->makeCustomer();

        $receiptB = Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
            'transaction_date' => now(),
        ]);

        ReceiptItem::factory()->create([
            'receipt_id' => $receiptB->id,
            'product_name' => 'Rahsia Company B',
            'total' => 999,
        ]);

        $response = $this->actingAs($userA)->get('/reports/products?filter=this_month');

        $response->assertOk();
        $response->assertDontSee('Rahsia Company B');
    }

    public function test_payment_types_report_aggregates_total_and_transactions_per_method_within_period(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_method' => 'cash',
            'amount' => 50,
        ]);

        Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_method' => 'cash',
            'amount' => 25,
        ]);

        Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_method' => 'card',
            'amount' => 100,
        ]);

        $response = $this->actingAs($user)->get('/reports/payment-types?filter=this_month');

        $response->assertOk();
        $response->assertSee('cash');
        $response->assertSee('RM 75.00');
        $response->assertSee('card');
        $response->assertSee('RM 100.00');
    }

    public function test_payment_types_report_does_not_leak_another_companys_payments(): void
    {
        [$userA] = $this->makeCustomer();
        [, $companyB, $accountB] = $this->makeCustomer();

        $receiptB = Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
            'transaction_date' => now(),
        ]);

        Payment::factory()->create([
            'receipt_id' => $receiptB->id,
            'payment_method' => 'boost',
            'amount' => 999,
        ]);

        $response = $this->actingAs($userA)->get('/reports/payment-types?filter=this_month');

        $response->assertOk();
        $response->assertDontSee('boost');
    }
}
