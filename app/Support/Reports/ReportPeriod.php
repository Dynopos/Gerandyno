<?php

namespace App\Support\Reports;

use Carbon\CarbonImmutable;

final readonly class ReportPeriod
{
    public function __construct(
        public string $key,
        public string $label,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}
}
