<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
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
            'category' => fake()->randomElement(['Pasar', 'Gaji Staf', 'Sewa', 'Utiliti', 'Lain-lain']),
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 10, 2000),
            'expense_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
