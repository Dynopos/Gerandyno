<?php

namespace App\Policies;

use App\Models\SalesplayAccount;
use App\Models\User;

class SalesplayAccountPolicy
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
    public function view(User $user, SalesplayAccount $salesplayAccount): bool
    {
        return $user->isAdmin() || $user->company_id === $salesplayAccount->company_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * Only DynoPOS admins provision SalesPlay accounts and their API tokens.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SalesplayAccount $salesplayAccount): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SalesplayAccount $salesplayAccount): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SalesplayAccount $salesplayAccount): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SalesplayAccount $salesplayAccount): bool
    {
        return false;
    }
}
