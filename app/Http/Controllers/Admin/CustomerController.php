<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCustomerInviteEmail;
use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Single-row way for an admin to onboard a company: creates the Company,
 * its SalesplayAccount, and a login User all in one submit, instead of
 * using the separate Company/SalesPlay Account/Login admin forms one
 * after another. salesplay_shop_id is optional since merchants often
 * can't find theirs right away — it's auto-discovered from the first
 * synced receipt, or can be filled in later on the SalesPlay Account.
 */
class CustomerController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('admin.customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'shop_name' => ['required', 'string', 'max:255'],
            'salesplay_shop_id' => ['nullable', 'string', 'max:255', Rule::unique('salesplay_accounts', 'salesplay_shop_id')],
            'api_token' => ['required', 'string'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'customer_password' => ['nullable', 'string', 'min:8'],
        ]);

        $manualPassword = $validated['customer_password'] ?? null;

        $user = DB::transaction(function () use ($validated, $manualPassword): User {
            $company = Company::create(['name' => $validated['company_name'], 'status' => 'active']);

            SalesplayAccount::create([
                'company_id' => $company->id,
                'shop_name' => $validated['shop_name'],
                'salesplay_shop_id' => $validated['salesplay_shop_id'] ?: null,
                'api_token' => $validated['api_token'],
                'status' => 'active',
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'password' => $manualPassword ?? Str::random(40),
                'role' => 'customer',
            ]);

            // email_verified_at isn't mass-assignable on User, so set it
            // separately — harmless either way since User doesn't implement
            // MustVerifyEmail, but keeps the record accurate.
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });

        // Only the email-invite path needs a password-reset link — when the
        // admin sets a password directly, the customer already has usable
        // credentials and doesn't depend on mail delivery to log in.
        if ($manualPassword === null) {
            SendCustomerInviteEmail::dispatch($user);
        }

        return redirect()->route('admin.customers.create')
            ->with('status', $manualPassword === null
                ? __('app.admin.customers.created', ['name' => $validated['company_name']])
                : __('app.admin.customers.created_with_password', ['name' => $validated['company_name']]));
    }
}
