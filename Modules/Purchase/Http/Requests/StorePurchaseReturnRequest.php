<?php

namespace Modules\Purchase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\Modules\Purchase\Utils\PurchaseAccessUtil::class)->canCreateReturns();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'purchase_id' => ['required', 'integer', 'min:1'],
            'contact_id' => ['required', 'integer', 'min:1'],
            'location_id' => ['required', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date'],
            'ref_no' => ['nullable', 'string', 'max:191'],
            'return_account_id' => ['nullable', 'integer', 'min:1'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_line_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'purchase_id.required' => 'Please select the original purchase.',
            'lines.required' => 'The selected purchase does not contain returnable product lines.',
        ];
    }
}
