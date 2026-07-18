<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Receipt;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_customer_sees_their_own_sales_stats(): void
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'total' => 150,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('RM 150.00');
    }

    public function test_customer_does_not_see_another_companys_sales_in_dashboard_stats(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $accountB = SalesplayAccount::factory()->create(['company_id' => $companyB->id]);

        Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
            'transaction_date' => now(),
            'total' => 999,
        ]);

        $userA = User::factory()->create(['company_id' => $companyA->id, 'role' => 'customer']);

        $response = $this->actingAs($userA)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('RM 999.00');
    }

    public function test_admin_sees_placeholder_instead_of_a_companys_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Panel Admin akan datang');
    }
}
