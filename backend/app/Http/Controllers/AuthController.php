<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::with('role', 'team')->where('email', $data['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        $token = $user->createToken($data['device_name'] ?? 'dashboard', ['*'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
            'permissions' => $user->isAdmin() ? ['*'] : $user->permissionNames(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role', 'team');

        return response()->json([
            'user' => new UserResource($user),
            'permissions' => $user->isAdmin() ? ['*'] : $user->permissionNames(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
