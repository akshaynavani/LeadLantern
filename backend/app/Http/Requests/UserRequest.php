<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $userId = $this->route('user')?->id;

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => [$creating ? 'required' : 'sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'password' => [$creating ? 'required' : 'sometimes', 'string', 'min:8'],
            'role' => [$creating ? 'required' : 'sometimes', 'exists:roles,name'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
