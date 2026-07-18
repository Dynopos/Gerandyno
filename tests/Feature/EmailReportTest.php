<?php

namespace Tests\Feature;

use App\Mail\ReportMail;
use App\Models\Company;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_sales_report_is_emailed_to_the_authenticated_users_own_email(): void
    {
        Mail::fake();

        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->post('/reports/sales/email', ['filter' => 'this_month']);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Mail::assertQueued(ReportMail::class, function (ReportMail $mail) use ($user, $company) {
            return $mail->hasTo($user->email)
                && $mail->companyName === $company->name
                && $mail->recipientName === $user->name
                && str_ends_with($mail->attachmentFilename, '.pdf')
                && $mail->attachmentMime === 'application/pdf'
                && strlen($mail->attachmentContent()) > 0;
        });
    }

    public function test_monthly_report_is_emailed(): void
    {
        Mail::fake();

        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->post('/reports/monthly/email', ['year' => now()->year]);

        $response->assertRedirect();

        Mail::assertQueued(ReportMail::class, fn (ReportMail $mail) => $mail->hasTo($user->email));
    }

    public function test_yearly_report_is_emailed(): void
    {
        Mail::fake();

        [$user, $company, $account] = $this->makeCustomer();

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->post('/reports/yearly/email');

        $response->assertRedirect();

        Mail::assertQueued(ReportMail::class, fn (ReportMail $mail) => $mail->hasTo($user->email));
    }

    public function test_products_report_is_emailed(): void
    {
        Mail::fake();

        [$user, $company, $account] = $this->makeCustomer();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Emailed Product']);
        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now(),
        ]);
        ReceiptItem::factory()->create([
            'receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'product_name' => 'Emailed Product',
        ]);

        $response = $this->actingAs($user)->post('/reports/products/email', ['filter' => 'this_month']);

        $response->assertRedirect();

        Mail::assertQueued(ReportMail::class, fn (ReportMail $mail) => $mail->hasTo($user->email));
    }

    public function test_email_report_cannot_be_sent_to_an_arbitrary_address(): void
    {
        Mail::fake();

        [$user] = $this->makeCustomer();

        // Even if a caller tried to smuggle a different recipient in, the
        // controller only ever reads $request->user()->email — there is no
        // "to" input the request even accepts.
        $response = $this->actingAs($user)->post('/reports/sales/email', [
            'filter' => 'this_month',
            'to' => 'someone-else@example.com',
        ]);

        $response->assertRedirect();

        Mail::assertQueued(ReportMail::class, function (ReportMail $mail) use ($user) {
            return $mail->hasTo($user->email) && ! $mail->hasTo('someone-else@example.com');
        });
    }

    public function test_admin_without_company_is_blocked_from_emailing_reports(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/reports/sales/email');

        $response->assertForbidden();
        Mail::assertNothingQueued();
    }

    public function test_email_report_view_renders_with_recipient_and_company_details(): void
    {
        $mail = new ReportMail(
            reportTitle: 'Laporan Jualan',
            periodLabel: 'Bulan Ini',
            companyName: 'Kedai Demo Sdn Bhd',
            recipientName: 'Demo Customer',
            attachmentContent: 'dummy-pdf-bytes',
            attachmentFilename: 'laporan-jualan.pdf',
            attachmentMime: 'application/pdf',
        );

        $html = $mail->render();

        $this->assertStringContainsString('Demo Customer', $html);
        $this->assertStringContainsString('Kedai Demo Sdn Bhd', $html);
        $this->assertStringContainsString('Bulan Ini', $html);
    }

    /**
     * Regression test: PDF/XLSX bytes are binary and not valid UTF-8.
     * Mail::fake() never exercises real queue serialization, so it would
     * NOT catch a Mailable that fails to JSON-encode once actually pushed
     * onto a real queue driver (database/redis) — which is exactly what
     * happened here (Illuminate\Queue\InvalidPayloadException: "Malformed
     * UTF-8 characters") before attachment bytes were base64-encoded before
     * being held on the Mailable. This test pushes a real job onto the
     * database driver to prove it survives serialization.
     */
    public function test_report_mail_with_binary_attachment_survives_real_queue_serialization(): void
    {
        config(['queue.default' => 'database']);

        [$user, $company] = $this->makeCustomer();

        $binaryContent = random_bytes(500);

        Mail::to($user->email)->queue(new ReportMail(
            reportTitle: 'Laporan Jualan',
            periodLabel: 'Bulan Ini',
            companyName: $company->name,
            recipientName: $user->name,
            attachmentContent: $binaryContent,
            attachmentFilename: 'laporan-jualan.pdf',
            attachmentMime: 'application/pdf',
        ));

        $this->assertDatabaseCount('jobs', 1);
    }
}
