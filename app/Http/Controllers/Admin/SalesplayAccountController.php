<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalesplayAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesplayAccountController extends Controller
{
    public function index(): View
    {
        $accounts = SalesplayAccount::query()
            ->with('company')
            ->orderBy('shop_name')
            ->paginate(20);

        return view('admin.salesplay-accounts.index', ['accounts' => $accounts]);
    }

    public function create(Request $request): View
    {
        return view('admin.salesplay-accounts.create', [
            'companies' => Company::orderBy('name')->get(),
            'selectedCompanyId' => $request->integer('company_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            'api_token' => ['required', 'string', 'max:1000'],
        ]);

        $account = SalesplayAccount::create($data);

        return redirect()
            ->route('admin.salesplay-accounts.edit', $account)
            ->with('status', __('app.admin.accounts.created'));
    }

    public function edit(SalesplayAccount $salesplayAccount): View
    {
        return view('admin.salesplay-accounts.edit', [
            'account' => $salesplayAccount->load('company'),
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SalesplayAccount $salesplayAccount): RedirectResponse
    {
        $data = $request->validate($this->rules($salesplayAccount) + [
            'api_token' => ['nullable', 'string', 'max:1000'],
        ]);

        // A blank token field means "leave the stored token alone" — the token
        // is never rendered back into the form, so there is nothing to resubmit.
        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        $salesplayAccount->update($data);

        return redirect()
            ->route('admin.salesplay-accounts.edit', $salesplayAccount)
            ->with('status', __('app.admin.accounts.updated'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?SalesplayAccount $account = null): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'shop_name' => ['required', 'string', 'max:255'],
            'salesplay_shop_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('salesplay_accounts', 'salesplay_shop_id')
                    ->where('company_id', request()->integer('company_id'))
                    ->ignore($account),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
