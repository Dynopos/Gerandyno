<?php

namespace App\Services\SalesPlay\Contracts;

use App\Services\SalesPlay\DTO\SalesPlayReceiptPage;
use App\Services\SalesPlay\Exceptions\SalesPlayApiException;
use Carbon\CarbonInterface;

/**
 * Abstraction over "however SalesPlay's API actually works". SalesPlaySyncService
 * only ever depends on this interface, so the concrete HTTP implementation
 * (endpoint paths, request/response shape) can be swapped or rewritten once
 * the real SalesPlay API documentation is confirmed, without touching any
 * sync/business logic.
 */
interface SalesPlayApiClientInterface
{
    /**
     * Fetch one page of receipts for a shop, optionally filtered to those
     * created/updated since a given timestamp (incremental sync) and/or
     * starting from an opaque pagination cursor returned by a previous call.
     *
     * @throws SalesPlayApiException
     */
    public function fetchReceipts(
        string $shopId,
        string $apiToken,
        ?CarbonInterface $since,
        ?string $cursor,
    ): SalesPlayReceiptPage;
}
