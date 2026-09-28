<?php

namespace Modules\Suppliers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Modules\Suppliers\Utils\SupplierContextUtil::check();
    }

    public function rules(): array
    {
        $businessId = (int) \Modules\Suppliers\Utils\SupplierContextUtil::businessId();

        return [
            'contact_id' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('contacts', 'contact_id')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId)->where('type', 'supplier');
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'supplier_business_name' => ['nullable', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'alternate_number' => ['nullable', 'string', 'max:30'],
            'landline' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:30'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'pay_term_number' => ['nullable', 'integer', 'min:0'],
            'pay_term_type' => ['nullable', 'in:days,months'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'transaction_date' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer'],
            'custom_field1' => ['nullable', 'string', 'max:255'],
            'custom_field2' => ['nullable', 'string', 'max:255'],
            'custom_field3' => ['nullable', 'string', 'max:255'],
            'custom_field4' => ['nullable', 'string', 'max:255'],
        ];
    }
}
