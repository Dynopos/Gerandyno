<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCustomerInviteEmail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Adds a login to an existing Company — split out from company/SalesPlay
 * account creation (CustomerController) since DynoPOS admins normally view
 * every tenant's reports themselves and don't need a customer-facing login
 * for most companies. This is for the cases where one is wanted after all,
 * e.g. so the admin (or the shop owner) can log in directly to check a
 * report instead of going through /admin.
 */
class CompanyUserController extends Controller
{
    public function create(Company $company): View
    {
        $this->authorize('update', $company);

        return view('admin.companies.users.create', ['company' => $company]);
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $manualPassword = $validated['password'] ?? null;

        $user = User::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $manualPassword ?? Str::random(40),
            'role' => 'customer',
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        if ($manualPassword === null) {
            SendCustomerInviteEmail::dispatch($user);
        }

        return redirect()->route('admin.companies.edit', $company)
            ->with('status', $manualPassword === null
                ? __('app.admin.company_users.created', ['name' => $validated['name']])
                : __('app.admin.company_users.created_with_password', ['name' => $validated['name']]));
    }
}
