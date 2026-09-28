<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Passengers;

use Illuminate\Foundation\Http\FormRequest;

class SavePassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.passengers.manage');
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable','string','max:20'],
            'first_name' => ['required','string','max:100'],
            'middle_name' => ['nullable','string','max:100'],
            'last_name' => ['required','string','max:100'],
            'gender' => ['nullable','in:male,female,other,unspecified'],
            'date_of_birth' => ['nullable','date','before:today'],
            'nationality_code' => ['nullable','string','size:2'],
            'email' => ['nullable','email','max:150'],
            'phone' => ['nullable','string','max:40'],
            'alternate_phone' => ['nullable','string','max:40'],
            'address' => ['nullable','string'],
            'city' => ['nullable','string','max:100'],
            'country_code' => ['nullable','string','size:2'],
            'corporate_customer_id' => ['nullable','integer'],
            'is_vip' => ['nullable','boolean'],
            'is_active' => ['nullable','boolean'],
            'notes' => ['nullable','string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_vip' => $this->boolean('is_vip'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
