<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'DynoPOS Admin',
            'email' => 'admin@dynopos.test',
        ]);

        $company = Company::factory()->create(['name' => 'Kedai Demo Sdn Bhd']);

        User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'customer@dynopos.test',
            'company_id' => $company->id,
            'role' => 'customer',
        ]);

        $account = SalesplayAccount::factory()->create([
            'company_id' => $company->id,
            'shop_name' => 'Kedai Demo - Cawangan Utama',
            'last_synced_at' => now(),
        ]);

        $products = Product::factory(10)->create(['company_id' => $company->id]);

        Receipt::factory(50)
            ->create([
                'company_id' => $company->id,
                'salesplay_account_id' => $account->id,
            ])
            ->each(function (Receipt $receipt) use ($products) {
                $items = $products->random(random_int(1, 4));

                foreach ($items as $product) {
                    $quantity = random_int(1, 5);
                    $unitPrice = fake()->randomFloat(2, 5, 80);

                    ReceiptItem::factory()->create([
                        'receipt_id' => $receipt->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount' => 0,
                        'total' => round($quantity * $unitPrice, 2),
                    ]);
                }

                Payment::factory()->create([
                    'receipt_id' => $receipt->id,
                    'payment_method' => fake()->randomElement(['cash', 'card', 'ewallet']),
                    'amount' => $receipt->total,
                ]);
            });
    }
}
