<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'salesplay_product_id' => (string) fake()->unique()->numberBetween(1000, 999999),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['Food', 'Beverage', 'Retail', 'Service']),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'barcode' => fake()->unique()->ean13(),
        ];
    }
}
