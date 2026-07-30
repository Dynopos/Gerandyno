<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_admin_without_a_company_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('sync.store'))->assertForbidden();
    }
}
