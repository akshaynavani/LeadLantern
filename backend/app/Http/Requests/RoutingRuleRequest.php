<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\TaskCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoutingRuleRequest extends FormRequest
{
    public function rules(): array
    {
        $ruleId = $this->route('routing_rule')?->id;

        return [
            'category' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                Rule::enum(TaskCategory::class),
                Rule::unique('routing_rules', 'category')->ignore($ruleId),
            ],
            'team_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'exists:teams,id'],
            'default_priority' => ['nullable', Rule::enum(Priority::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
