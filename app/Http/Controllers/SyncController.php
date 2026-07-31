<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSalesPlayAccountJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $inProgress = 0;

        foreach ($accounts as $account) {
            // Peek at the job's own lock before dispatching — see the admin
            // SalesplayAccountController::sync() for why this can't be
            // detected from the job's own return value or persisted state.
            $probe = Cache::lock(SyncSalesPlayAccountJob::lockKey($account), 300);

            if (! $probe->get()) {
                $inProgress++;

                continue;
            }

            $probe->release();

            try {
                SyncSalesPlayAccountJob::dispatchSync($account);
            } catch (Throwable) {
                // Already logged and persisted onto the account by the job itself.
            }

            if ($account->refresh()->last_sync_status === 'success') {
                $succeeded++;
            }
        }

        $failed = $accounts->count() - $succeeded - $inProgress;

        if ($failed === 0 && $inProgress === 0) {
            return back()->with('status', __('app.sync.success', ['count' => $succeeded]));
        }

        if ($failed === 0 && $succeeded === 0) {
            return back()->with('status', __('app.sync.in_progress'));
        }

        return back()->with('status', __('app.sync.partial', ['succeeded' => $succeeded, 'failed' => $failed]));
    }
}
