<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalesplayAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Quick single-row way for an admin to onboard a company + its SalesPlay
 * account, instead of using the separate Company and SalesPlay Account
 * admin forms one after another. Doesn't create a login — DynoPOS is used
 * by DynoPOS admins to view every tenant's reports, not by the shop owners
 * themselves, so no customer-facing account is needed here.
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
        ]);

        DB::transaction(function () use ($validated): void {
            $company = Company::create(['name' => $validated['company_name'], 'status' => 'active']);

            SalesplayAccount::create([
                'company_id' => $company->id,
                'shop_name' => $validated['shop_name'],
                'salesplay_shop_id' => $validated['salesplay_shop_id'] ?: null,
                'api_token' => $validated['api_token'],
                'status' => 'active',
            ]);
        });

        return redirect()->route('admin.customers.create')
            ->with('status', __('app.admin.customers.created', ['name' => $validated['company_name']]));
    }
}
