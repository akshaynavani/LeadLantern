<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RbacSeeder::class, TeamSeeder::class]);

        $roles = Role::pluck('id', 'name');
        $teams = Team::pluck('id', 'slug');

        $admin = User::updateOrCreate(['email' => 'admin@leadlantern.test'], [
            'name' => 'Ada Admin', 'password' => 'password', 'role_id' => $roles[Role::ADMIN],
        ]);
        User::updateOrCreate(['email' => 'manager@leadlantern.test'], [
            'name' => 'Maya Manager', 'password' => 'password', 'role_id' => $roles[Role::MANAGER],
        ]);
        User::updateOrCreate(['email' => 'sales.agent@leadlantern.test'], [
            'name' => 'Sam Sales', 'password' => 'password',
            'role_id' => $roles[Role::AGENT], 'team_id' => $teams['sales'],
        ]);
        User::updateOrCreate(['email' => 'support.agent@leadlantern.test'], [
            'name' => 'Sky Support', 'password' => 'password',
            'role_id' => $roles[Role::AGENT], 'team_id' => $teams['support'],
        ]);

        if (Contact::exists()) {
            return;
        }

        $samples = [
            ['Priya', 'Shah', 'Northwind Labs', 'email', 'Pricing for 200 seats?',
                'Hi, we are evaluating CRM tools for our 200-person sales org and would love a demo and enterprise pricing this week.'],
            ['Tom', 'Becker', 'Acme Retail', 'chat', 'Charged twice',
                'My card was charged twice for the March invoice. Please refund the duplicate payment.'],
            ['Lena', 'Ortiz', 'Globex', 'email', 'API returning 500s',
                'Since this morning every call to the contacts sync API returns HTTP 500. Our integration is down.'],
            ['Marcus', 'Lee', 'Initech', 'call', 'Considering cancelling',
                'Customer said the team is frustrated with missing features and they are looking at competitors before renewal next month.'],
        ];

        foreach ($samples as [$first, $last, $company, $channel, $subject, $body]) {
            $contact = Contact::create([
                'first_name' => $first, 'last_name' => $last, 'company' => $company,
                'email' => strtolower($first).'@'.strtolower(str_replace(' ', '', $company)).'.test',
                'owner_id' => $admin->id,
            ]);
            $interaction = Interaction::create([
                'contact_id' => $contact->id, 'channel' => $channel, 'direction' => 'inbound',
                'subject' => $subject, 'body' => $body, 'occurred_at' => now()->subHours(rand(1, 48)),
            ]);
            Task::create([
                'contact_id' => $contact->id, 'interaction_id' => $interaction->id,
                'title' => $subject, 'description' => $body, 'channel' => $channel,
                'status' => 'new', 'created_by' => $admin->id,
            ]);
        }
    }
}
