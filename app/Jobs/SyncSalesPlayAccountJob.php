<?php

namespace App\Jobs;

use App\Models\SalesplayAccount;
use App\Services\SalesPlay\SalesPlaySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Syncs a single SalesPlay account. Queued per-account (rather than one job
 * for all accounts) so that: (1) one account's failure never blocks another
 * account's sync, and (2) syncing scales horizontally across queue workers
 * as the number of accounts grows.
 */
class SyncSalesPlayAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * A failed sync is retried by the next scheduled run (every 15 minutes),
     * so the queue itself doesn't need to retry.
     */
    public int $tries = 1;

    public function __construct(
        public readonly SalesplayAccount $account,
    ) {}

    public function handle(SalesPlaySyncService $syncService): void
    {
        try {
            $result = $syncService->sync($this->account);

            Log::info('SalesPlay sync succeeded', [
                'salesplay_account_id' => $this->account->id,
                'company_id' => $this->account->company_id,
                'synced' => $result->synced,
                'skipped' => $result->skipped,
            ]);
        } catch (Throwable $e) {
            Log::error('SalesPlay sync failed', [
                'salesplay_account_id' => $this->account->id,
                'company_id' => $this->account->company_id,
                'error' => $e->getMessage(),
            ]);

            $this->account->update([
                'last_sync_status' => 'failed',
                'last_sync_error' => Str::limit($e->getMessage(), 1000),
            ]);

            throw $e;
        }
    }
}
