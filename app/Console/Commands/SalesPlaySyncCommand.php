<?php

namespace App\Console\Commands;

use App\Jobs\SyncSalesPlayAccountJob;
use App\Models\SalesplayAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('salesplay:sync {--now : Run synchronously in this process instead of dispatching to the queue}')]
#[Description('Sync new transactions from SalesPlay for every active SalesPlay account')]
class SalesPlaySyncCommand extends Command
{
    /**
     * Execute the console command.
     *
     * Loops every active SalesPlay account (belonging to an active company)
     * and syncs it independently — one account's failure is logged and does
     * not stop the others. By default each account is dispatched as a
     * queued job so syncing scales across workers; pass --now to run all
     * accounts synchronously in this process (useful for local development
     * or a one-off manual sync without a queue worker running).
     */
    public function handle(): int
    {
        $accounts = SalesplayAccount::where('status', 'active')
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('No active SalesPlay accounts to sync.');

            return self::SUCCESS;
        }

        $this->info("Syncing {$accounts->count()} active SalesPlay account(s)...");

        foreach ($accounts as $account) {
            if (! $this->option('now')) {
                SyncSalesPlayAccountJob::dispatch($account);

                continue;
            }

            // dispatchSync() runs the job inline, so a thrown exception would
            // otherwise abort this loop and skip every remaining account.
            // The job itself already logs the failure and persists it onto
            // the account (last_sync_status/last_sync_error) before
            // rethrowing, so it's safe to just swallow it here and move on.
            try {
                SyncSalesPlayAccountJob::dispatchSync($account);
            } catch (Throwable) {
                continue;
            }
        }

        $this->info($this->option('now')
            ? 'Sync complete.'
            : 'Sync jobs dispatched to the queue.');

        return self::SUCCESS;
    }
}
