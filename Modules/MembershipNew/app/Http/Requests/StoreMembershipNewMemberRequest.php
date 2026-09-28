<?php

namespace Modules\MembershipNew\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipNewMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'member_code' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'joined_on' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
