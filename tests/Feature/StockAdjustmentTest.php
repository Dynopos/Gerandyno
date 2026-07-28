<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        return [$user, $company];
    }

    public function test_create_lists_only_own_companys_products(): void
    {
        [$user, $company] = $this->makeCustomerUser();
        [, $companyB] = $this->makeCustomerUser();

        Product::factory()->create(['company_id' => $company->id, 'name' => 'Mine Product']);
        Product::factory()->create(['company_id' => $companyB->id, 'name' => 'Not Mine Product']);

        $response = $this->actingAs($user)->get('/reports/inventory/adjustment');

        $response->assertOk();
        $response->assertSee('Mine Product');
        $response->assertDontSee('Not Mine Product');
    }

    public function test_customer_can_record_a_stock_adjustment(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Sugar 1kg']);

        $response = $this->actingAs($user)->post('/reports/inventory/adjustment', [
            'product_id' => $product->id,
            'quantity' => 42,
            'adjusted_at' => now()->format('Y-m-d'),
            'note' => 'Stock take Julai',
        ]);

        $response->assertRedirect('/reports/inventory');
        $this->assertDatabaseHas('stock_adjustments', [
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => 42,
            'created_by' => $user->id,
        ]);
    }

    public function test_adjustment_requires_product_quantity_and_date(): void
    {
        [$user] = $this->makeCustomerUser();

        $response = $this->actingAs($user)->post('/reports/inventory/adjustment', []);

        $response->assertSessionHasErrors(['product_id', 'quantity', 'adjusted_at']);
    }

    public function test_customer_cannot_submit_an_adjustment_for_another_companys_product(): void
    {
        [$userA] = $this->makeCustomerUser();
        [, $companyB] = $this->makeCustomerUser();

        $productB = Product::factory()->create(['company_id' => $companyB->id]);

        $response = $this->actingAs($userA)->post('/reports/inventory/adjustment', [
            'product_id' => $productB->id,
            'quantity' => 10,
            'adjusted_at' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['product_id']);
        $this->assertDatabaseMissing('stock_adjustments', ['product_id' => $productB->id]);
    }

    public function test_admin_without_company_is_blocked_from_adjustments(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/reports/inventory/adjustment')->assertForbidden();
    }
}
