<?php

namespace Modules\ExpensesNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ExpensesNew\Services\AccountingModuleService;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'location_id' => ['required', 'integer'],
            'sw_shift_no' => ['nullable', 'string', 'max:60'],
            'category_id' => ['required', 'integer'],
            /*
             * MA-002 (S-621 #2): the three category-driven fields.
             *
             * All nullable - they only appear when the chosen category has the
             * matching checkbox ticked, so most expenses submit none of them.
             */
            'vat_category_id' => ['nullable', 'integer'],
            'sub_category_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'payee_id' => ['nullable', 'integer'],
            'expense_account_id' => ['nullable', 'integer'],
            // The controller derives this value from payment_method. It remains
            // accepted here for edit-form compatibility but is never trusted.
            'accounting_module' => [
                'nullable',
                'string',
                Rule::in(AccountingModuleService::allowedValues()),
            ],
            // Total Amount is no longer a user-entered field. The controller
            // derives it from Paid Amount so existing ledger/report columns remain intact.
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => [
                'required',
                'string',
                'max:100',
            ],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'cheque_no' => ['nullable', 'string', 'max:191'],
            // Existing column reused as the selected Finance account for every
            // payment method (Cash/Card/Cheque/Bank Transfer).
            'bank_account_id' => ['required', 'integer', 'min:1'],
            'card_no' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string'],
            // MA-002 (Issue 3): Applicable Tax and VAT Invoice.
            // Both are optional so existing integrations that post to this
            // endpoint without them keep working; the controller applies the
            // documented defaults (None / No).
            'applicable_tax' => ['nullable', 'string', Rule::in(['none', 'vat'])],
            'vat_invoice' => ['nullable', 'boolean'],
            'attachments.*' => ['nullable', 'file', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_id.required' => 'Please select an Accounting Module account.',
            'bank_account_id.integer' => 'The selected Accounting Module account is invalid.',
            'bank_account_id.min' => 'Please select an Accounting Module account.',
        ];
    }

}
