<?php

namespace Tests\Feature;

use App\Jobs\SendCustomerInviteEmail;
use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerCreateTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Kedai Ali',
            'shop_name' => 'Kedai Ali Cawangan 1',
            'salesplay_shop_id' => 'shop-001',
            'api_token' => 'token-abc',
            'customer_name' => 'Ali bin Abu',
            'customer_email' => 'ali@kedaiali.test',
        ], $overrides);
    }

    public function test_admin_can_create_a_customer_with_company_shop_and_login(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload());

        $response->assertRedirect('/admin/customers/create');

        $this->assertDatabaseHas('companies', ['name' => 'Kedai Ali']);
        $this->assertDatabaseHas('salesplay_accounts', ['salesplay_shop_id' => 'shop-001', 'shop_name' => 'Kedai Ali Cawangan 1']);
        $this->assertDatabaseHas('users', ['email' => 'ali@kedaiali.test', 'role' => 'customer']);

        $user = User::where('email', 'ali@kedaiali.test')->first();
        Queue::assertPushed(fn (SendCustomerInviteEmail $job) => $job->user->is($user));
    }

    public function test_admin_can_set_a_password_directly_and_no_invite_email_is_sent(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload(['customer_password' => 'a-strong-password']));

        $response->assertRedirect('/admin/customers/create');

        $user = User::where('email', 'ali@kedaiali.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
        $this->assertNotNull($user->email_verified_at);

        Queue::assertNotPushed(SendCustomerInviteEmail::class);
    }

    public function test_manual_password_must_be_at_least_8_characters(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload(['customer_password' => 'short']));

        $response->assertSessionHasErrors(['customer_password']);
        $this->assertDatabaseMissing('users', ['email' => 'ali@kedaiali.test']);
    }

    public function test_shop_id_is_optional(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload(['salesplay_shop_id' => '']));

        $response->assertRedirect('/admin/customers/create');
        $this->assertDatabaseHas('salesplay_accounts', ['shop_name' => 'Kedai Ali Cawangan 1', 'salesplay_shop_id' => null]);
    }

    public function test_customer_email_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $existingCompany = Company::factory()->create();
        User::factory()->create(['company_id' => $existingCompany->id, 'email' => 'ali@kedaiali.test']);

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload());

        $response->assertSessionHasErrors(['customer_email']);
        $this->assertDatabaseMissing('companies', ['name' => 'Kedai Ali']);
    }

    public function test_shop_id_must_be_unique_when_provided(): void
    {
        $admin = User::factory()->admin()->create();
        SalesplayAccount::factory()->create(['salesplay_shop_id' => 'shop-001']);

        $response = $this->actingAs($admin)->post('/admin/customers', $this->payload());

        $response->assertSessionHasErrors(['salesplay_shop_id']);
        $this->assertDatabaseMissing('users', ['email' => 'ali@kedaiali.test']);
    }

    public function test_required_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', []);

        $response->assertSessionHasErrors(['company_name', 'shop_name', 'api_token', 'customer_name', 'customer_email']);
    }

    public function test_non_admin_is_forbidden_from_creating_customers(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $this->actingAs($user)->get('/admin/customers/create')->assertForbidden();
    }
}
