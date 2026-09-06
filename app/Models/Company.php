<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'status', 'sst_registered', 'sst_no', 'ssm_no', 'address'])]
class Company extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sst_registered' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function salesplayAccounts(): HasMany
    {
        return $this->hasMany(SalesplayAccount::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
