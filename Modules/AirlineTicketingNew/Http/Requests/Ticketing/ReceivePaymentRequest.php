<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Ticketing;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.payments.create');
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required','date'],
            'payment_method' => ['required','in:cash,card,bank_transfer,cheque,credit,other'],
            'payment_account' => ['nullable','string','max:150'],
            'reference_no' => ['nullable','string','max:100'],
            'amount' => ['required','numeric','gt:0'],
            'remarks' => ['nullable','string'],
        ];
    }
}
