<?php

namespace Database\Factories;

use App\Models\SalesplayAccount;
use App\Models\StockIn;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockIn>
 */
class StockInFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salesplay_account_id' => SalesplayAccount::factory(),
            'company_id' => fn (array $attributes) => SalesplayAccount::find($attributes['salesplay_account_id'])->company_id,
            'salesplay_grn_id' => 'grn-'.Str::random(12),
            'supplier_name' => fake()->company(),
            'invoice_no' => (string) fake()->unique()->numberBetween(10000, 999999),
            'received_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'total' => fake()->randomFloat(2, 50, 2000),
        ];
    }
}
