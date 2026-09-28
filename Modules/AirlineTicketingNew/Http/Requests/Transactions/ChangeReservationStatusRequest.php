<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Transactions;

use Illuminate\Foundation\Http\FormRequest;

class ChangeReservationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.reservations.status');
    }

    public function rules(): array
    {
        return [
            'status' => ['required','in:reserved,confirmed,on_hold,cancelled,expired,ticketed'],
            'reason' => ['nullable','string','max:1000'],
        ];
    }
}
