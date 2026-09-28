<?php

namespace Modules\AirlineTicketingNew\Http\Requests\Transactions;

use Illuminate\Foundation\Http\FormRequest;

class ConvertQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.reservations.create');
    }

    public function rules(): array
    {
        return [
            'pnr_code' => ['nullable','string','max:20'],
            'reservation_date' => ['required','date'],
            'ticketing_deadline' => ['nullable','date'],
            'supplier_id' => ['nullable','integer'],
            'remarks' => ['nullable','string'],
        ];
    }
}
