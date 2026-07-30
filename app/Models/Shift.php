<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'salesplay_account_id',
    'salesplay_shift_id',
    'pos_device_id',
    'opened_at',
    'closed_at',
    'opened_by_employee',
    'closed_by_employee',
    'starting_cash',
    'cash_payments',
    'cash_refunds',
    'paid_in',
    'paid_out',
    'expected_cash',
    'actual_cash',
    'gross_sales',
    'refunds',
    'discounts',
    'net_sales',
    'tip',
    'surcharge',
    'raw_json',
])]
class Shift extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'starting_cash' => 'decimal:2',
            'cash_payments' => 'decimal:2',
            'cash_refunds' => 'decimal:2',
            'paid_in' => 'decimal:2',
            'paid_out' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'gross_sales' => 'decimal:2',
            'refunds' => 'decimal:2',
            'discounts' => 'decimal:2',
            'net_sales' => 'decimal:2',
            'tip' => 'decimal:2',
            'surcharge' => 'decimal:2',
            'raw_json' => 'array',
        ];
    }

    public function salesplayAccount(): BelongsTo
    {
        return $this->belongsTo(SalesplayAccount::class);
    }

    public function cashDifference(): float
    {
        return round((float) $this->actual_cash - (float) $this->expected_cash, 2);
    }
}
