<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\DTO\SalesInsight;
use App\Services\Ai\Exceptions\AiInsightException;
use App\Services\Ai\OpenAiSalesInsightGenerator;
use App\Support\Reports\WeeklySalesSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiSalesInsightGeneratorTest extends TestCase
{
    private function generator(): OpenAiSalesInsightGenerator
    {
        return new OpenAiSalesInsightGenerator(
            apiKey: 'test-key',
            model: 'gpt-4o-mini',
            baseUrl: 'https://api.openai.com/v1',
            timeout: 10,
        );
    }

    private function snapshot(): WeeklySalesSnapshot
    {
        $weekStart = CarbonImmutable::create(2026, 8, 24);

        return new WeeklySalesSnapshot(
            weekStart: $weekStart,
            weekEnd: $weekStart->addDays(6)->endOfDay(),
            totalSales: 4200.50,
            transactions: 120,
            averageBasket: 35.0,
            previousTotalSales: 3500.00,
            previousTransactions: 100,
            deltaPercent: 20.01,
            dailySeries: collect([
                ['label' => 'Isnin', 'date' => '2026-08-24', 'total' => 800.0, 'transactions' => 25],
                ['label' => 'Selasa', 'date' => '2026-08-25', 'total' => 200.0, 'transactions' => 8],
            ]),
            topProducts: collect([
                ['product_name' => 'Nasi Lemak', 'quantity_sold' => 210.0, 'total_sales' => 1470.0],
            ]),
            topCategories: collect([
                ['category' => 'Makanan', 'total' => 3000.0, 'percentage' => 71.4],
            ]),
            paymentMix: collect([
                ['payment_method' => 'cash', 'total' => 2500.0, 'transactions' => 80],
            ]),
            bestDay: ['label' => 'Isnin', 'date' => '2026-08-24', 'total' => 800.0, 'transactions' => 25],
            quietestDay: ['label' => 'Selasa', 'date' => '2026-08-25', 'total' => 200.0, 'transactions' => 8],
            totalExpenses: 900.0,
            netProfit: 3300.50,
        );
    }

    /**
     * @param  array<string, mixed>  $review
     */
    private function fakeCompletion(array $review): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($review)]],
                ],
            ]),
        ]);
    }

    public function test_it_returns_the_review_the_model_wrote(): void
    {
        $this->fakeCompletion([
            'headline' => 'Jualan naik 20% minggu ini',
            'summary' => 'Minggu yang kukuh, didorong oleh Nasi Lemak.',
            'highlights' => ['Jualan RM 4,200.50', 'Isnin hari terkuat'],
            'advice' => [
                ['title' => 'Tambah stok Nasi Lemak', 'detail' => 'Ia menyumbang sepertiga jualan.'],
            ],
        ]);

        $insight = $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');

        $this->assertSame('Jualan naik 20% minggu ini', $insight->headline);
        $this->assertSame('Minggu yang kukuh, didorong oleh Nasi Lemak.', $insight->summary);
        $this->assertSame(['Jualan RM 4,200.50', 'Isnin hari terkuat'], $insight->highlights);
        $this->assertSame('Tambah stok Nasi Lemak', $insight->advice[0]['title']);
        $this->assertSame(SalesInsight::SOURCE_OPENAI, $insight->source);
    }

    public function test_it_sends_the_configured_model_and_asks_for_json(): void
    {
        $this->fakeCompletion([
            'headline' => 'Headline',
            'summary' => 'Summary',
            'highlights' => [],
            'advice' => [],
        ]);

        $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $body['model'] === 'gpt-4o-mini'
                && $body['response_format'] === ['type' => 'json_object'];
        });
    }

    /**
     * The prompt is the one place merchant data leaves the app, so it must
     * carry the aggregates the review needs and nothing that identifies a
     * customer.
     */
    public function test_the_prompt_carries_the_weeks_figures(): void
    {
        $this->fakeCompletion([
            'headline' => 'Headline',
            'summary' => 'Summary',
            'highlights' => [],
            'advice' => [],
        ]);

        $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');

        Http::assertSent(function (Request $request) {
            $userMessage = $request->data()['messages'][1]['content'];

            return str_contains($userMessage, 'Kedai Demo')
                && str_contains($userMessage, '4200.5')
                && str_contains($userMessage, 'Nasi Lemak')
                && str_contains($userMessage, '"transactions": 120');
        });
    }

    public function test_it_writes_the_review_in_the_apps_locale(): void
    {
        $this->fakeCompletion([
            'headline' => 'Headline',
            'summary' => 'Summary',
            'highlights' => [],
            'advice' => [],
        ]);

        $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'en');

        Http::assertSent(fn (Request $request) => str_contains($request->data()['messages'][0]['content'], 'English'));
    }

    public function test_it_caps_highlights_and_advice(): void
    {
        $this->fakeCompletion([
            'headline' => 'Headline',
            'summary' => 'Summary',
            'highlights' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
            'advice' => array_fill(0, 8, ['title' => 'T', 'detail' => 'D']),
        ]);

        $insight = $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');

        $this->assertCount(5, $insight->highlights);
        $this->assertCount(4, $insight->advice);
    }

    public function test_it_drops_malformed_highlight_and_advice_entries(): void
    {
        $this->fakeCompletion([
            'headline' => 'Headline',
            'summary' => 'Summary',
            'highlights' => ['ok', '', 42, null],
            'advice' => [
                ['title' => 'Kept', 'detail' => 'Has a detail'],
                ['title' => 'Dropped'],
                'not an object',
            ],
        ]);

        $insight = $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');

        $this->assertSame(['ok'], $insight->highlights);
        $this->assertSame([['title' => 'Kept', 'detail' => 'Has a detail']], $insight->advice);
    }

    public function test_it_throws_when_the_api_returns_an_error(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'nope']], 401)]);

        $this->expectException(AiInsightException::class);

        $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');
    }

    public function test_it_throws_when_the_completion_is_not_json(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Sorry, I cannot do that.']]],
            ]),
        ]);

        $this->expectException(AiInsightException::class);

        $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');
    }

    public function test_it_throws_when_the_review_has_no_headline(): void
    {
        $this->fakeCompletion(['summary' => 'Summary only']);

        $this->expectException(AiInsightException::class);

        $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');
    }

    public function test_it_throws_when_the_request_cannot_be_sent(): void
    {
        Http::fake(fn () => throw new \RuntimeException('connection refused'));

        $this->expectException(AiInsightException::class);

        $this->generator()->generate($this->snapshot(), 'Kedai Demo', 'ms');
    }
}
