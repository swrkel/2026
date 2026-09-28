<?php

namespace Modules\AirlineTicketingNew\Http\Requests\PostTicket;

use Illuminate\Foundation\Http\FormRequest;

class RequestVoidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.voids.create');
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required','date'],
            'reason' => ['required','string','max:1000'],
            'remarks' => ['nullable','string'],
        ];
    }
}
