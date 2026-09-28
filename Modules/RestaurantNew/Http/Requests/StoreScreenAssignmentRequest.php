<?php

namespace Modules\RestaurantNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScreenAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('restaurant_new.screen_assignments.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'location_id' => 'nullable|integer|exists:business_locations,id',
            'screen_role' => 'required|in:waiter,cashier,kitchen,takeaway,collection',
            'station_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ];
    }
}
