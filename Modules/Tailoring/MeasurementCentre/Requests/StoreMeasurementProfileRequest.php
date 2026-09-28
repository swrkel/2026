<?php
namespace Modules\Tailoring\MeasurementCentre\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['customer_id'=>'required|integer','profile_name'=>'required|string|max:191','garment_type'=>'nullable|string|max:100','measurements'=>'nullable|array'];
    }
}
