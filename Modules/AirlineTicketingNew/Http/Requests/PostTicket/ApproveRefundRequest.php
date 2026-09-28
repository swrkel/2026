<?php

namespace Modules\AirlineTicketingNew\Http\Requests\PostTicket;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('airline_ticketing_new.refunds.approve');
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable','integer'],
            'payment_id' => ['nullable','integer'],
            'service_fee' => ['nullable','numeric','min:0'],
            'other_deductions' => ['nullable','numeric','min:0'],
            'refund_method' => ['required','in:cash,card,bank_transfer,cheque,credit_note,other'],
            'reference_no' => ['nullable','string','max:100'],
            'customer_type' => ['nullable','in:individual,corporate,walk_in'],
            'corporate_customer_id' => ['nullable','integer'],
            'remarks' => ['nullable','string'],
        ];
    }
}
