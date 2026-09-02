<?php

namespace App\Providers;

use App\Services\Ai\Contracts\SalesInsightGenerator;
use App\Services\Ai\OpenAiSalesInsightGenerator;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Binds the AI weekly-review generator.
     *
     * Resolving this without an OPENAI_API_KEY configured is a programming
     * error, not a runtime condition — callers check
     * AiInsightController::isConfigured() first and show the "AI not set up
     * yet" state instead. Binding it as a closure keeps the key out of the
     * container until the moment a review is actually generated.
     */
    public function register(): void
    {
        $this->app->bind(SalesInsightGenerator::class, function () {
            $config = $this->app['config']->get('services.openai');

            return new OpenAiSalesInsightGenerator(
                apiKey: (string) ($config['api_key'] ?? ''),
                model: (string) ($config['model'] ?? 'gpt-4o-mini'),
                baseUrl: (string) ($config['base_url'] ?? 'https://api.openai.com/v1'),
                timeout: (int) ($config['timeout'] ?? 60),
            );
        });
    }
}
