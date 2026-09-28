<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DayEntryStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'assignment_id' => ['nullable', 'integer', 'min:1'],
            'entry_type' => ['required', Rule::in(['note', 'incident', 'expense', 'deposit', 'meter', 'testing', 'other'])],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'settlement_no' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'starting_meter' => ['nullable', 'numeric', 'min:0'],
            'closing_meter' => ['nullable', 'numeric', 'min:0'],
            'testing_quantity' => ['nullable', 'numeric', 'min:0'],
            'entry_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:4000'],
            'edit_reason' => [$this->isMethod('PUT') || $this->isMethod('PATCH') ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }
}
