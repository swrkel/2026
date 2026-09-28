<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OtherSaleStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'cheque', 'credit', 'other'])],
            'collection_form_no' => ['nullable', 'string', 'max:100'],
            'sale_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'edit_reason' => [$this->isMethod('PUT') || $this->isMethod('PATCH') ? 'required' : 'nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
