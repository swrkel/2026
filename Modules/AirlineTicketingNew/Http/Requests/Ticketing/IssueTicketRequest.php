<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Ticketing;

use Illuminate\Foundation\Http\FormRequest;

class IssueTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.tickets.issue');
    }

    public function rules(): array
    {
        return [
            'ticket_no' => ['nullable','string','max:30'],
            'issue_date' => ['required','date'],
            'reservation_passenger_id' => ['nullable','integer'],
            'passenger_id' => ['nullable','integer'],
            'airline_id' => ['nullable','integer'],
            'supplier_id' => ['nullable','integer'],
            'ticket_type' => ['required','in:normal,reissue,exchange'],
            'remarks' => ['nullable','string'],
        ];
    }
}
