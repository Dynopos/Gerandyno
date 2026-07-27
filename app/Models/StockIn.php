<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'salesplay_account_id',
    'salesplay_grn_id',
    'supplier_name',
    'invoice_no',
    'received_at',
    'total',
    'raw_json',
])]
class StockIn extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'total' => 'decimal:2',
            'raw_json' => 'array',
        ];
    }

    public function salesplayAccount(): BelongsTo
    {
        return $this->belongsTo(SalesplayAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockInItem::class);
    }
}
