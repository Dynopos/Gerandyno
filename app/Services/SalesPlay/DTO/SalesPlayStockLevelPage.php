<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlayStockLevelPage
{
    /**
     * @param  array<int, SalesPlayStockLevelData>  $items
     * @param  string|null  $nextCursor  Opaque pagination cursor to pass back into the next fetch call.
     */
    public function __construct(
        public array $items,
        public bool $hasMore,
        public ?string $nextCursor,
    ) {}
}
