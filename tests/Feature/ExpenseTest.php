<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company];
    }

    public function test_index_lists_expenses_within_the_selected_period_with_a_total(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Pasar',
            'amount' => 55.50,
            'expense_date' => now(),
        ]);

        Expense::factory()->create([
            'company_id' => $company->id,
            'category' => 'Gaji Staf',
            'amount' => 20,
            'expense_date' => now()->subYear(),
        ]);

        $response = $this->actingAs($user)->get('/expenses?filter=today');

        $response->assertOk();
        $response->assertSee('Pasar');
        $response->assertDontSee('Gaji Staf');
        $response->assertSee('RM 55.50');
    }

    public function test_customer_can_create_an_expense(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        $response = $this->actingAs($user)->post('/expenses', [
            'category' => 'Sewa',
            'description' => 'Sewa kedai bulan ini',
            'amount' => 1200,
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect('/expenses');
        $this->assertDatabaseHas('expenses', [
            'company_id' => $company->id,
            'category' => 'Sewa',
            'amount' => 1200,
            'created_by' => $user->id,
        ]);
    }

    public function test_create_requires_category_amount_and_date(): void
    {
        [$user] = $this->makeCustomerUser();

        $response = $this->actingAs($user)->post('/expenses', []);

        $response->assertSessionHasErrors(['category', 'amount', 'expense_date']);
    }

    public function test_customer_can_update_their_own_expense(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        $expense = Expense::factory()->create(['company_id' => $company->id, 'category' => 'Pasar']);

        $response = $this->actingAs($user)->put("/expenses/{$expense->id}", [
            'category' => 'Pasar (dikemaskini)',
            'amount' => 99.99,
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect('/expenses');
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category' => 'Pasar (dikemaskini)',
        ]);
    }

    public function test_customer_can_delete_their_own_expense(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        $expense = Expense::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete("/expenses/{$expense->id}");

        $response->assertRedirect('/expenses');
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_customer_cannot_edit_another_companys_expense(): void
    {
        [$userA] = $this->makeCustomerUser();
        [, $companyB] = $this->makeCustomerUser();

        $expenseB = Expense::factory()->create(['company_id' => $companyB->id]);

        $response = $this->actingAs($userA)->get("/expenses/{$expenseB->id}/edit");

        $response->assertNotFound();
    }

    public function test_customer_cannot_delete_another_companys_expense(): void
    {
        [$userA] = $this->makeCustomerUser();
        [, $companyB] = $this->makeCustomerUser();

        $expenseB = Expense::factory()->create(['company_id' => $companyB->id]);

        $response = $this->actingAs($userA)->delete("/expenses/{$expenseB->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('expenses', ['id' => $expenseB->id]);
    }

    public function test_admin_without_company_is_blocked_from_expenses(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/expenses')->assertForbidden();
    }
}
