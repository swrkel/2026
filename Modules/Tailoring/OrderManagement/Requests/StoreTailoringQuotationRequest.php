<?php
namespace Modules\Tailoring\OrderManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTailoringQuotationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|integer',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'items' => 'nullable|array',
        ];
    }
}
