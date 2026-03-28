<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q')->trim()->value();

        $contacts = Contact::query()
            ->with('owner')
            ->withCount('interactions')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('company', 'like', "%{$q}%")))
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 25));

        return ContactResource::collection($contacts);
    }

    public function store(ContactRequest $request): ContactResource
    {
        $contact = Contact::create([
            'owner_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return new ContactResource($contact->load('owner'));
    }

    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact->load([
            'owner',
            'interactions.user',
            'interactions.task',
            'tasks' => fn ($q) => $q->with('team', 'latestDecision')->latest('id'),
        ]));
    }

    public function update(ContactRequest $request, Contact $contact): ContactResource
    {
        $contact->update($request->validated());

        return new ContactResource($contact->load('owner'));
    }

    public function destroy(Contact $contact): Response
    {
        $contact->delete();

        return response()->noContent();
    }
}
