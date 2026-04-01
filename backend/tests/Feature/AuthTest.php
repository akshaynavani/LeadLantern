<?php

namespace Tests\Feature;

use App\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_returns_token_and_permissions(): void
    {
        $this->userWithRole(Role::MANAGER, ['email' => 'm@example.test']);

        $this->postJson('/api/auth/login', ['email' => 'm@example.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'role'], 'permissions'])
            ->assertJsonPath('user.role', 'manager');
    }

    public function test_login_rejects_bad_password_and_inactive_users(): void
    {
        $this->userWithRole(Role::AGENT, ['email' => 'a@example.test']);
        $this->userWithRole(Role::AGENT, ['email' => 'off@example.test', 'is_active' => false]);

        $this->postJson('/api/auth/login', ['email' => 'a@example.test', 'password' => 'nope'])
            ->assertStatus(422);
        $this->postJson('/api/auth/login', ['email' => 'off@example.test', 'password' => 'password'])
            ->assertStatus(422);
    }

    public function test_protected_routes_require_token(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }
}
