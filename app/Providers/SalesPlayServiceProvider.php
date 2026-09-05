<?php

namespace App\Providers;

use App\Services\SalesPlay\Contracts\SalesPlayApiClientInterface;
use App\Services\SalesPlay\SalesPlayApiClient;
use App\Services\SalesPlay\SalesPlayMockApiClient;
use App\Services\SalesPlay\SalesPlaySyncService;
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

        $this->app->bind(SalesPlaySyncService::class, function () {
            return new SalesPlaySyncService(
                client: $this->app->make(SalesPlayApiClientInterface::class),
                initialSyncMonths: (int) $this->app['config']->get('services.salesplay.initial_sync_months', 12),
                maxPages: (int) $this->app['config']->get('services.salesplay.max_sync_pages', 5000),
            );
        });
    }
}
