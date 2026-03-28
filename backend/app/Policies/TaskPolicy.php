<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.view_all')
            || $task->assignee_id === $user->id
            || ($user->team_id !== null && $task->team_id === $user->team_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.update') && $this->view($user, $task);
    }

    public function route(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.route');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }
}
