<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SalesplayAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SalesplayAccount>
 */
class SalesplayAccountFactory extends Factory
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
            'shop_name' => fake()->unique()->company().' Shop',
            'salesplay_shop_id' => 'sp-'.Str::random(8),
            'api_token' => Str::random(40),
            'status' => 'active',
        ];
    }
}
