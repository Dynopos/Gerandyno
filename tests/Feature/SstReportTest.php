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
 * Whether the tax inside a receipt is income depends on the shop, and
 * nothing in the receipts says which: a registered business collects SST
 * for the government, an unregistered one keeps every ringgit it takes.
 * Reading both the same way is wrong for one of them.
 */
class SstReportTest extends TestCase
{
    /**
     * @return array{0: User, 1: Company}
     */
    private function shop(bool $sstRegistered): array
    {
        $company = Company::factory()->create([
            'sst_registered' => $sstRegistered,
            'sst_no' => $sstRegistered ? 'W10-1234-56789012' : null,
        ]);
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        // RM 1,000 of food with 6% on top: the customer paid RM 1,060.
        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 60,
            'total' => 1060,
        ]);

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Pasar',
            'amount' => 200,
            'expense_date' => now(),
        ]);

        return [$user, $company];
    }

    use RefreshDatabase;

    public function test_a_registered_shop_has_its_tax_taken_out_of_profit(): void
    {
        [$user] = $this->shop(sstRegistered: true);

        $response = $this->actingAs($user)->get('/reports/pnl?filter=today');

        $response->assertOk();
        // 1,060 takings − 60 tax = 1,000 net sales − 200 costs = 800.
        $response->assertSeeInOrder(['RM 1,060.00', 'RM 60.00', 'RM 1,000.00', 'RM 200.00', 'RM 800.00']);
    }

    /**
     * The case that made this setting necessary: a shop with a stray 6%
     * configured in SalesPlay that it never remits. Deducting it would
     * understate the owner's earnings by exactly the tax.
     */
    public function test_an_unregistered_shop_keeps_its_tax_as_income(): void
    {
        [$user] = $this->shop(sstRegistered: false);

        $response = $this->actingAs($user)->get('/reports/pnl?filter=today');

        $response->assertOk();
        $response->assertDontSee(__('app.reports.pnl.net_sales'));
        // 1,060 takings − 200 costs = 860. Every ringgit is theirs.
        $response->assertSeeInOrder(['RM 1,060.00', 'RM 200.00', 'RM 860.00']);
    }

    public function test_the_tax_tab_and_report_are_only_for_registered_shops(): void
    {
        [$registered] = $this->shop(sstRegistered: true);
        [$unregistered] = $this->shop(sstRegistered: false);

        $this->actingAs($registered)->get('/dashboard')->assertSee(__('app.nav.sst'));
        $this->actingAs($registered)->get('/reports/sst')->assertOk();

        $this->actingAs($unregistered)->get('/dashboard')->assertDontSee(__('app.nav.sst'));
        $this->actingAs($unregistered)->get('/reports/sst')->assertNotFound();
    }

    public function test_the_report_shows_the_month_its_taxable_value_and_tax(): void
    {
        [$user, $company] = $this->shop(sstRegistered: true);

        $response = $this->actingAs($user)->get('/reports/sst?dari='.now()->format('Y-m').'&hingga='.now()->format('Y-m'));

        $response->assertOk();
        $response->assertSee($company->name);
        $response->assertSee('W10-1234-56789012');
        // Taxable service value is the takings less the tax inside them.
        $response->assertSee('1,000.00');
        $response->assertSee('60.00');
    }

    public function test_one_shops_tax_report_never_includes_another_shops_receipts(): void
    {
        [$user] = $this->shop(sstRegistered: true);
        [, $otherCompany] = $this->shop(sstRegistered: true);

        $otherAccount = SalesplayAccount::factory()->create(['company_id' => $otherCompany->id]);
        Receipt::factory()->create([
            'company_id' => $otherCompany->id,
            'salesplay_account_id' => $otherAccount->id,
            'transaction_date' => now(),
            'subtotal' => 50000,
            'discount' => 0,
            'tax' => 3000,
            'total' => 53000,
        ]);

        $response = $this->actingAs($user)->get('/reports/sst');

        $response->assertOk();
        $response->assertDontSee('53,000.00');
        $response->assertDontSee('3,000.00');
    }

    public function test_an_admin_can_mark_a_company_as_registered(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => 'admin']);
        $company = Company::factory()->create(['sst_registered' => false]);

        $this->actingAs($admin)->put(route('admin.companies.update', $company), [
            'name' => $company->name,
            'status' => 'active',
            'sst_registered' => '1',
            'sst_no' => 'W10-1234-56789012',
        ])->assertRedirect();

        $company->refresh();

        $this->assertTrue($company->sst_registered);
        $this->assertSame('W10-1234-56789012', $company->sst_no);
    }

    public function test_leaving_the_box_unticked_marks_the_company_unregistered(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => 'admin']);
        $company = Company::factory()->create(['sst_registered' => true]);

        // An unticked checkbox sends nothing, so the hidden 0 has to win.
        $this->actingAs($admin)->put(route('admin.companies.update', $company), [
            'name' => $company->name,
            'status' => 'active',
            'sst_registered' => '0',
        ])->assertRedirect();

        $this->assertFalse($company->refresh()->sst_registered);
    }
}
