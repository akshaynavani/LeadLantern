<?php

namespace App\Services\Triage;

use App\Enums\Priority;
use App\Enums\TaskCategory;

/**
 * Normalised output from the triage service.
 */
final class TriageResult
{
    /**
     * @param  array<string>  $suggestedActions
     */
    public function __construct(
        public readonly TaskCategory $category,
        public readonly Priority $priority,
        public readonly string $sentiment,
        public readonly float $confidence,
        public readonly string $rationale,
        public readonly array $suggestedActions,
        public readonly string $source,
        public readonly ?string $model,
        public readonly ?int $latencyMs,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            category: TaskCategory::tryFrom($data['category'] ?? '') ?? TaskCategory::Other,
            priority: Priority::tryFrom($data['priority'] ?? '') ?? Priority::Medium,
            sentiment: (string) ($data['sentiment'] ?? 'neutral'),
            confidence: max(0.0, min(1.0, (float) ($data['confidence'] ?? 0))),
            rationale: (string) ($data['rationale'] ?? ''),
            suggestedActions: array_values(array_map('strval', $data['suggested_actions'] ?? [])),
            source: in_array($data['source'] ?? null, ['llm', 'fallback'], true) ? $data['source'] : 'llm',
            model: $data['model'] ?? null,
            latencyMs: isset($data['latency_ms']) ? (int) $data['latency_ms'] : null,
        );
    }
}
