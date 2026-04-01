<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TeamResource::collection(
            Team::withCount(['tasks' => fn ($q) => $q->whereNotIn('status', ['resolved'])])
                ->orderBy('name')->get()
        );
    }

    public function store(Request $request): TeamResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'alpha_dash', 'max:60', 'unique:teams,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['slug'] ??= Str::slug($data['name']);

        return new TeamResource(Team::create($data));
    }

    public function update(Request $request, Team $team): TeamResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'alpha_dash', 'max:60', Rule::unique('teams', 'slug')->ignore($team->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $team->update($data);

        return new TeamResource($team);
    }
}
