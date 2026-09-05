<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['company_id' => null, 'role' => 'admin']);
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function customerLogin(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'customer',
            'password' => 'kata-laluan-lama',
        ]);

        return [$company, $user];
    }

    public function test_admin_sees_each_login_on_the_company_page(): void
    {
        [$company, $user] = $this->customerLogin();

        $response = $this->actingAs($this->admin())->get(route('admin.companies.edit', $company));

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee(route('admin.companies.users.password.edit', [$company, $user]));
    }

    public function test_admin_can_set_a_new_password(): void
    {
        [$company, $user] = $this->customerLogin();

        $this->actingAs($this->admin())
            ->put(route('admin.companies.users.password.update', [$company, $user]), [
                'password' => 'kata-laluan-baharu',
                'password_confirmation' => 'kata-laluan-baharu',
            ])
            ->assertRedirect(route('admin.companies.edit', $company))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(Hash::check('kata-laluan-baharu', $user->password));
        $this->assertFalse(Hash::check('kata-laluan-lama', $user->password));
    }

    public function test_the_merchant_can_log_in_with_the_new_password_and_not_the_old_one(): void
    {
        [$company, $user] = $this->customerLogin();

        $this->actingAs($this->admin())
            ->put(route('admin.companies.users.password.update', [$company, $user]), [
                'password' => 'kata-laluan-baharu',
                'password_confirmation' => 'kata-laluan-baharu',
            ]);

        // Drop the admin's authenticated state — /login is guest-only, and a
        // still-signed-in admin would be redirected instead of validating.
        $this->app['auth']->forgetGuards();

        $this->post('/login', ['email' => $user->email, 'password' => 'kata-laluan-lama'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'kata-laluan-baharu']);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * A device that ticked "remember me" under the old password must not
     * stay signed in after a reset.
     */
    public function test_resetting_invalidates_the_remember_me_token(): void
    {
        [$company, $user] = $this->customerLogin();
        $user->forceFill(['remember_token' => 'token-lama'])->save();

        $this->actingAs($this->admin())
            ->put(route('admin.companies.users.password.update', [$company, $user]), [
                'password' => 'kata-laluan-baharu',
                'password_confirmation' => 'kata-laluan-baharu',
            ]);

        $this->assertNotSame('token-lama', $user->refresh()->remember_token);
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        [$company, $user] = $this->customerLogin();

        $this->actingAs($this->admin())
            ->from(route('admin.companies.users.password.edit', [$company, $user]))
            ->put(route('admin.companies.users.password.update', [$company, $user]), [
                'password' => 'kata-laluan-baharu',
                'password_confirmation' => 'tersalah-taip',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin())
            ->from(route('admin.companies.users.password.edit', [$company, $user]))
            ->put(route('admin.companies.users.password.update', [$company, $user]), [
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('kata-laluan-lama', $user->refresh()->password));
    }

    public function test_a_login_from_another_company_cannot_be_reached(): void
    {
        [$company] = $this->customerLogin();
        [, $otherUser] = $this->customerLogin();

        $this->actingAs($this->admin())
            ->get(route('admin.companies.users.password.edit', [$company, $otherUser]))
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->put(route('admin.companies.users.password.update', [$company, $otherUser]), [
                'password' => 'kata-laluan-baharu',
                'password_confirmation' => 'kata-laluan-baharu',
            ])
            ->assertNotFound();

        $this->assertTrue(Hash::check('kata-laluan-lama', $otherUser->refresh()->password));
    }

    public function test_a_customer_cannot_reset_anyones_password(): void
    {
        [$company, $user] = $this->customerLogin();

        $this->actingAs($user)
            ->get(route('admin.companies.users.password.edit', [$company, $user]))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.companies.users.password.update', [$company, $user]), [
                'password' => 'kata-laluan-baharu',
                'password_confirmation' => 'kata-laluan-baharu',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('kata-laluan-lama', $user->refresh()->password));
    }

    public function test_guests_are_sent_to_login(): void
    {
        [$company, $user] = $this->customerLogin();

        $this->get(route('admin.companies.users.password.edit', [$company, $user]))
            ->assertRedirect('/login');
    }
}
