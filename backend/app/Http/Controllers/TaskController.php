<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\TaskCategory;
use App\Http\Requests\OverrideRoutingRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\RoutingDecisionResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskRouter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function __construct(private readonly TaskRouter $router) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string'],
            'team_id' => ['sometimes', 'integer'],
            'q' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tasks = Task::query()
            ->visibleTo($request->user())
            ->with(['team', 'assignee', 'contact', 'latestDecision'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->whereIn('status', explode(',', $v)))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->whereIn('priority', explode(',', $v)))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['team_id'] ?? null, fn ($q, $v) => $q->where('team_id', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$v}%")
                ->orWhere('description', 'like', "%{$v}%")))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->latest('id')
            ->paginate($filters['per_page'] ?? 25);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        $this->authorize('create', Task::class);

        $task = Task::create([
            ...$request->safe()->except('auto_route'),
            'created_by' => $request->user()->id,
            'status' => 'new',
        ]);

        if ($request->boolean('auto_route', true)) {
            $this->router->route($task);
        }

        return new TaskResource($task->fresh(['team', 'assignee', 'contact', 'latestDecision']));
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load([
            'team', 'assignee', 'contact', 'latestDecision',
            'routingDecisions.team', 'routingDecisions.decider',
        ]));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task->fresh(['team', 'assignee', 'contact', 'latestDecision']));
    }

    public function destroy(Task $task): Response
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->noContent();
    }

    /** Re-run LLM triage for a task. */
    public function route(Task $task): RoutingDecisionResource
    {
        $this->authorize('route', $task);

        return new RoutingDecisionResource($this->router->route($task)->load('team'));
    }

    /** Manually override routing. */
    public function override(OverrideRoutingRequest $request, Task $task): RoutingDecisionResource
    {
        $this->authorize('route', $task);

        $decision = $this->router->override(
            $task,
            $request->user(),
            TaskCategory::from($request->input('category')),
            Priority::from($request->input('priority')),
            $request->input('team_id'),
            $request->input('note'),
        );

        return new RoutingDecisionResource($decision->load('team', 'decider'));
    }

    public function decisions(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        return RoutingDecisionResource::collection(
            $task->routingDecisions()->with('team', 'decider')->get()
        );
    }
}
