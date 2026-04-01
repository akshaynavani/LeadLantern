<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RbacTest extends TestCase
{
    public function test_agent_only_sees_own_team_tasks(): void
    {
        $sales = Team::where('slug', 'sales')->first();
        $support = Team::where('slug', 'support')->first();
        $agent = $this->userWithRole(Role::AGENT, ['team_id' => $sales->id]);

        $mine = Task::factory()->create(['team_id' => $sales->id]);
        $other = Task::factory()->create(['team_id' => $support->id]);

        Sanctum::actingAs($agent);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        $this->getJson("/api/tasks/{$other->id}")->assertForbidden();
    }

    public function test_manager_sees_all_tasks(): void
    {
        Task::factory()->count(3)->create();
        Sanctum::actingAs($this->userWithRole(Role::MANAGER));

        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_agent_cannot_trigger_routing_or_edit_rules(): void
    {
        $task = Task::factory()->create();
        Sanctum::actingAs($this->userWithRole(Role::AGENT));

        $this->postJson("/api/tasks/{$task->id}/route")->assertForbidden();
        $this->postJson('/api/routing-rules', ['category' => 'billing', 'team_id' => 1])->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
        $this->getJson('/api/dashboard/stats')->assertForbidden();
    }

    public function test_only_admin_manages_users(): void
    {
        Sanctum::actingAs($this->userWithRole(Role::MANAGER));
        $this->getJson('/api/users')->assertForbidden();

        Sanctum::actingAs($this->userWithRole(Role::ADMIN));
        $this->postJson('/api/users', [
            'name' => 'New Agent', 'email' => 'new@example.test',
            'password' => 'supersecret', 'role' => 'agent',
        ])->assertCreated()->assertJsonPath('role', 'agent');
    }
}
