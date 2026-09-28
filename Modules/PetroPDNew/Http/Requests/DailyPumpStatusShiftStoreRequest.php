<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DailyPumpStatusShiftStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operator_profile_id' => ['required', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'shift_number' => ['nullable', 'string', 'max:80'],
            'opened_at' => ['required', 'date'],
            'pump_ids' => ['required', 'array', 'min:1', 'max:100'],
            'pump_ids.*' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
