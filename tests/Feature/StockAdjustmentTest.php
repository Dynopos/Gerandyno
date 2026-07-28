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

    public function test_customer_can_record_a_stock_adjustment(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Sugar 1kg']);

        $response = $this->actingAs($user)->post("/reports/inventory/{$product->id}/adjustment", [
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

    public function test_adjustment_requires_quantity_and_date(): void
    {
        [$user, $company] = $this->makeCustomerUser();

        $product = Product::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post("/reports/inventory/{$product->id}/adjustment", []);

        $response->assertSessionHasErrors(['quantity', 'adjusted_at']);
    }

    public function test_customer_cannot_adjust_another_companys_product(): void
    {
        [$userA] = $this->makeCustomerUser();
        [, $companyB] = $this->makeCustomerUser();

        $productB = Product::factory()->create(['company_id' => $companyB->id]);

        $response = $this->actingAs($userA)->get("/reports/inventory/{$productB->id}/adjustment");

        $response->assertNotFound();
    }

    public function test_customer_cannot_submit_an_adjustment_for_another_companys_product(): void
    {
        [$userA] = $this->makeCustomerUser();
        [, $companyB] = $this->makeCustomerUser();

        $productB = Product::factory()->create(['company_id' => $companyB->id]);

        $response = $this->actingAs($userA)->post("/reports/inventory/{$productB->id}/adjustment", [
            'quantity' => 10,
            'adjusted_at' => now()->format('Y-m-d'),
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('stock_adjustments', ['product_id' => $productB->id]);
    }

    public function test_admin_without_company_is_blocked_from_adjustments(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->get("/reports/inventory/{$product->id}/adjustment")->assertForbidden();
    }
}
