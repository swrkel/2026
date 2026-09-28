<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Passengers;

use Illuminate\Foundation\Http\FormRequest;

class SaveCorporateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.corporate_customers.manage');
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required','string','max:180'],
            'registration_no' => ['nullable','string','max:80'],
            'tax_no' => ['nullable','string','max:80'],
            'contact_person' => ['nullable','string','max:150'],
            'email' => ['nullable','email','max:150'],
            'phone' => ['nullable','string','max:40'],
            'alternate_phone' => ['nullable','string','max:40'],
            'billing_address' => ['nullable','string'],
            'city' => ['nullable','string','max:100'],
            'country_code' => ['nullable','string','size:2'],
            'credit_limit' => ['nullable','numeric','min:0'],
            'payment_terms_days' => ['nullable','integer','min:0'],
            'currency_code' => ['nullable','string','size:3'],
            'is_active' => ['nullable','boolean'],
            'notes' => ['nullable','string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
