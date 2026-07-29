<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSalesPlayAccountJob;
use App\Models\Company;
use App\Models\SalesplayAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SalesplayAccountController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', SalesplayAccount::class);

        $accounts = SalesplayAccount::with('company')
            ->orderBy('shop_name')
            ->paginate(15);

        return view('admin.salesplay-accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SalesplayAccount::class);

        return view('admin.salesplay-accounts.create', [
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalesplayAccount::class);

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'shop_name' => ['required', 'string', 'max:255'],
            'salesplay_shop_id' => ['nullable', 'string', 'max:255'],
            'api_token' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $account = SalesplayAccount::create($validated);

        return redirect()->route('admin.salesplay-accounts.index')
            ->with('status', __('app.admin.salesplay_accounts.created', ['name' => $account->shop_name]));
    }

    public function edit(SalesplayAccount $salesplayAccount): View
    {
        $this->authorize('update', $salesplayAccount);

        return view('admin.salesplay-accounts.edit', [
            'account' => $salesplayAccount,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SalesplayAccount $salesplayAccount): RedirectResponse
    {
        $this->authorize('update', $salesplayAccount);

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'shop_name' => ['required', 'string', 'max:255'],
            'salesplay_shop_id' => ['nullable', 'string', 'max:255'],
            'api_token' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (blank($validated['api_token'] ?? null)) {
            unset($validated['api_token']);
        }

        $salesplayAccount->update($validated);

        return redirect()->route('admin.salesplay-accounts.index')
            ->with('status', __('app.admin.salesplay_accounts.updated', ['name' => $salesplayAccount->shop_name]));
    }

    /**
     * Runs the sync inline (not queued) so the admin gets an immediate
     * pass/fail result in the flash message, without depending on a queue
     * worker being up — the same reasoning behind the scheduled sync
     * using --now instead of dispatching to the queue.
     */
    public function sync(SalesplayAccount $salesplayAccount): RedirectResponse
    {
        $this->authorize('update', $salesplayAccount);

        try {
            SyncSalesPlayAccountJob::dispatchSync($salesplayAccount);
        } catch (Throwable) {
            // Already logged and persisted onto the account by the job itself.
        }

        $salesplayAccount->refresh();

        return redirect()->route('admin.salesplay-accounts.index')
            ->with('status', $salesplayAccount->last_sync_status === 'success'
                ? __('app.admin.salesplay_accounts.sync_success', ['name' => $salesplayAccount->shop_name])
                : __('app.admin.salesplay_accounts.sync_failed', [
                    'name' => $salesplayAccount->shop_name,
                    'error' => $salesplayAccount->last_sync_error,
                ]));
    }

    public function destroy(SalesplayAccount $salesplayAccount): RedirectResponse
    {
        $this->authorize('delete', $salesplayAccount);

        $name = $salesplayAccount->shop_name;

        $salesplayAccount->delete();

        return redirect()->route('admin.salesplay-accounts.index')
            ->with('status', __('app.admin.salesplay_accounts.deleted', ['name' => $name]));
    }
}
