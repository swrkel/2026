<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'payment_type' => ['required', Rule::in(['cash', 'card', 'cheque', 'credit', 'shortage', 'excess', 'other'])],
            'collection_form_no' => ['nullable', 'string', 'max:100'],
            'parent_payment_id' => ['nullable', 'integer', 'min:1'],
            'account_id' => ['nullable', 'integer', 'min:1'],
            'gross_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['required_if:payment_type,credit', 'nullable', 'integer', 'min:1'],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'card_type' => ['nullable', 'string', 'max:80'],
            'card_last_four' => ['nullable', 'digits:4'],
            'slip_no' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'cheque_no' => ['required_if:payment_type,cheque', 'nullable', 'string', 'max:100'],
            'cheque_date' => ['required_if:payment_type,cheque', 'nullable', 'date'],
            'transaction_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'edit_reason' => [$this->isMethod('PUT') || $this->isMethod('PATCH') ? 'required' : 'nullable', 'string', 'max:1000'],
            'cash_denominations' => ['nullable', 'array', 'max:30'],
            'cash_denominations.*.denomination' => ['nullable', 'numeric', 'gt:0'],
            'cash_denominations.*.quantity' => ['nullable', 'integer', 'min:0'],
            'card_lines' => ['nullable', 'array', 'max:20'],
            'card_lines.*.card_type' => ['nullable', 'string', 'max:80'],
            'card_lines.*.last_four' => ['nullable', 'digits:4'],
            'card_lines.*.slip_no' => ['nullable', 'string', 'max:100'],
            'card_lines.*.account_id' => ['nullable', 'integer', 'min:1'],
            'card_lines.*.reference_no' => ['nullable', 'string', 'max:191'],
            'card_lines.*.amount' => ['nullable', 'numeric', 'min:0'],
            'order_number' => ['required_if:payment_type,credit', 'nullable', 'string', 'max:191'],
            'bill_number' => ['nullable', 'string', 'max:191'],
            'vehicle_number' => ['required_if:payment_type,credit', 'nullable', 'string', 'max:100'],
            'customer_reference' => ['nullable', 'string', 'max:191'],
            'order_date' => ['required_if:payment_type,credit', 'nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'customer_confirmed' => ['nullable', 'boolean'],
            'order_confirmed' => ['nullable', 'boolean'],
            'vehicle_confirmed' => ['nullable', 'boolean'],
            'confirmation_rounds' => ['nullable', 'integer', 'min:0', 'max:2'],
            'lines' => ['required_if:payment_type,credit', 'array', 'max:100'],
            'lines.*.product_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
