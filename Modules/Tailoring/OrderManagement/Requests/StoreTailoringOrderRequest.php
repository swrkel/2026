<?php
namespace Modules\Tailoring\OrderManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTailoringOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|integer',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'items' => 'nullable|array',
        ];
    }
}
