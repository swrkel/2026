<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_type' => ['required', Rule::in(['replace', 'increase', 'decrease'])],
            'field_name' => [
                'required',
                Rule::in([
                    'meter_sales_total',
                    'other_sales_total',
                    'source_payments_total',
                    'manual_payments_total',
                    'expected_total',
                    'received_total',
                ]),
            ],
            'requested_amount' => 'required|numeric',
            'reason' => 'required|string|min:3|max:5000',
            'metadata' => 'nullable|array',
        ];
    }
}
