<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $openedAt = fake()->dateTimeBetween('-30 days', '-1 hour');
        $expectedCash = fake()->randomFloat(2, 50, 1000);

        return [
            'company_id' => Company::factory(),
            'opened_at' => $openedAt,
            'closed_at' => fake()->dateTimeBetween($openedAt, 'now'),
            'expected_cash' => $expectedCash,
            'actual_cash' => $expectedCash,
            'difference' => 0,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'closed_at' => null,
            'expected_cash' => null,
            'actual_cash' => null,
            'difference' => null,
        ]);
    }
}
