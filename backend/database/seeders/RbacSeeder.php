<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /** permission => description */
    public const PERMISSIONS = [
        'tasks.view_all' => 'See tasks across every team',
        'tasks.create' => 'Create tasks manually or from interactions',
        'tasks.update' => 'Update status / assignee of visible tasks',
        'tasks.route' => 'Trigger LLM routing or manually override routing',
        'contacts.manage' => 'Create and edit contacts and interactions',
        'routing_rules.manage' => 'Edit category to team routing rules',
        'teams.manage' => 'Create and edit teams',
        'users.manage' => 'Create users and assign roles',
        'reports.view' => 'View dashboard statistics',
    ];

    public const ROLE_PERMISSIONS = [
        Role::ADMIN => ['*'],
        Role::MANAGER => [
            'tasks.view_all', 'tasks.create', 'tasks.update', 'tasks.route',
            'contacts.manage', 'routing_rules.manage', 'reports.view',
        ],
        Role::AGENT => ['tasks.create', 'tasks.update', 'contacts.manage'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $description) {
            Permission::updateOrCreate(['name' => $name], ['description' => $description]);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $perms) {
            $role = Role::updateOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);
            $ids = $perms === ['*']
                ? Permission::pluck('id')
                : Permission::whereIn('name', $perms)->pluck('id');
            $role->permissions()->sync($ids);
        }
    }
}
