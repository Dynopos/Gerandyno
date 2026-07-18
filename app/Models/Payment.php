<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['receipt_id', 'payment_method', 'amount'])]
class Payment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
