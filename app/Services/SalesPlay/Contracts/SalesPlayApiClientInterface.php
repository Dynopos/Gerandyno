<?php

namespace App\Services\SalesPlay\Contracts;

use App\Services\SalesPlay\DTO\SalesPlayReceiptPage;
use App\Services\SalesPlay\DTO\SalesPlayStockInPage;
use App\Services\SalesPlay\DTO\SalesPlayStockLevelPage;
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
        ?string $shopId,
        string $apiToken,
        ?CarbonInterface $since,
        ?string $cursor,
    ): SalesPlayReceiptPage;

    /**
     * Fetch one page of current stock-on-hand levels for a shop. This is a
     * snapshot of "right now", not an incremental feed, so there is no
     * "since" filter — a full sync pages through every level each time.
     *
     * @throws SalesPlayApiException
     */
    public function fetchStockLevels(
        ?string $shopId,
        string $apiToken,
        ?string $cursor,
    ): SalesPlayStockLevelPage;

    /**
     * Fetch one page of goods-received notes ("stock in") for a shop,
     * optionally filtered to those received since a given timestamp
     * (incremental sync) and/or starting from an opaque pagination cursor
     * returned by a previous call.
     *
     * @throws SalesPlayApiException
     */
    public function fetchStockIns(
        ?string $shopId,
        string $apiToken,
        ?CarbonInterface $since,
        ?string $cursor,
    ): SalesPlayStockInPage;
}
