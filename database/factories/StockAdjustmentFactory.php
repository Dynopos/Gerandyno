<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'company_id' => fn (array $attributes) => Product::find($attributes['product_id'])->company_id,
            'quantity' => fake()->randomFloat(2, 0, 500),
            'note' => 'Stock take',
            'adjusted_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
