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
    'salesplay_receipt_id',
    'receipt_number',
    'transaction_date',
    'subtotal',
    'discount',
    'tax',
    'total',
    'payment_status',
    'raw_json',
])]
class Receipt extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
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
        return $this->hasMany(ReceiptItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
