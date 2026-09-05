<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiInsightTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Company, 2: SalesplayAccount}
     */
    private function makeCustomer(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    private function sale(Company $company, SalesplayAccount $account, string $date, float $total, ?string $product = null): Receipt
    {
        $receipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => $date,
            'total' => $total,
        ]);

        if ($product !== null) {
            ReceiptItem::factory()->create([
                'receipt_id' => $receipt->id,
                'product_name' => $product,
                'quantity' => 2,
                'total' => $total,
            ]);
        }

        return $receipt;
    }

    private function fakeReview(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'headline' => 'Minggu paling kukuh setakat ini',
                        'summary' => 'Jualan naik berbanding minggu lepas.',
                        'highlights' => ['Nasi Lemak paling laku'],
                        'advice' => [['title' => 'Tambah stok', 'detail' => 'Sediakan lebih Nasi Lemak hujung minggu.']],
                    ])],
                ]],
            ]),
        ]);
    }

    public function test_it_shows_this_weeks_figures_and_top_products(): void
    {
        [$user, $company, $account] = $this->makeCustomer();
        $monday = CarbonImmutable::today()->startOfWeek();

        $this->sale($company, $account, $monday->addHours(10)->toDateTimeString(), 300, 'Nasi Lemak');
        $this->sale($company, $account, $monday->addDay()->addHours(10)->toDateTimeString(), 200);

        $response = $this->actingAs($user)->get('/reports/ai');

        $response->assertOk();
        $response->assertSee(__('app.ai.title'));
        $response->assertSee('RM 500.00');
        $response->assertSee('Nasi Lemak');
    }

    public function test_last_week_shows_last_weeks_figures(): void
    {
        [$user, $company, $account] = $this->makeCustomer();
        $lastMonday = CarbonImmutable::today()->startOfWeek()->subWeek();

        $this->sale($company, $account, $lastMonday->addHours(10)->toDateTimeString(), 750);

        $response = $this->actingAs($user)->get('/reports/ai?week=last_week');

        $response->assertOk();
        $response->assertSee('RM 750.00');
    }

    public function test_it_tells_the_admin_when_no_api_key_is_configured(): void
    {
        config(['services.openai.api_key' => null]);
        [$user] = $this->makeCustomer();

        $response = $this->actingAs($user)->get('/reports/ai');

        $response->assertOk();
        $response->assertSee(__('app.ai.not_configured_title'));
        $response->assertDontSee(__('app.ai.generate'));
    }

    public function test_generating_stores_the_review_and_shows_it_on_the_page(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $this->fakeReview();

        [$user, $company, $account] = $this->makeCustomer();
        $this->sale($company, $account, CarbonImmutable::today()->startOfWeek()->addHours(10)->toDateTimeString(), 300, 'Nasi Lemak');

        $this->actingAs($user)
            ->from('/reports/ai')
            ->post('/reports/ai')
            ->assertRedirect('/reports/ai')
            ->assertSessionHas('status', __('app.ai.generated'));

        // Generated once, then served from the cache on every later view.
        $response = $this->actingAs($user)->get('/reports/ai');
        $response->assertSee('Minggu paling kukuh setakat ini');
        $response->assertSee('Tambah stok');
        Http::assertSentCount(1);
    }

    public function test_generating_reports_a_friendly_error_when_the_api_fails(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'boom'], 500)]);

        [$user, $company, $account] = $this->makeCustomer();
        $this->sale($company, $account, CarbonImmutable::today()->startOfWeek()->addHours(10)->toDateTimeString(), 300);

        $this->actingAs($user)
            ->from('/reports/ai')
            ->post('/reports/ai')
            ->assertRedirect('/reports/ai')
            ->assertSessionHas('ai_error', __('app.ai.failed'));
    }

    public function test_generating_is_refused_when_no_api_key_is_configured(): void
    {
        config(['services.openai.api_key' => null]);
        Http::fake();

        [$user, $company, $account] = $this->makeCustomer();
        $this->sale($company, $account, CarbonImmutable::today()->startOfWeek()->addHours(10)->toDateTimeString(), 300);

        $this->actingAs($user)
            ->from('/reports/ai')
            ->post('/reports/ai')
            ->assertSessionHas('ai_error', __('app.ai.not_configured'));

        Http::assertNothingSent();
    }

    public function test_generating_is_refused_for_a_week_with_no_sales(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake();

        [$user] = $this->makeCustomer();

        $this->actingAs($user)
            ->from('/reports/ai')
            ->post('/reports/ai')
            ->assertSessionHas('ai_error', __('app.ai.no_data'));

        Http::assertNothingSent();
    }

    public function test_it_never_counts_another_companys_sales(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $this->fakeReview();

        [$user, $company, $account] = $this->makeCustomer();
        [, $otherCompany, $otherAccount] = $this->makeCustomer();

        $monday = CarbonImmutable::today()->startOfWeek()->addHours(10)->toDateTimeString();
        $this->sale($company, $account, $monday, 300, 'Nasi Lemak');
        $this->sale($otherCompany, $otherAccount, $monday, 9999, 'Rahsia Jiran');

        $response = $this->actingAs($user)->get('/reports/ai');

        $response->assertOk();
        $response->assertSee('RM 300.00');
        $response->assertDontSee('Rahsia Jiran');
        $response->assertDontSee('RM 9,999.00');

        // ...and the other company's figures never reach the API either.
        $this->actingAs($user)->from('/reports/ai')->post('/reports/ai');

        Http::assertSent(function ($request) {
            $prompt = $request->data()['messages'][1]['content'];

            return str_contains($prompt, 'Nasi Lemak') && ! str_contains($prompt, 'Rahsia Jiran');
        });
    }

    public function test_a_cached_review_is_not_shared_between_companies(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $this->fakeReview();

        [$user, $company, $account] = $this->makeCustomer();
        [$otherUser, $otherCompany, $otherAccount] = $this->makeCustomer();

        $monday = CarbonImmutable::today()->startOfWeek()->addHours(10)->toDateTimeString();
        $this->sale($company, $account, $monday, 300);
        $this->sale($otherCompany, $otherAccount, $monday, 400);

        $this->actingAs($user)->from('/reports/ai')->post('/reports/ai');

        $this->actingAs($otherUser)
            ->get('/reports/ai')
            ->assertDontSee('Minggu paling kukuh setakat ini');
    }

    public function test_an_admin_without_a_company_cannot_see_it(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => 'admin']);

        $this->actingAs($admin)->get('/reports/ai')->assertForbidden();
    }

    public function test_guests_cannot_see_it(): void
    {
        $this->get('/reports/ai')->assertRedirect('/login');
    }
}
