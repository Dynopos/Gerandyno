<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\DTO\SalesInsight;
use App\Services\Ai\Exceptions\AiInsightException;
use App\Support\Reports\WeeklySalesSnapshot;

interface SalesInsightGenerator
{
    /**
     * Turn one week of sales figures into a readable review for the seller.
     *
     * @param  string  $locale  App locale ('ms'/'en') — the review is written
     *                          in the language the seller is reading the app in.
     *
     * @throws AiInsightException when the review cannot be generated.
     */
    public function generate(WeeklySalesSnapshot $snapshot, string $companyName, string $locale): SalesInsight;
}
