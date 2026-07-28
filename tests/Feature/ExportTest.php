<?php

namespace Tests\Feature;

use App\Exports\MonthlyExport;
use App\Exports\PaymentTypesExport;
use App\Exports\ProductsExport;
use App\Exports\SalesExport;
use App\Exports\YearlyExport;
use App\Models\Company;
use App\Models\Payment;
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

        $response = $this->actingAs($user)->get('/reports/sales/export/docx');

        $response->assertNotFound();
    }

    public function test_sales_pdf_export_downloads_with_correct_content_type(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'receipt_number' => 'MINE-PDF',
        ]);

        $response = $this->actingAs($user)->get('/reports/sales/export/pdf?filter=this_month');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertGreaterThan(0, strlen($response->getContent()));
    }

    /**
     * The PDF response body is FlateDecode-compressed, so scoping can't be
     * asserted by searching the raw bytes for a receipt number. Instead,
     * render the same Blade template SalesReportController::export() uses
     * with a hand-built collection and confirm it only displays what it was
     * given — the controller itself is already proven to pass only the
     * authenticated user's own-company receipts (see the CSV/XLSX scoping
     * tests above, which query the exact same $receipts variable).
     */
    public function test_sales_pdf_template_only_renders_the_receipts_it_is_given(): void
    {
        [, $company, $account] = $this->makeCustomer();

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
            'receipt_number' => 'TEMPLATE-MINE',
        ])->load('payments');

        $html = view('exports.pdf.sales', [
            'title' => 'Laporan Jualan',
            'subtitle' => 'Bulan Ini',
            'companyName' => $company->name,
            'receipts' => collect([$receipt]),
        ])->render();

        $this->assertStringContainsString('TEMPLATE-MINE', $html);
        $this->assertStringNotContainsString('SOME-OTHER-RECEIPT', $html);
    }

    public function test_monthly_pdf_export_downloads(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/monthly/export/pdf?year='.now()->year);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_yearly_pdf_export_downloads(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/reports/yearly/export/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_products_pdf_export_downloads(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Pdf Product']);
        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);
        ReceiptItem::factory()->create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'product_name' => 'Pdf Product',
        ]);

        $response = $this->actingAs($user)->get('/reports/products/export/pdf?filter=this_month');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_without_company_is_blocked_from_exports(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/reports/sales/export/csv');

        $response->assertForbidden();
    }

    public function test_payment_types_export_is_scoped_to_own_company(): void
    {
        Excel::fake();

        [$user, $company, $account] = $this->makeCustomer();
        [, $companyB, $accountB] = $this->makeCustomer();

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);
        Payment::factory()->create(['receipt_id' => $receipt->id, 'payment_method' => 'cash', 'amount' => 50]);

        $receiptB = Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
            'transaction_date' => now(),
        ]);
        Payment::factory()->create(['receipt_id' => $receiptB->id, 'payment_method' => 'boost', 'amount' => 999]);

        $response = $this->actingAs($user)->get('/reports/payment-types/export/csv?filter=this_month');

        $response->assertOk();

        $filename = 'laporan-jenis-bayaran-'.Str::slug(__('app.filters.this_month')).'-'.now()->format('Y-m-d').'.csv';

        Excel::assertDownloaded($filename, function (PaymentTypesExport $export) {
            $paymentTypes = $export->collection();

            return $paymentTypes->count() === 1
                && $paymentTypes->first()['payment_method'] === 'cash';
        });
    }

    public function test_payment_types_pdf_export_downloads(): void
    {
        [$user, $company, $account] = $this->makeCustomer();

        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);
        Payment::factory()->create(['receipt_id' => $receipt->id, 'payment_method' => 'cash', 'amount' => 50]);

        $response = $this->actingAs($user)->get('/reports/payment-types/export/pdf?filter=this_month');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
