<?php

namespace App\Services\Ai\DTO;

use Carbon\CarbonImmutable;

/**
 * One generated weekly review: a headline, a short narrative summary, the
 * numbers worth noticing, and concrete things the seller can do next week.
 *
 * Cached as a plain array (see toArray()/fromArray()) so a generated review
 * survives page reloads without spending another API call. `source` records
 * which generator wrote it, so a cached review stays readable if the app
 * later gains a second provider.
 */
final readonly class SalesInsight
{
    public const SOURCE_OPENAI = 'openai';

    /**
     * @param  array<int, string>  $highlights
     * @param  array<int, array{title: string, detail: string}>  $advice
     */
    public function __construct(
        public string $headline,
        public string $summary,
        public array $highlights,
        public array $advice,
        public string $source,
        public CarbonImmutable $generatedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'headline' => $this->headline,
            'summary' => $this->summary,
            'highlights' => $this->highlights,
            'advice' => $this->advice,
            'source' => $this->source,
            'generated_at' => $this->generatedAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            headline: (string) ($data['headline'] ?? ''),
            summary: (string) ($data['summary'] ?? ''),
            highlights: array_values((array) ($data['highlights'] ?? [])),
            advice: array_values((array) ($data['advice'] ?? [])),
            source: (string) ($data['source'] ?? self::SOURCE_OPENAI),
            generatedAt: CarbonImmutable::parse($data['generated_at'] ?? now()),
        );
    }
}
