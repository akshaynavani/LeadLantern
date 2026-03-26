<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class InteractionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'channel' => 'email',
            'direction' => 'inbound',
            'subject' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'occurred_at' => now(),
        ];
    }
}
