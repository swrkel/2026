<?php

namespace Modules\AirlineTicketingNew\Http\Requests\PostTicket;

use Illuminate\Foundation\Http\FormRequest;

class RequestCancellationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.cancellations.create');
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required','date'],
            'reason' => ['required','string','max:1000'],
            'cancellation_fee' => ['nullable','numeric','min:0'],
            'remarks' => ['nullable','string'],
        ];
    }
}
