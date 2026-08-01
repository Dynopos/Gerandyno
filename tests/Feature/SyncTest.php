<?php

namespace Tests\Feature;

use App\Jobs\SyncSalesPlayAccountJob;
use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_trigger_a_sync_for_their_own_company(): void
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id, 'last_synced_at' => null]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $response = $this->actingAs($user)->post(route('sync.store'));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $account->refresh();
        $this->assertNotNull($account->last_synced_at);
        $this->assertSame('success', $account->last_sync_status);
    }

    public function test_syncing_only_touches_the_users_own_company_accounts(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);
        SalesplayAccount::factory()->create(['company_id' => $company->id, 'last_synced_at' => null]);

        $otherAccount = SalesplayAccount::factory()->create(['last_synced_at' => null]);

        $this->actingAs($user)->post(route('sync.store'));

        $otherAccount->refresh();
        $this->assertNull($otherAccount->last_synced_at);
    }

    public function test_a_company_with_no_salesplay_account_shows_a_helpful_message(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $response = $this->actingAs($user)->post(route('sync.store'));

        $response->assertRedirect();
        $response->assertSessionHas('status', __('app.sync.no_accounts'));
    }

    public function test_first_time_sync_is_queued_instead_of_run_inline(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id, 'last_synced_at' => null]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $response = $this->actingAs($user)->post(route('sync.store'));

        $response->assertRedirect();
        $response->assertSessionHas('status', __('app.sync.queued'));

        Queue::assertPushed(SyncSalesPlayAccountJob::class, fn (SyncSalesPlayAccountJob $job) => $job->account->is($account));

        $account->refresh();
        $this->assertNull($account->last_synced_at);
    }

    public function test_incremental_sync_still_runs_inline_for_immediate_feedback(): void
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id, 'last_synced_at' => now()->subDay()]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $response = $this->actingAs($user)->post(route('sync.store'));

        // Ran synchronously within the same request (not merely queued) —
        // last_sync_status is already set by the time we get the response.
        $response->assertSessionHas('status', __('app.sync.success', ['count' => 1]));

        $account->refresh();
        $this->assertSame('success', $account->last_sync_status);
    }

    public function test_admin_without_a_company_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('sync.store'))->assertForbidden();
    }

    public function test_shows_an_in_progress_message_instead_of_a_false_failure_when_already_locked(): void
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id, 'last_synced_at' => null]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $lock = Cache::lock("salesplay-sync-account-{$account->id}", 300);
        $this->assertTrue($lock->get());

        try {
            $response = $this->actingAs($user)->post(route('sync.store'));

            $response->assertRedirect();
            $response->assertSessionHas('status', __('app.sync.in_progress'));
        } finally {
            $lock->release();
        }

        $account->refresh();
        $this->assertNull($account->last_synced_at);
        $this->assertNull($account->last_sync_status);
    }
}
