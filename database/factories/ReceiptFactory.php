<?php

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\SalesplayAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $discount = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $tax = round(($subtotal - $discount) * 0.06, 2);

        return [
            'salesplay_account_id' => SalesplayAccount::factory(),
            'company_id' => fn (array $attributes) => SalesplayAccount::find($attributes['salesplay_account_id'])->company_id,
            'salesplay_receipt_id' => 'rcpt-'.Str::random(12),
            'receipt_number' => (string) fake()->unique()->numberBetween(10000, 999999),
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => round($subtotal - $discount + $tax, 2),
            'payment_status' => 'paid',
        ];
    }
}
