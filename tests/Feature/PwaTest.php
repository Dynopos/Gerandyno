<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Merchants run this on a phone, so it installs to the home screen instead
 * of living behind a typed URL in a browser tab.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $path = public_path('manifest.webmanifest');

        $this->assertFileExists($path);

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_the_manifest_describes_an_installable_app(): void
    {
        $manifest = $this->manifest();

        $this->assertSame('DynoPOS Cloud Report', $manifest['name']);
        $this->assertSame('DynoPOS', $manifest['short_name']);
        // standalone is what removes the browser chrome once installed.
        $this->assertSame('standalone', $manifest['display']);
        // Opening the icon lands on the dashboard, not the login redirect.
        $this->assertSame('/dashboard', $manifest['start_url']);
    }

    /**
     * Android crops icons to the launcher's own shape. Without a maskable
     * icon the badge's edges get shaved off, so both purposes are required.
     */
    public function test_the_icons_cover_both_plain_and_maskable_launchers(): void
    {
        $icons = collect($this->manifest()['icons']);

        foreach (['any', 'maskable'] as $purpose) {
            foreach (['192x192', '512x512'] as $size) {
                $icon = $icons->firstWhere(fn (array $i) => $i['purpose'] === $purpose && $i['sizes'] === $size);

                $this->assertNotNull($icon, "Missing a {$size} {$purpose} icon.");
                $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
            }
        }
    }

    public function test_the_signed_in_pages_advertise_the_manifest(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('manifest.webmanifest', escape: false);
        $response->assertSee('apple-touch-icon.png', escape: false);
    }

    /**
     * The login page has to carry it too — a merchant installing the app
     * arrives there first, before any session exists.
     */
    public function test_the_login_page_advertises_the_manifest(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('manifest.webmanifest', escape: false);
    }

    /**
     * Every page here is behind a login and carries a per-session CSRF
     * token. A cached page would mean either someone else's figures on
     * screen or a 419 on the next submit, so the worker must leave
     * navigations alone entirely.
     */
    public function test_the_service_worker_never_caches_pages(): void
    {
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("request.mode === 'navigate'", $worker);
        $this->assertStringContainsString("request.method !== 'GET'", $worker);

        // Only versioned build output and the app's own images are cached.
        $this->assertStringContainsString('/^\/build\//', $worker);
        $this->assertStringContainsString('/^\/images\//', $worker);
    }

    public function test_the_worker_is_registered_by_the_app_bundle(): void
    {
        $bundle = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("navigator.serviceWorker.register('/sw.js')", $bundle);
    }
}
