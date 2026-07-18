<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (! $model->company_id && Auth::check() && Auth::user()->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
