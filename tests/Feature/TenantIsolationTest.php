<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Receipt;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_only_sees_receipts_from_their_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $accountA = SalesplayAccount::factory()->create(['company_id' => $companyA->id]);
        $accountB = SalesplayAccount::factory()->create(['company_id' => $companyB->id]);

        $receiptA = Receipt::factory()->create(['company_id' => $companyA->id, 'salesplay_account_id' => $accountA->id]);
        Receipt::factory()->create(['company_id' => $companyB->id, 'salesplay_account_id' => $accountB->id]);

        $userA = User::factory()->create(['company_id' => $companyA->id, 'role' => 'customer']);

        $this->actingAs($userA);

        $visibleReceipts = Receipt::all();

        $this->assertCount(1, $visibleReceipts);
        $this->assertTrue($visibleReceipts->first()->is($receiptA));
    }

    public function test_customer_cannot_load_another_companys_receipt_by_id(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $accountB = SalesplayAccount::factory()->create(['company_id' => $companyB->id]);
        $receiptB = Receipt::factory()->create(['company_id' => $companyB->id, 'salesplay_account_id' => $accountB->id]);

        $userA = User::factory()->create(['company_id' => $companyA->id, 'role' => 'customer']);
        $this->actingAs($userA);

        $this->assertNull(Receipt::find($receiptB->id));
    }

    public function test_admin_can_see_receipts_across_all_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $accountA = SalesplayAccount::factory()->create(['company_id' => $companyA->id]);
        $accountB = SalesplayAccount::factory()->create(['company_id' => $companyB->id]);

        Receipt::factory()->create(['company_id' => $companyA->id, 'salesplay_account_id' => $accountA->id]);
        Receipt::factory()->create(['company_id' => $companyB->id, 'salesplay_account_id' => $accountB->id]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertCount(2, Receipt::all());
    }

    public function test_salesplay_api_token_is_never_exposed_in_serialized_output(): void
    {
        $account = SalesplayAccount::factory()->create(['api_token' => 'super-secret-token']);

        $this->assertArrayNotHasKey('api_token', $account->toArray());
        $this->assertStringNotContainsString('super-secret-token', $account->toJson());
        $this->assertEquals('super-secret-token', $account->api_token);
    }
}
