<?php

namespace Modules\AirlineTicketingNew\Http\Requests\PostTicket;

use Illuminate\Foundation\Http\FormRequest;

class RequestReissueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.reissues.create');
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required','date'],
            'reason' => ['required','string','max:1000'],
            'fare_difference' => ['nullable','numeric'],
            'tax_difference' => ['nullable','numeric'],
            'service_fee' => ['nullable','numeric','min:0'],
            'penalty_amount' => ['nullable','numeric','min:0'],
            'remarks' => ['nullable','string'],
        ];
    }
}
