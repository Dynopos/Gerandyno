<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerUser(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_index_lists_shifts_within_the_selected_period(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        Shift::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'pos_device_id' => 'SP12345',
            'opened_at' => now(),
            'closed_at' => now()->addHours(2),
        ]);

        Shift::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'pos_device_id' => 'SP99999',
            'opened_at' => now()->subYear(),
            'closed_at' => now()->subYear()->addHours(2),
        ]);

        $response = $this->actingAs($user)->get('/reports/shifts?filter=today');

        $response->assertOk();
        $response->assertSee('SP12345');
        $response->assertDontSee('SP99999');
    }

    public function test_index_shows_a_shortage_when_actual_cash_is_below_expected(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        Shift::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'opened_at' => now(),
            'closed_at' => now()->addHours(2),
            'expected_cash' => 445.25,
            'actual_cash' => 0,
        ]);

        $response = $this->actingAs($user)->get('/reports/shifts?filter=today');

        $response->assertOk();
        $response->assertSee('RM 445.25');
        $response->assertSee('RM -445.25');
    }

    public function test_a_shift_still_open_shows_as_still_open_instead_of_a_closing_time(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        Shift::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'opened_at' => now(),
            'closed_at' => null,
            'expected_cash' => 136.50,
            'actual_cash' => 0,
        ]);

        $response = $this->actingAs($user)->get('/reports/shifts?filter=today');

        $response->assertOk();
        $response->assertSee(__('app.reports.shifts.still_open'));
        // An open shift hasn't been cash-counted yet, so actual_cash is just
        // a placeholder zero — it must not be shown as a false shortage.
        $response->assertDontSee('RM -136.50');
    }

    public function test_a_shift_from_another_company_is_never_shown(): void
    {
        [$user] = $this->makeCustomerUser();

        $otherCompany = Company::factory()->create();
        $otherAccount = SalesplayAccount::factory()->create(['company_id' => $otherCompany->id]);

        Shift::factory()->create([
            'company_id' => $otherCompany->id,
            'salesplay_account_id' => $otherAccount->id,
            'pos_device_id' => 'SP-OTHER',
            'opened_at' => now(),
            'closed_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($user)->get('/reports/shifts?filter=today');

        $response->assertOk();
        $response->assertDontSee('SP-OTHER');
    }

    public function test_index_is_blocked_for_admin_without_company(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/shifts')->assertForbidden();
    }
}
