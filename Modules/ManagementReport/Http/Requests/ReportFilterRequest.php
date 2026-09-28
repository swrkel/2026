<?php
namespace Modules\ManagementReport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    protected function prepareForValidation()
    {
        $normalized = [];

        foreach (['location_id', 'store_id', 'shift_id'] as $field) {
            $value = $this->input($field);
            $normalized[$field] = ($value === null || $value === '' || in_array((string) $value, ['0', '__all__'], true))
                ? null
                : $value;
        }

        $this->merge($normalized);
    }

    public function rules()
    {
        return [
            'business_id' => 'nullable|integer',
            'location_id' => 'nullable|integer|min:1',
            'store_id' => 'nullable|integer|min:1',
            'shift_id' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sections' => 'required|array|min:1',
            'sections.*' => 'string|max:80',
        ];
    }
}
