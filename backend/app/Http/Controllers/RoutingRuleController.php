<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoutingRuleRequest;
use App\Http\Resources\RoutingRuleResource;
use App\Models\RoutingRule;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RoutingRuleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RoutingRuleResource::collection(RoutingRule::with('team')->orderBy('category')->get());
    }

    public function store(RoutingRuleRequest $request): RoutingRuleResource
    {
        return new RoutingRuleResource(RoutingRule::create($request->validated())->load('team'));
    }

    public function update(RoutingRuleRequest $request, RoutingRule $routingRule): RoutingRuleResource
    {
        $routingRule->update($request->validated());

        return new RoutingRuleResource($routingRule->load('team'));
    }

    public function destroy(RoutingRule $routingRule): Response
    {
        $routingRule->delete();

        return response()->noContent();
    }
}
