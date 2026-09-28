<?php

namespace Modules\RestaurantNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('restaurant_new.shifts.open') ?? false;
    }

    public function rules(): array
    {
        return [
            'location_id' => 'required|integer',
            'opening_cash' => 'required|numeric|min:0',
            'opening_note' => 'nullable|string|max:1000',
        ];
    }
}
