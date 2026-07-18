<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Restrict every query on the model to the authenticated user's own
     * company. Admin users (no company_id, role=admin) are exempt so the
     * admin section can manage records across all tenants.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        $builder->where($model->getTable().'.company_id', $user->company_id);
    }
}
