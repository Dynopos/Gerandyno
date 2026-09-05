<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Expense;
use App\Models\Receipt;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Receipt totals are tax-inclusive — what the customer actually paid, which
 * is the money that reached the till. SalesPlay's own dashboard quotes
 * "Gross sales" *before* tax, so a shop charging 6% sees two different
 * numbers for the same day and has no way to reconcile them unless the tax
 * inside our figure is stated.
 *
 * The tax also is not the shop's income — it is collected for the
 * government — so profit has to be calculated after taking it back out.
 */
class TaxSeparationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A day billed the way Kahf's shop bills: 6% added on top, so the
     * customer pays 1,060.00 on 1,000.00 of food.
     *
     * @return array{0: User, 1: Company}
     */
    private function shopWithTax(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 60,
            'total' => 1060,
        ]);

        return [$user, $company];
    }

    public function test_the_sales_report_states_the_tax_inside_the_total(): void
    {
        [$user] = $this->shopWithTax();

        $response = $this->actingAs($user)->get('/reports/sales?filter=today');

        $response->assertOk();
        // The headline stays the amount collected...
        $response->assertSee('RM 1,060.00');
        // ...with the tax inside it stated, so RM 1,000 reconciles against
        // SalesPlay's tax-exclusive "Gross sales".
        $response->assertSee(__('app.reports.sales.tax_collected'));
        $response->assertSee('RM 60.00');
    }

    public function test_a_shop_with_no_tax_is_not_shown_an_empty_tax_card(): void
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'total' => 500,
        ]);

        $response = $this->actingAs($user)->get('/reports/sales?filter=today');

        $response->assertOk();
        $response->assertDontSee(__('app.reports.sales.tax_collected'));
    }

    /**
     * Profit must be calculated on the shop's own earnings. Counting the
     * tax as income makes a shop charging 6% look 6% more profitable than
     * it is — on RM 1,060 of takings and RM 200 of costs, profit is RM 800,
     * not RM 860.
     */
    public function test_profit_is_calculated_after_taking_the_tax_back_out(): void
    {
        [$user, $company] = $this->shopWithTax();

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Pasar',
            'amount' => 200,
            'expense_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/pnl?filter=today');

        $response->assertOk();
        $response->assertSee(__('app.reports.pnl.tax_collected'));
        $response->assertSee(__('app.reports.pnl.net_sales'));
        // Takings 1,060 − tax 60 = net sales 1,000 − expenses 200 = 800.
        $response->assertSeeInOrder(['RM 1,060.00', 'RM 60.00', 'RM 1,000.00', 'RM 200.00', 'RM 800.00']);
    }

    public function test_a_shop_with_no_tax_sees_the_profit_it_always_saw(): void
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'total' => 500,
        ]);

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Pasar',
            'amount' => 120,
            'expense_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/pnl?filter=today');

        $response->assertOk();
        $response->assertDontSee(__('app.reports.pnl.net_sales'));
        $response->assertSeeInOrder(['RM 500.00', 'RM 120.00', 'RM 380.00']);
    }
}
