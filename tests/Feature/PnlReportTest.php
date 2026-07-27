<?php

namespace Tests\Feature;

use App\Exports\PnlExport;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Receipt;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PnlReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_index_shows_sales_expenses_and_net_profit_for_the_period(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'total' => 500,
        ]);

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Pasar',
            'amount' => 120,
            'expense_date' => now(),
        ]);

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Gaji Staf',
            'amount' => 80,
            'expense_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/pnl?filter=today');

        $response->assertOk();
        $response->assertSee($company->name);
        $response->assertSee('Pasar');
        $response->assertSee('Gaji Staf');
        // sales 500, expenses 200, net profit 300
        $response->assertSeeInOrder(['RM 500.00', 'RM 200.00', 'RM 300.00']);
    }

    public function test_index_excludes_data_outside_the_selected_period(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now()->subYear(),
            'total' => 999,
        ]);

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Lama',
            'amount' => 500,
            'expense_date' => now()->subYear(),
        ]);

        $response = $this->actingAs($user)->get('/reports/pnl?filter=today');

        $response->assertOk();
        $response->assertDontSee('Lama');
        $response->assertSeeInOrder(['RM 0.00', 'RM 0.00', 'RM 0.00']);
    }

    public function test_admin_without_company_is_blocked_from_pnl(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/pnl')->assertForbidden();
    }

    public function test_csv_export_downloads_with_sales_expenses_and_net_profit_rows(): void
    {
        Excel::fake();

        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'total' => 300,
        ]);

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Pasar',
            'amount' => 100,
            'expense_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/pnl/export/csv?filter=today');

        $response->assertOk();

        Excel::assertDownloaded(
            'penyata-untung-rugi-today-'.now()->format('Y-m-d').'.csv',
            function (PnlExport $export) {
                $rows = $export->collection();

                return $rows->firstWhere('label', __('app.reports.pnl.total_sales'))['amount'] === 300.0
                    && $rows->firstWhere('label', __('app.reports.pnl.net_profit'))['amount'] === 200.0;
            }
        );
    }

    public function test_pdf_export_downloads_with_correct_content_type(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/pnl/export/pdf?filter=today');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
