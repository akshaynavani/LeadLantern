<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::with('role', 'team')->orderBy('name')->get());
    }

    public function store(UserRequest $request): UserResource
    {
        $data = $request->validated();
        $data['role_id'] = Role::where('name', $data['role'])->value('id');
        unset($data['role']);

        return new UserResource(User::create($data)->load('role', 'team'));
    }

    public function update(UserRequest $request, User $user): UserResource
    {
        $data = $request->validated();
        if (isset($data['role'])) {
            $data['role_id'] = Role::where('name', $data['role'])->value('id');
            unset($data['role']);
        }
        $user->update($data);

        if ($user->wasChanged('is_active') && ! $user->is_active) {
            $user->tokens()->delete();
        }

        return new UserResource($user->load('role', 'team'));
    }
}
