<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_type' => [
                'required',
                'string',
                'max:40',
                Rule::in((array) config('petropdnew.payment_types', [])),
            ],
            'gross_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0.0001',
            'customer_id' => 'nullable|integer|min:1',
            'reference_no' => 'nullable|string|max:191',
            'transaction_at' => 'nullable|date',
            'note' => 'nullable|string|max:5000',
            'details' => 'nullable|array|max:100',
            'details.*.detail_type' => 'nullable|string|max:40',
            'details.*.reference_no' => 'nullable|string|max:191',
            'details.*.amount' => 'nullable|numeric|min:0',
            'metadata' => 'nullable|array',
        ];
    }
}
