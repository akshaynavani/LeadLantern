<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\RoutingDecision;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $days = min(max($request->integer('days', 30), 1), 365);
        $since = now()->subDays($days);

        $base = fn () => Task::query()->visibleTo($request->user())->where('tasks.created_at', '>=', $since);

        $byStatus = $base()->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $byCategory = $base()->whereNotNull('category')
            ->select('category', DB::raw('count(*) as total'))->groupBy('category')->pluck('total', 'category');
        $byPriority = $base()->whereNotNull('priority')
            ->select('priority', DB::raw('count(*) as total'))->groupBy('priority')->pluck('total', 'priority');
        $byTeam = $base()->join('teams', 'teams.id', '=', 'tasks.team_id')
            ->select('teams.name', DB::raw('count(*) as total'))->groupBy('teams.name')->pluck('total', 'name');

        $decisions = RoutingDecision::query()
            ->where('created_at', '>=', $since)
            ->whereIn('task_id', $base()->select('tasks.id'));

        $bySource = (clone $decisions)->select('source', DB::raw('count(*) as total'))
            ->groupBy('source')->pluck('total', 'source');
        $llm = (clone $decisions)->where('source', 'llm');

        // Override rate: tasks whose LLM decision was later corrected manually.
        $llmTaskIds = (clone $llm)->distinct()->pluck('task_id');
        $overridden = RoutingDecision::whereIn('task_id', $llmTaskIds)->where('source', 'manual')
            ->distinct()->count('task_id');

        return response()->json([
            'window_days' => $days,
            'totals' => [
                'tasks' => $base()->count(),
                'open' => $base()->whereNotIn('status', [TaskStatus::Resolved->value])->count(),
                'needs_review' => (int) ($byStatus[TaskStatus::NeedsReview->value] ?? 0),
            ],
            'by_status' => $byStatus,
            'by_category' => $byCategory,
            'by_priority' => $byPriority,
            'by_team' => $byTeam,
            'routing' => [
                'by_source' => $bySource,
                'avg_confidence' => round((float) (clone $llm)->avg('confidence'), 3),
                'avg_latency_ms' => (int) round((float) (clone $llm)->avg('latency_ms')),
                'override_rate' => $llmTaskIds->count() > 0 ? round($overridden / $llmTaskIds->count(), 3) : 0,
            ],
        ]);
    }
}
