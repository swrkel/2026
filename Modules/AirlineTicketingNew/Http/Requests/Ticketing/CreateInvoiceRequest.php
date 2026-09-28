<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Ticketing;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.invoices.create');
    }

    public function rules(): array
    {
        return [
            'invoice_date' => ['required','date'],
            'customer_type' => ['required','in:individual,corporate,walk_in'],
            'corporate_customer_id' => ['nullable','integer'],
            'passenger_id' => ['nullable','integer'],
            'remarks' => ['nullable','string'],
        ];
    }
}
