<?php

namespace App\Services\Triage;

use App\Models\Task;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for the Node.js / LangChain triage Lambda.
 */
class TriageClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $secret,
        private readonly int $timeout,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            rtrim((string) config('services.triage.url'), '/'),
            config('services.triage.secret'),
            (int) config('services.triage.timeout', 20),
        );
    }

    public function classify(Task $task): TriageResult
    {
        $task->loadMissing('contact', 'interaction');

        $history = $task->contact
            ? $task->contact->interactions()
                ->where('id', '!=', $task->interaction_id)
                ->limit(5)
                ->get(['channel', 'direction', 'subject', 'body', 'occurred_at'])
                ->map(fn ($i) => [
                    'channel' => $i->channel,
                    'direction' => $i->direction,
                    'subject' => $i->subject,
                    'body' => mb_substr($i->body, 0, 500),
                    'occurred_at' => $i->occurred_at?->toIso8601String(),
                ])->all()
            : [];

        $payload = [
            'task_id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'channel' => $task->channel,
            'contact' => $task->contact ? [
                'name' => $task->contact->full_name,
                'company' => $task->contact->company,
                'lifecycle_stage' => $task->contact->lifecycle_stage,
            ] : null,
            'recent_interactions' => $history,
        ];

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->acceptJson()
                ->withHeaders(array_filter(['X-Triage-Secret' => $this->secret]))
                ->post('/triage', $payload);
        } catch (ConnectionException $e) {
            throw new TriageException('Triage service unreachable: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new TriageException("Triage service returned HTTP {$response->status()}");
        }

        return TriageResult::fromArray($response->json() ?? []);
    }
}
