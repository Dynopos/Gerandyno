<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/companies')->assertRedirect('/login');
    }

    public function test_customers_cannot_reach_the_admin_panel(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/companies')->assertForbidden();
        $this->actingAs($customer)->get('/admin/companies/create')->assertForbidden();
    }

    public function test_admin_sees_every_company(): void
    {
        $admin = User::factory()->admin()->create();
        Company::factory()->create(['name' => 'Kedai Satu']);
        Company::factory()->create(['name' => 'Kedai Dua']);

        $response = $this->actingAs($admin)->get('/admin/companies');

        $response->assertOk();
        $response->assertSee('Kedai Satu');
        $response->assertSee('Kedai Dua');
    }

    public function test_admin_can_create_a_company(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/companies', ['name' => 'Kedai Baharu', 'status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', ['name' => 'Kedai Baharu', 'status' => 'active']);
    }

    public function test_admin_can_update_a_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create(['name' => 'Lama', 'status' => 'active']);

        $this->actingAs($admin)
            ->put("/admin/companies/{$company->id}", ['name' => 'Baharu', 'status' => 'inactive'])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Baharu', 'status' => 'inactive']);
    }

    public function test_admin_can_create_a_customer_user_who_can_log_in(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->post("/admin/companies/{$company->id}/users", [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'password' => 'rahsia-panjang-123',
            'password_confirmation' => 'rahsia-panjang-123',
        ])->assertRedirect();

        $user = User::where('email', 'ali@example.com')->firstOrFail();

        $this->assertSame($company->id, $user->company_id);
        $this->assertSame('customer', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('rahsia-panjang-123', $user->password));

        $this->post('/login', ['email' => 'ali@example.com', 'password' => 'rahsia-panjang-123'])
            ->assertRedirect('/dashboard');
    }

    public function test_admin_cannot_delete_a_user_belonging_to_another_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/companies/{$company->id}/users/{$otherUser->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }
}
