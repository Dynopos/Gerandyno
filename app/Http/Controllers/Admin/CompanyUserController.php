<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class CompanyUserController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $company->users()->make([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'customer',
        ]);

        // Admin-provisioned accounts skip the verification email — the admin
        // hands the credentials over directly.
        $user->email_verified_at = now();
        $user->save();

        return redirect()
            ->route('admin.companies.edit', $company)
            ->with('status', __('app.admin.users.created'));
    }

    public function destroy(Company $company, User $user): RedirectResponse
    {
        abort_unless($user->company_id === $company->id, 404);

        $user->delete();

        return redirect()
            ->route('admin.companies.edit', $company)
            ->with('status', __('app.admin.users.deleted'));
    }
}
