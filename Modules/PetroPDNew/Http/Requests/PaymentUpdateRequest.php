<?php
namespace Modules\PetroPDNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PaymentUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'gross_amount'=>'nullable|numeric|min:0','discount_amount'=>'nullable|numeric|min:0',
            'amount'=>'required|numeric|min:0.0001','customer_id'=>'nullable|integer|min:1',
            'reference_no'=>'nullable|string|max:191','transaction_at'=>'nullable|date',
            'note'=>'nullable|string|max:5000','metadata'=>'nullable|array',
        ];
    }
}
