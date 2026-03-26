<?php

namespace App\Services;

use App\Enums\DecisionSource;
use App\Enums\Priority;
use App\Enums\TaskCategory;
use App\Enums\TaskStatus;
use App\Models\RoutingDecision;
use App\Models\RoutingRule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Triage\TriageClient;
use App\Services\Triage\TriageException;
use App\Services\Triage\TriageResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a triage classification into a team assignment and records an
 * auditable RoutingDecision for every routing attempt.
 */
class TaskRouter
{
    public function __construct(
        private readonly TriageClient $client,
        private readonly float $minConfidence,
    ) {}

    public function route(Task $task): RoutingDecision
    {
        try {
            $result = $this->client->classify($task);
        } catch (TriageException $e) {
            Log::warning('Triage failed; sending task to review queue', [
                'task_id' => $task->id, 'error' => $e->getMessage(),
            ]);

            return $this->sendToReview($task, $e->getMessage());
        }

        $lowConfidence = $result->confidence < $this->minConfidence;
        $rule = RoutingRule::where('category', $result->category->value)->where('is_active', true)->first();
        $team = (! $lowConfidence && $rule) ? $rule->team : Team::triage();
        $priority = $this->resolvePriority($result->priority, $rule);

        return DB::transaction(function () use ($task, $result, $team, $priority, $lowConfidence) {
            $decision = $task->routingDecisions()->create([
                'source' => DecisionSource::from($result->source),
                'category' => $result->category->value,
                'priority' => $priority->value,
                'sentiment' => $result->sentiment,
                'confidence' => $result->confidence,
                'rationale' => $result->rationale,
                'suggested_actions' => $result->suggestedActions,
                'team_id' => $team->id,
                'model' => $result->model,
                'latency_ms' => $result->latencyMs,
            ]);

            $needsReview = $lowConfidence || $team->slug === Team::TRIAGE_SLUG;

            $task->update([
                'category' => $result->category->value,
                'priority' => $priority,
                'team_id' => $team->id,
                'status' => $needsReview ? TaskStatus::NeedsReview : TaskStatus::Routed,
                'routed_at' => now(),
            ]);

            return $decision;
        });
    }

    /**
     * Manual override by a human: always wins, recorded with full confidence.
     */
    public function override(Task $task, User $user, TaskCategory $category, Priority $priority, ?int $teamId, ?string $note): RoutingDecision
    {
        $teamId ??= RoutingRule::where('category', $category->value)->value('team_id') ?? Team::triage()->id;

        return DB::transaction(function () use ($task, $user, $category, $priority, $teamId, $note) {
            $decision = $task->routingDecisions()->create([
                'source' => DecisionSource::Manual,
                'category' => $category->value,
                'priority' => $priority->value,
                'confidence' => 1.0,
                'rationale' => $note ?: 'Manually routed by '.$user->name,
                'team_id' => $teamId,
                'decided_by' => $user->id,
            ]);

            $task->update([
                'category' => $category->value,
                'priority' => $priority,
                'team_id' => $teamId,
                'status' => TaskStatus::Routed,
                'routed_at' => now(),
            ]);

            return $decision;
        });
    }

    private function sendToReview(Task $task, string $reason): RoutingDecision
    {
        $triage = Team::triage();

        return DB::transaction(function () use ($task, $triage, $reason) {
            $decision = $task->routingDecisions()->create([
                'source' => DecisionSource::Fallback,
                'category' => TaskCategory::Other->value,
                'priority' => Priority::Medium->value,
                'confidence' => 0,
                'rationale' => 'Automatic triage unavailable: '.$reason,
                'team_id' => $triage->id,
            ]);

            $task->update([
                'team_id' => $triage->id,
                'status' => TaskStatus::NeedsReview,
                'routed_at' => now(),
            ]);

            return $decision;
        });
    }

    /**
     * Take the higher of the LLM priority and the rule's floor priority.
     */
    private function resolvePriority(Priority $llm, ?RoutingRule $rule): Priority
    {
        $floor = $rule?->default_priority;

        return ($floor && $floor->weight() > $llm->weight()) ? $floor : $llm;
    }
}
