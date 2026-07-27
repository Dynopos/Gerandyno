<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlayStockInPage
{
    /**
     * @param  array<int, SalesPlayStockInData>  $items
     * @param  string|null  $nextCursor  Opaque pagination cursor to pass back into the next fetch call.
     */
    public function __construct(
        public array $items,
        public bool $hasMore,
        public ?string $nextCursor,
    ) {}
}
