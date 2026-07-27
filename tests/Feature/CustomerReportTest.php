<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerUser(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_customer_index_lists_customers_with_purchase_totals(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Ali Bin Abu']);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'customer_id' => $customer->id,
            'total' => 88.50,
        ]);

        $response = $this->actingAs($user)->get('/reports/customers');

        $response->assertOk();
        $response->assertSee('Ali Bin Abu');
        $response->assertSee('RM 88.50');
    }

    public function test_customer_show_lists_their_purchase_history(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Ali Bin Abu']);

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'customer_id' => $customer->id,
            'receipt_number' => 'RCPT-1',
            'total' => 42.00,
        ]);

        $response = $this->actingAs($user)->get("/reports/customers/{$customer->id}");

        $response->assertOk();
        $response->assertSee('Ali Bin Abu');
        $response->assertSee('RCPT-1');
    }

    public function test_customer_show_does_not_leak_another_companys_customer(): void
    {
        [$userA] = $this->makeCustomerUser();
        [, $companyB] = $this->makeCustomerUser();

        $customerB = Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Rahsia Company B']);

        $response = $this->actingAs($userA)->get("/reports/customers/{$customerB->id}");

        $response->assertNotFound();
    }

    public function test_admin_without_company_is_blocked_from_customer_report(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/customers')->assertForbidden();
    }
}
