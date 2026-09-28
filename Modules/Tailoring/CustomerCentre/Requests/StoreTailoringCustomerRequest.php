<?php
namespace Modules\Tailoring\CustomerCentre\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreTailoringCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['name'=>'required|string|max:191','mobile'=>'nullable|string|max:50','email'=>'nullable|email|max:191','location_id'=>'nullable|integer'];
    }
}
