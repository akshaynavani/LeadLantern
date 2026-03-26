<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\TaskCategory;
use App\Models\RoutingRule;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            'sales' => ['Sales', 'New business and inbound leads'],
            'support' => ['Customer Support', 'Technical issues and product questions'],
            'billing' => ['Billing & Finance', 'Invoices, refunds and payment problems'],
            'success' => ['Customer Success', 'Renewals, expansion and churn prevention'],
            'partnerships' => ['Partnerships', 'Resellers, integrations and alliances'],
        ];

        foreach ($teams as $slug => [$name, $description]) {
            Team::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);
        }
        Team::triage();

        $rules = [
            TaskCategory::SalesLead->value => ['sales', Priority::High],
            TaskCategory::Billing->value => ['billing', Priority::Medium],
            TaskCategory::TechnicalSupport->value => ['support', Priority::Medium],
            TaskCategory::AccountManagement->value => ['success', Priority::Medium],
            TaskCategory::ChurnRisk->value => ['success', Priority::Urgent],
            TaskCategory::Partnership->value => ['partnerships', Priority::Low],
            TaskCategory::Other->value => ['triage', null],
            TaskCategory::Spam->value => ['triage', Priority::Low],
        ];

        foreach ($rules as $category => [$slug, $priority]) {
            RoutingRule::updateOrCreate(
                ['category' => $category],
                [
                    'team_id' => Team::where('slug', $slug)->value('id'),
                    'default_priority' => $priority,
                    'is_active' => true,
                ],
            );
        }
    }
}
