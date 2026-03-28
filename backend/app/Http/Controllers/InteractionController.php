<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInteractionRequest;
use App\Http\Resources\InteractionResource;
use App\Models\Contact;
use App\Models\Task;
use App\Services\TaskRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class InteractionController extends Controller
{
    public function __construct(private readonly TaskRouter $router) {}

    public function index(Contact $contact): AnonymousResourceCollection
    {
        return InteractionResource::collection(
            $contact->interactions()->with('user', 'task')->paginate(50)
        );
    }

    /**
     * Log an interaction. Inbound interactions with create_task=true become
     * a task that is immediately sent through LLM triage.
     */
    public function store(StoreInteractionRequest $request, Contact $contact): JsonResponse
    {
        $data = $request->safe()->except('create_task');
        $interaction = $contact->interactions()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        $task = null;
        if ($request->boolean('create_task') && ($data['direction'] ?? 'inbound') === 'inbound') {
            $task = Task::create([
                'contact_id' => $contact->id,
                'interaction_id' => $interaction->id,
                'title' => $interaction->subject ?: Str::limit($interaction->body, 80),
                'description' => $interaction->body,
                'channel' => $interaction->channel,
                'status' => 'new',
                'created_by' => $request->user()->id,
            ]);
            $this->router->route($task);
        }

        return response()->json([
            'interaction' => new InteractionResource($interaction->load('user')),
            'task_id' => $task?->id,
        ], 201);
    }
}
