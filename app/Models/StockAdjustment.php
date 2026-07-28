<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manual stock count correction ("stock take"), entered by the customer.
 * Establishes a new baseline for a product's balance as of adjusted_at —
 * see InventoryReportController for how this baseline is combined with
 * stock-in/stock-out movements since that point to compute a balance at any
 * later point in time.
 */
#[Fillable(['company_id', 'product_id', 'created_by', 'quantity', 'note', 'adjusted_at'])]
class StockAdjustment extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'adjusted_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
