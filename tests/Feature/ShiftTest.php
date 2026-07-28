<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\SalesplayAccount;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerUser(): array
    {
        $company = Company::factory()->create();
        $account = SalesplayAccount::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company, $account];
    }

    public function test_customer_can_start_a_shift(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        $response = $this->actingAs($user)->post('/reports/shifts/start');

        $response->assertRedirect('/reports/shifts');
        $this->assertDatabaseHas('shifts', [
            'company_id' => $company->id,
            'opened_by' => $user->id,
            'closed_at' => null,
        ]);
    }

    public function test_customer_cannot_start_a_shift_when_one_is_already_open(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        Shift::factory()->open()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post('/reports/shifts/start');

        $this->assertSame(1, Shift::withoutGlobalScopes()->where('company_id', $company->id)->count());
    }

    public function test_ending_a_shift_computes_expected_cash_from_cash_payments_since_it_opened(): void
    {
        [$user, $company, $account] = $this->makeCustomerUser();

        $shift = Shift::factory()->open()->create([
            'company_id' => $company->id,
            'opened_at' => now()->subHours(2),
        ]);

        // Before the shift opened - must be excluded.
        $beforeReceipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now()->subHours(3),
        ]);
        Payment::factory()->create(['receipt_id' => $beforeReceipt->id, 'payment_method' => 'cash', 'amount' => 40]);

        // During the shift - cash counts, card doesn't.
        $duringReceipt = Receipt::factory()->create([
            'company_id' => $company->id,
            'salesplay_account_id' => $account->id,
            'transaction_date' => now()->subHour(),
        ]);
        Payment::factory()->create(['receipt_id' => $duringReceipt->id, 'payment_method' => 'cash', 'amount' => 50]);
        Payment::factory()->create(['receipt_id' => $duringReceipt->id, 'payment_method' => 'card', 'amount' => 30]);

        $response = $this->actingAs($user)->post('/reports/shifts/end', [
            'actual_cash' => 45,
        ]);

        $response->assertRedirect('/reports/shifts');

        $shift->refresh();
        $this->assertSame('50.00', $shift->expected_cash);
        $this->assertSame('45.00', $shift->actual_cash);
        $this->assertSame('-5.00', $shift->difference);
        $this->assertNotNull($shift->closed_at);
        $this->assertSame($user->id, $shift->closed_by);
    }

    public function test_customer_cannot_end_a_shift_when_none_is_open(): void
    {
        [$user] = $this->makeCustomerUser();

        $response = $this->actingAs($user)->post('/reports/shifts/end', ['actual_cash' => 10]);

        $response->assertRedirect();
        $this->assertSame(0, Shift::count());
    }

    public function test_end_shift_requires_actual_cash(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        Shift::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post('/reports/shifts/end', []);

        $response->assertSessionHasErrors(['actual_cash']);
    }

    public function test_cash_totals_are_scoped_to_own_company(): void
    {
        [$user, $company] = $this->makeCustomerUser();
        [, $companyB, $accountB] = $this->makeCustomerUser();

        $shift = Shift::factory()->open()->create([
            'company_id' => $company->id,
            'opened_at' => now()->subHour(),
        ]);

        $receiptB = Receipt::factory()->create([
            'company_id' => $companyB->id,
            'salesplay_account_id' => $accountB->id,
            'transaction_date' => now(),
        ]);
        Payment::factory()->create(['receipt_id' => $receiptB->id, 'payment_method' => 'cash', 'amount' => 999]);

        $this->actingAs($user)->post('/reports/shifts/end', ['actual_cash' => 0]);

        $shift->refresh();
        $this->assertSame('0.00', $shift->expected_cash);
    }

    public function test_index_shows_open_shift_and_history(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        Shift::factory()->create([
            'company_id' => $company->id,
            'expected_cash' => 100,
            'actual_cash' => 100,
            'difference' => 0,
        ]);

        Shift::factory()->open()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get('/reports/shifts');

        $response->assertOk();
        $response->assertSee(__('app.reports.shifts.open'));
    }

    public function test_admin_without_company_is_blocked_from_shifts(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/shifts')->assertForbidden();
    }
}
