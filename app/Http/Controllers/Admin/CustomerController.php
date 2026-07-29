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
 * Single-row counterpart to CustomerImportController: lets an admin
 * provision one customer (Company + SalesplayAccount + login User) through
 * a normal form instead of preparing a CSV, for the common case of adding
 * customers one at a time. Unlike the bulk import, salesplay_shop_id is
 * optional here since not every merchant can find theirs right away — the
 * SalesPlay account can be edited later once it's known.
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
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $company = Company::create(['name' => $validated['company_name'], 'status' => 'active']);

            SalesplayAccount::create([
                'company_id' => $company->id,
                'shop_name' => $validated['shop_name'],
                'salesplay_shop_id' => $validated['salesplay_shop_id'] ?: null,
                'api_token' => $validated['api_token'],
                'status' => 'active',
            ]);

            return User::create([
                'company_id' => $company->id,
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'password' => Str::random(40),
                'role' => 'customer',
            ]);
        });

        SendCustomerInviteEmail::dispatch($user);

        return redirect()->route('admin.customers.create')
            ->with('status', __('app.admin.customers.created', ['name' => $validated['customer_name']]));
    }
}
