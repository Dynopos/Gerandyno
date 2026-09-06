<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::withCount(['users', 'salesplayAccounts'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.companies.index', [
            'companies' => $companies,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'sst_registered' => ['boolean'],
            'sst_no' => ['nullable', 'string', 'max:255'],
            'ssm_no' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['sst_registered'] = $request->boolean('sst_registered');

        $company = Company::create($validated);

        return redirect()->route('admin.companies.index')
            ->with('status', __('app.admin.companies.created', ['name' => $company->name]));
    }

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        $company->load(['users' => fn ($query) => $query->orderBy('name')]);

        return view('admin.companies.edit', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'sst_registered' => ['boolean'],
            'sst_no' => ['nullable', 'string', 'max:255'],
            'ssm_no' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['sst_registered'] = $request->boolean('sst_registered');

        $company->update($validated);

        return redirect()->route('admin.companies.index')
            ->with('status', __('app.admin.companies.updated', ['name' => $company->name]));
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $name = $company->name;

        $company->delete();

        return redirect()->route('admin.companies.index')
            ->with('status', __('app.admin.companies.deleted', ['name' => $name]));
    }
}
