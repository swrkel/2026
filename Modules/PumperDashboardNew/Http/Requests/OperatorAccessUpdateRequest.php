<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperatorAccessUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer', 'min:1'],
            'login_enabled' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
