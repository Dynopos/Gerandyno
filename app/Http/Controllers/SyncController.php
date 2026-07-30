<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSalesPlayAccountJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Lets a customer trigger a sync for their own company's SalesPlay
 * account(s) directly, instead of having to call/message us to ask for one
 * — the same underlying sync the admin "Sync Now" button and the 15-minute
 * scheduler both use, so it's safe to run at any time (per-account locking
 * in the job prevents it from racing a sync already in progress).
 */
class SyncController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $accounts = $request->user()->company->salesplayAccounts;

        if ($accounts->isEmpty()) {
            return back()->with('status', __('app.sync.no_accounts'));
        }

        $succeeded = 0;

        foreach ($accounts as $account) {
            try {
                SyncSalesPlayAccountJob::dispatchSync($account);
            } catch (Throwable) {
                // Already logged and persisted onto the account by the job itself.
            }

            if ($account->refresh()->last_sync_status === 'success') {
                $succeeded++;
            }
        }

        $failed = $accounts->count() - $succeeded;

        return back()->with('status', $failed === 0
            ? __('app.sync.success', ['count' => $succeeded])
            : __('app.sync.partial', ['succeeded' => $succeeded, 'failed' => $failed]));
    }
}
