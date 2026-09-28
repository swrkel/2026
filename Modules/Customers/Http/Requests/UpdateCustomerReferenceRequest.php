<?php

namespace Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Task 8046 - validates an edit of one existing customer reference.
 *
 * Separate from the store request because editing works on a single flat row,
 * not the `references` array the multi-row add popup posts.
 */
class UpdateCustomerReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = (int) ($this->session()->get('business.id') ?: $this->session()->get('user.business_id'));

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')
                    ->where('business_id', $businessId)
                    ->whereIn('type', ['customer', 'both'])
                    ->whereNull('deleted_at'),
            ],
            'reference_datetime' => 'nullable|string|max:40',
            'is_vehicle' => 'required|in:0,1',
            'reference_no' => 'required|string|max:191',
            'fuel_type' => 'nullable|string|max:50',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'reference_no' => 'customer reference',
            'is_vehicle' => 'reference is a vehicle',
            'fuel_type' => 'fuel type',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function referenceRow(): array
    {
        return [
            'customer_id' => (int) $this->input('customer_id'),
            'reference_datetime' => $this->input('reference_datetime'),
            'is_vehicle' => (int) $this->input('is_vehicle') === 1,
            'reference_no' => (string) $this->input('reference_no'),
            'fuel_type' => $this->input('fuel_type'),
        ];
    }
}
