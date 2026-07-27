<?php

namespace Database\Factories;

use App\Models\StockIn;
use App\Models\StockInItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockInItem>
 */
class StockInItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 50);
        $unitCost = fake()->randomFloat(2, 2, 40);

        return [
            'stock_in_id' => StockIn::factory(),
            'product_id' => null,
            'product_name' => fake()->words(2, true),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total' => round($quantity * $unitCost, 2),
        ];
    }
}
