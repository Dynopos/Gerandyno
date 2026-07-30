<?php

namespace Database\Factories;

use App\Models\SalesplayAccount;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $openedAt = fake()->dateTimeBetween('-30 days', '-1 hours');
        $closedAt = (clone $openedAt)->modify('+'.random_int(4, 10).' hours');
        $cashPayments = fake()->randomFloat(2, 20, 300);
        $expectedCash = round(100 + $cashPayments, 2);

        return [
            'salesplay_account_id' => SalesplayAccount::factory(),
            'company_id' => fn (array $attributes) => SalesplayAccount::find($attributes['salesplay_account_id'])->company_id,
            'salesplay_shift_id' => 'shift-'.Str::random(12),
            'pos_device_id' => 'SP'.fake()->numberBetween(10000000, 99999999),
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'opened_by_employee' => 'admin',
            'closed_by_employee' => 'admin',
            'starting_cash' => 100,
            'cash_payments' => $cashPayments,
            'cash_refunds' => 0,
            'paid_in' => 0,
            'paid_out' => 0,
            'expected_cash' => $expectedCash,
            'actual_cash' => $expectedCash,
            'gross_sales' => $cashPayments,
            'refunds' => 0,
            'discounts' => 0,
            'net_sales' => $cashPayments,
            'tip' => 0,
            'surcharge' => 0,
        ];
    }
}
