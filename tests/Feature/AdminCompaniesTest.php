<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompaniesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_companies_index(): void
    {
        $admin = User::factory()->admin()->create();
        Company::factory()->create(['name' => 'Kedai Demo']);

        $response = $this->actingAs($admin)->get(route('admin.companies.index'));

        $response->assertOk();
        $response->assertSee('Kedai Demo');
    }

    public function test_admin_can_create_a_company(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.companies.store'), [
            'name' => 'New Company Sdn Bhd',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseHas('companies', [
            'name' => 'New Company Sdn Bhd',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_a_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->put(route('admin.companies.update', $company), [
            'name' => $company->name,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertSame('inactive', $company->fresh()->status);
    }

    public function test_admin_can_delete_a_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.companies.destroy', $company));

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_customer_cannot_access_the_admin_companies_panel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.companies.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.companies.create'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.companies.index'))->assertRedirect(route('login'));
    }
}
