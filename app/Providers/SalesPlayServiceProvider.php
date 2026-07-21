<?php

namespace App\Providers;

use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\SalesPlayApiClient;
use App\Services\SalesPlay\SalesPlayMockApiClient;
use Illuminate\Support\ServiceProvider;

class SalesPlayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * Binds the real HTTP client whenever a SalesPlay base URL is
     * configured; otherwise falls back to the mock client so the sync
     * pipeline, dashboard, and reports remain fully usable before the real
     * SalesPlay API endpoint is confirmed.
     */
    public function register(): void
    {
        $this->app->bind(SalesPlayApiClientInterface::class, function () {
            $config = $this->app['config']->get('services.salesplay');

            if (empty($config['base_url'])) {
                return new SalesPlayMockApiClient;
            }

            return new SalesPlayApiClient(
                baseUrl: $config['base_url'],
                timeout: (int) ($config['timeout'] ?? 30),
            );
        });
    }
}
