<?php

namespace Tests\Unit;

use App\Enums\Priority;
use App\Enums\TaskCategory;
use App\Services\Triage\TriageResult;
use PHPUnit\Framework\TestCase;

class TriageResultTest extends TestCase
{
    public function test_unknown_values_are_normalised(): void
    {
        $r = TriageResult::fromArray([
            'category' => 'not-a-category',
            'priority' => 'whenever',
            'confidence' => 4.2,
            'source' => 'mystery',
        ]);

        $this->assertSame(TaskCategory::Other, $r->category);
        $this->assertSame(Priority::Medium, $r->priority);
        $this->assertSame(1.0, $r->confidence);
        $this->assertSame('llm', $r->source);
        $this->assertSame([], $r->suggestedActions);
    }

    public function test_valid_payload_is_preserved(): void
    {
        $r = TriageResult::fromArray([
            'category' => 'sales_lead', 'priority' => 'urgent', 'sentiment' => 'positive',
            'confidence' => 0.8, 'suggested_actions' => ['Book demo'], 'source' => 'fallback',
            'latency_ms' => '12',
        ]);

        $this->assertSame(TaskCategory::SalesLead, $r->category);
        $this->assertSame(Priority::Urgent, $r->priority);
        $this->assertSame(['Book demo'], $r->suggestedActions);
        $this->assertSame('fallback', $r->source);
        $this->assertSame(12, $r->latencyMs);
    }
}
