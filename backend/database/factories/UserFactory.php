<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role_id' => fn () => Role::firstOrCreate(['name' => Role::AGENT], ['label' => 'Agent'])->id,
            'is_active' => true,
        ];
    }

    public function role(string $name): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(['name' => $name], ['label' => ucfirst($name)])->id,
        ]);
    }
}
