<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'shop_name', 'salesplay_shop_id', 'api_token', 'status'])]
#[Hidden(['api_token'])]
class SalesplayAccount extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
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
