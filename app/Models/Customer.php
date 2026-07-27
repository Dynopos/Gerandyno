<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'salesplay_customer_id',
    'name',
    'email',
    'phone',
    'address',
    'city',
    'region',
    'postal_code',
])]
class Customer extends Model
{
    use BelongsToCompany, HasFactory;

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
