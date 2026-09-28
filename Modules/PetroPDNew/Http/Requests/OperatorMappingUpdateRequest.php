<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperatorMappingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:active,inactive',
            'settings' => 'nullable|array|max:50',
        ];
    }
}
