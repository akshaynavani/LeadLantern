<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\RoutingRuleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['status' => 'ok']);

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Tasks — row-level visibility enforced by TaskPolicy / visibleTo scope
    Route::apiResource('tasks', TaskController::class);
    Route::post('/tasks/{task}/route', [TaskController::class, 'route']);
    Route::post('/tasks/{task}/override', [TaskController::class, 'override']);
    Route::get('/tasks/{task}/decisions', [TaskController::class, 'decisions']);

    // Contacts & interaction history
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{contact}', [ContactController::class, 'show']);
    Route::get('/contacts/{contact}/interactions', [InteractionController::class, 'index']);
    Route::middleware('permission:contacts.manage')->group(function () {
        Route::post('/contacts', [ContactController::class, 'store']);
        Route::match(['put', 'patch'], '/contacts/{contact}', [ContactController::class, 'update']);
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);
        Route::post('/contacts/{contact}/interactions', [InteractionController::class, 'store']);
    });

    Route::get('/teams', [TeamController::class, 'index']);
    Route::middleware('permission:teams.manage')->group(function () {
        Route::post('/teams', [TeamController::class, 'store']);
        Route::match(['put', 'patch'], '/teams/{team}', [TeamController::class, 'update']);
    });

    Route::get('/routing-rules', [RoutingRuleController::class, 'index']);
    Route::middleware('permission:routing_rules.manage')->group(function () {
        Route::post('/routing-rules', [RoutingRuleController::class, 'store']);
        Route::match(['put', 'patch'], '/routing-rules/{routing_rule}', [RoutingRuleController::class, 'update']);
        Route::delete('/routing-rules/{routing_rule}', [RoutingRuleController::class, 'destroy']);
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
    });

    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->middleware('permission:reports.view');
});
