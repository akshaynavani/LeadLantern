<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\TaskCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OverrideRoutingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(TaskCategory::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'team_id' => ['nullable', 'exists:teams,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
