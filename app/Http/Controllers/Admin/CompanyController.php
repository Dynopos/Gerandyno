<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->withCount(['users', 'salesplayAccounts'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.companies.index', ['companies' => $companies]);
    }

    public function create(): View
    {
        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $company = Company::create($data);

        return redirect()
            ->route('admin.companies.edit', $company)
            ->with('status', __('app.admin.companies.created'));
    }

    public function edit(Company $company): View
    {
        $company->load([
            'users' => fn ($query) => $query->orderBy('name'),
            'salesplayAccounts' => fn ($query) => $query->orderBy('shop_name'),
        ]);

        return view('admin.companies.edit', ['company' => $company]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $company->update($data);

        return redirect()
            ->route('admin.companies.edit', $company)
            ->with('status', __('app.admin.companies.updated'));
    }
}
