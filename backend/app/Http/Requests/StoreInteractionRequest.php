<?php

namespace App\Http\Requests;

use App\Models\Interaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInteractionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(Interaction::CHANNELS)],
            'direction' => ['sometimes', Rule::in(['inbound', 'outbound'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'occurred_at' => ['nullable', 'date'],
            // Inbound interactions can spawn a task that is immediately triaged.
            'create_task' => ['sometimes', 'boolean'],
        ];
    }
}
