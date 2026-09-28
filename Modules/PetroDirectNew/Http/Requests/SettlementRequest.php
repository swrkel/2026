<?php

namespace Modules\PetroDirectNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettlementRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array
    {
        return [
            'location_id'=>'required|integer','operator_id'=>'required|integer','transaction_date'=>'required|date',
            'shift_id'=>'nullable|integer','work_shift'=>'nullable|string|max:80','note'=>'nullable|string|max:1000',
            'meter_sales'=>'nullable|array','payments'=>'nullable|array','other_sales'=>'nullable|array',
            'other_income'=>'nullable|array','customer_payments'=>'nullable|array',
        ];
    }
}
