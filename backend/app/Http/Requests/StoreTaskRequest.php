<?php

namespace App\Http\Requests;

use App\Models\Interaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'channel' => ['sometimes', Rule::in(Interaction::CHANNELS)],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'interaction_id' => ['nullable', 'exists:interactions,id'],
            'due_at' => ['nullable', 'date'],
            'auto_route' => ['sometimes', 'boolean'],
        ];
    }
}
