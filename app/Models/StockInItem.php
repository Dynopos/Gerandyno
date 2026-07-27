<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stock_in_id', 'product_id', 'product_name', 'quantity', 'unit_cost', 'total'])]
class StockInItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
