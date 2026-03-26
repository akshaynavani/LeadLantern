<?php

namespace App\Providers;

use App\Services\TaskRouter;
use App\Services\Triage\TriageClient;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TriageClient::class, fn () => TriageClient::fromConfig());

        $this->app->singleton(TaskRouter::class, fn ($app) => new TaskRouter(
            $app->make(TriageClient::class),
            (float) config('services.triage.min_confidence', 0.6),
        ));
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
