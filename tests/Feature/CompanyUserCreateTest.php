<?php

namespace Tests\Feature;

use App\Jobs\SendCustomerInviteEmail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompanyUserCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_login_to_an_existing_company_via_email_invite(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/companies/{$company->id}/users", [
            'name' => 'Wardini',
            'email' => 'wardini@kedai.test',
        ]);

        $response->assertRedirect(route('admin.companies.edit', $company));

        $user = User::where('email', 'wardini@kedai.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->company_id === $company->id);
        $this->assertSame('customer', $user->role);

        Queue::assertPushed(fn (SendCustomerInviteEmail $job) => $job->user->is($user));
    }

    public function test_admin_can_set_a_password_directly_and_no_invite_email_is_sent(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->post("/admin/companies/{$company->id}/users", [
            'name' => 'Wardini',
            'email' => 'wardini@kedai.test',
            'password' => 'a-strong-password',
        ]);

        $user = User::where('email', 'wardini@kedai.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
        $this->assertNotNull($user->email_verified_at);

        Queue::assertNotPushed(SendCustomerInviteEmail::class);
    }

    public function test_email_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        User::factory()->create(['company_id' => $otherCompany->id, 'email' => 'wardini@kedai.test']);

        $response = $this->actingAs($admin)->post("/admin/companies/{$company->id}/users", [
            'name' => 'Wardini',
            'email' => 'wardini@kedai.test',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $this->actingAs($user)->get("/admin/companies/{$company->id}/users/create")->assertForbidden();
    }
}
