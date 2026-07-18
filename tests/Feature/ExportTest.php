<?php

namespace Tests\Feature;

use App\Exports\MonthlyExport;
use App\Exports\ProductsExport;
use App\Exports\SalesExport;
use App\Exports\YearlyExport;
use App\Models\Company;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_sales_csv_export_downloads_and_is_scoped_to_own_company(): void
    {
        Excel::fake();

        [$user, $company, $account] = $this->makeCustomer();
        [, $companyB, $accountB] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'receipt_number' => 'MINE',
        ]);

        Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
            'transaction_date' => now(),
            'receipt_number' => 'NOT-MINE',
        ]);

        $response = $this->actingAs($user)->get('/reports/sales/export/csv?filter=this_month');

        $response->assertOk();

        $filename = 'laporan-jualan-'.Str::slug(__('app.filters.this_month')).'-'.now()->format('Y-m-d').'.csv';

        Excel::assertDownloaded($filename, function (SalesExport $export) {
            $receipts = $export->collection();

            return $receipts->count() === 1
                && $receipts->first()->receipt_number === 'MINE';
        });
    }

    public function test_sales_xlsx_export_has_correct_content_type(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/sales/export/xlsx?filter=this_month');

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_monthly_export_downloads(): void
    {
        Excel::fake();

        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now()->startOfYear(),
            'total' => 100,
        ]);

        $year = now()->year;
        $response = $this->actingAs($user)->get("/reports/monthly/export/csv?year={$year}");

        $response->assertOk();

        Excel::assertDownloaded("laporan-bulanan-{$year}.csv", function (MonthlyExport $export) {
            return $export->collection()->count() === 12;
        });
    }

    public function test_yearly_export_downloads(): void
    {
        Excel::fake();

        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/yearly/export/csv');

        $response->assertOk();

        $filename = 'laporan-tahunan-'.now()->format('Y-m-d').'.csv';

        Excel::assertDownloaded($filename, function (YearlyExport $export) {
            return $export->collection()->isNotEmpty();
        });
    }

    public function test_products_export_is_scoped_to_own_company(): void
    {
        Excel::fake();

        [$user, $company, $account] = $this->makeCustomer();
        [, $companyB, $accountB] = $this->makeCustomer();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Mine Product']);
        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);
        ReceiptItem::factory()->create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'product_name' => 'Mine Product',
        ]);

        $productB = Product::factory()->create(['company_id' => $companyB->id, 'name' => 'Not Mine Product']);
        $receiptB = Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
            'transaction_date' => now(),
        ]);
        ReceiptItem::factory()->create([
            'receipt_id' => $receiptB->id,
            'product_id' => $productB->id,
            'product_name' => 'Not Mine Product',
        ]);

        $response = $this->actingAs($user)->get('/reports/products/export/csv?filter=this_month');

        $response->assertOk();

        $filename = 'laporan-produk-'.Str::slug(__('app.filters.this_month')).'-'.now()->format('Y-m-d').'.csv';

        Excel::assertDownloaded($filename, function (ProductsExport $export) {
            $products = $export->collection();

            return $products->count() === 1
                && $products->first()['product_name'] === 'Mine Product';
        });
    }

    public function test_invalid_export_format_returns_not_found(): void
    {
        [$user] = $this->makeCustomer();

        $response = $this->actingAs($user)->get('/reports/sales/export/pdf');

        $response->assertNotFound();
    }

    public function test_admin_without_company_is_blocked_from_exports(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/reports/sales/export/csv');

        $response->assertForbidden();
    }
}
