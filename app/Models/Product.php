<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'salesplay_product_id', 'name', 'category', 'sku', 'barcode', 'stock_on_hand', 'stock_synced_at'])]
class Product extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'stock_on_hand' => 'decimal:2',
            'stock_synced_at' => 'datetime',
        ];
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }
}
