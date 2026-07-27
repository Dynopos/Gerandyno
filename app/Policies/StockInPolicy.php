<?php

namespace App\Policies;

use App\Models\StockIn;
use App\Models\User;

class StockInPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StockIn $stockIn): bool
    {
        return $user->isAdmin() || $user->company_id === $stockIn->company_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * Stock-in records only ever arrive via SalesPlaySyncService, never user input.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StockIn $stockIn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockIn $stockIn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StockIn $stockIn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StockIn $stockIn): bool
    {
        return false;
    }
}
