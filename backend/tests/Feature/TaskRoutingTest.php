<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskRoutingTest extends TestCase
{
    private function fakeTriage(array $overrides = []): void
    {
        Http::fake([
            'triage.test/*' => Http::response(array_merge([
                'category' => 'billing',
                'priority' => 'high',
                'sentiment' => 'negative',
                'confidence' => 0.91,
                'rationale' => 'Customer reports a duplicate charge.',
                'suggested_actions' => ['Verify charge in Stripe', 'Issue refund'],
                'source' => 'llm',
                'model' => 'gpt-4o-mini',
                'latency_ms' => 840,
            ], $overrides)),
        ]);
    }

    public function test_creating_task_routes_it_to_rule_team(): void
    {
        $this->fakeTriage();
        Sanctum::actingAs($this->userWithRole(Role::MANAGER));

        $response = $this->postJson('/api/tasks', [
            'title' => 'Charged twice',
            'description' => 'I was billed twice this month, please refund.',
            'contact_id' => Contact::factory()->create()->id,
        ])->assertCreated();

        $response->assertJsonPath('status', 'routed')
            ->assertJsonPath('category', 'billing')
            ->assertJsonPath('priority', 'high')
            ->assertJsonPath('team.slug', 'billing')
            ->assertJsonPath('latest_decision.source', 'llm');

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Triage-Secret', 'test-secret')
            && $r['title'] === 'Charged twice');
    }

    public function test_low_confidence_goes_to_review_queue(): void
    {
        $this->fakeTriage(['confidence' => 0.3, 'category' => 'sales_lead']);
        Sanctum::actingAs($this->userWithRole(Role::MANAGER));

        $this->postJson('/api/tasks', ['title' => 'hmm', 'description' => 'unclear message'])
            ->assertCreated()
            ->assertJsonPath('status', 'needs_review')
            ->assertJsonPath('team.slug', Team::TRIAGE_SLUG);
    }

    public function test_rule_priority_acts_as_floor(): void
    {
        // churn_risk rule has an "urgent" default priority in TeamSeeder
        $this->fakeTriage(['category' => 'churn_risk', 'priority' => 'medium']);
        Sanctum::actingAs($this->userWithRole(Role::MANAGER));

        $this->postJson('/api/tasks', ['title' => 'Cancelling', 'description' => 'We will cancel.'])
            ->assertJsonPath('priority', 'urgent')
            ->assertJsonPath('team.slug', 'success');
    }

    public function test_triage_outage_falls_back_to_review(): void
    {
        Http::fake(['triage.test/*' => Http::response(['error' => 'boom'], 503)]);
        Sanctum::actingAs($this->userWithRole(Role::MANAGER));

        $this->postJson('/api/tasks', ['title' => 'x', 'description' => 'y'])
            ->assertCreated()
            ->assertJsonPath('status', 'needs_review')
            ->assertJsonPath('latest_decision.source', 'fallback');
    }

    public function test_manual_override_is_recorded(): void
    {
        $manager = $this->userWithRole(Role::MANAGER);
        $task = Task::factory()->create();
        $sales = Team::where('slug', 'sales')->first();
        Sanctum::actingAs($manager);

        $this->postJson("/api/tasks/{$task->id}/override", [
            'category' => 'sales_lead', 'priority' => 'high', 'note' => 'Enterprise prospect',
        ])->assertOk()
            ->assertJsonPath('source', 'manual')
            ->assertJsonPath('team.id', $sales->id)
            ->assertJsonPath('decided_by.id', $manager->id);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'team_id' => $sales->id, 'status' => 'routed']);
    }

    public function test_inbound_interaction_can_spawn_triaged_task(): void
    {
        $this->fakeTriage(['category' => 'technical_support']);
        $contact = Contact::factory()->create();
        Sanctum::actingAs($this->userWithRole(Role::AGENT));

        $taskId = $this->postJson("/api/contacts/{$contact->id}/interactions", [
            'channel' => 'email',
            'subject' => 'API down',
            'body' => 'All sync calls return 500.',
            'create_task' => true,
        ])->assertCreated()->json('task_id');

        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'category' => 'technical_support']);
        $this->assertDatabaseCount('routing_decisions', 1);
    }
}
