<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlaySyncResult
{
    public function __construct(
        public int $synced,
        public int $skipped,
    ) {}
}
