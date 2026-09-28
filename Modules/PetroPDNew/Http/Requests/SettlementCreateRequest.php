<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettlementCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('shift_id') && (int) $this->route('shift') > 0) {
            $this->merge(['shift_id' => (int) $this->route('shift')]);
        }
    }

    public function rules(): array
    {
        return [
            'shift_id' => 'required|integer|min:1',
            'settlement_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
        ];
    }
}
