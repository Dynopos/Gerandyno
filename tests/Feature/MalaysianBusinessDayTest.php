<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Receipt;
use App\Models\SalesplayAccount;
use App\Models\User;
use App\Services\SalesPlay\SalesPlayApiClient;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every shop here trades in Malaysia and every report is about a Malaysian
 * business day. On UTC, `today()` still resolved to the previous Malaysian
 * day until 8am local, so a late-night shop's after-midnight sales were
 * reported under the wrong day.
 */
class MalaysianBusinessDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_runs_on_malaysian_time(): void
    {
        $this->assertSame('Asia/Kuala_Lumpur', config('app.timezone'));
        $this->assertSame('Asia/Kuala_Lumpur', now()->timezone->getName());
    }

    /**
     * 00:30 on a Malaysian calendar day belongs to that day's takings — the
     * hour when the old UTC boundary put it in yesterday's report.
     */
    public function test_sales_just_after_midnight_count_towards_the_same_malaysian_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-06 02:00:00', 'Asia/Kuala_Lumpur'));

        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => '2026-09-06 00:30:00',
            'total' => 250,
        ]);

        // Yesterday's closing sale must stay in yesterday.
        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => '2026-09-05 23:30:00',
            'total' => 999,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();

        // Today's card is the after-midnight sale only...
        $response->assertSee('RM 250.00');

        // ...and the daily chart buckets them on separate days: the 5th
        // keeps 999, the 6th gets 250. Under UTC both landed on the 5th.
        $response->assertSee('[0,0,0,0,999,250,', escape: false);
    }

    public function test_the_sales_report_uses_the_same_malaysian_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-06 02:00:00', 'Asia/Kuala_Lumpur'));

        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => '2026-09-06 00:30:00',
            'total' => 250,
        ]);

        $response = $this->actingAs($user)->get('/reports/sales?filter=today');

        $response->assertOk();
        $response->assertSee('RM 250.00');
    }

    /**
     * The API returns bare wall-clock strings with no offset. A receipt rung
     * up at 00:30 in Kuala Lumpur must stay 00:30 after syncing, not shift
     * by the app's timezone offset.
     */
    public function test_a_synced_receipt_keeps_the_wall_clock_time_the_shop_rang_it_up_at(): void
    {
        Http::fake([
            '*' => Http::response([
                'receipts' => [[
                    'receipt_number' => '10-0009',
                    'receipt_date_time' => '2026-09-06 00:30:00',
                    'total_money' => 250,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'receipt_delete_status' => false,
                    'line_products' => [],
                    'payments' => [],
                ]],
                'cursor' => null,
            ]),
        ]);

        $client = new SalesPlayApiClient(baseUrl: 'https://api.salesplaypos.com/v1.0', timeout: 10);

        $page = $client->fetchReceipts(shopId: null, apiToken: 'token', since: null, cursor: null);

        $this->assertSame('2026-09-06 00:30:00', $page->items[0]->transactionDate->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Kuala_Lumpur', $page->items[0]->transactionDate->timezone->getName());
    }
}
