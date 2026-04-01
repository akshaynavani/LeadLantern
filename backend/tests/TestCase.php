<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, TeamSeeder::class]);
    }

    protected function userWithRole(string $role, array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            ...$attributes,
        ]);
    }
}
