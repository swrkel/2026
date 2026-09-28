<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Transactions;

use Illuminate\Foundation\Http\FormRequest;

class SaveQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.quotations.create');
    }

    public function rules(): array
    {
        return [
            'quotation_date' => ['required','date'],
            'valid_until' => ['nullable','date','after_or_equal:quotation_date'],
            'customer_type' => ['required','in:individual,corporate,walk_in'],
            'corporate_customer_id' => ['nullable','integer'],
            'passenger_id' => ['nullable','integer'],
            'agent_id' => ['nullable','integer'],
            'currency_code' => ['required','string','size:3'],
            'exchange_rate' => ['required','numeric','gt:0'],
            'remarks' => ['nullable','string'],
            'segments' => ['required','array','min:1'],
            'segments.*.airline_id' => ['required','integer'],
            'segments.*.flight_number' => ['nullable','string','max:20'],
            'segments.*.origin_airport_id' => ['required','integer'],
            'segments.*.destination_airport_id' => ['required','integer','different:segments.*.origin_airport_id'],
            'segments.*.departure_at' => ['required','date'],
            'segments.*.arrival_at' => ['required','date','after:segments.*.departure_at'],
            'segments.*.travel_class_id' => ['nullable','integer'],
            'segments.*.booking_class' => ['nullable','string','max:10'],
            'segments.*.fare_basis' => ['nullable','string','max:30'],
            'segments.*.baggage_allowance' => ['nullable','string','max:50'],
            'segments.*.base_fare' => ['required','numeric','min:0'],
            'segments.*.tax_amount' => ['nullable','numeric','min:0'],
            'segments.*.service_fee' => ['nullable','numeric','min:0'],
            'segments.*.discount_amount' => ['nullable','numeric','min:0'],
        ];
    }
}
