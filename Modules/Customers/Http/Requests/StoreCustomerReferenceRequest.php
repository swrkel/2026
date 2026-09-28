<?php

namespace Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Task 8046 - validates the Add Customer Reference popup.
 *
 * The popup lets the user stack up several rows before pressing Save, so the
 * payload is an array of rows under `references` rather than a single set of
 * fields.
 */
class StoreCustomerReferenceRequest extends FormRequest
{
    /**
     * Authorisation is handled by the route middleware
     * (customers.access:create) and re-checked in the controller through
     * CustomerPermissionService, so it is not duplicated here.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = (int) ($this->session()->get('business.id') ?: $this->session()->get('user.business_id'));

        return [
            'references' => 'required|array|min:1',

            'references.*.customer_id' => [
                'required',
                'integer',
                // Scoped to the business and to customer-type contacts so a
                // crafted request cannot attach a reference to another tenant's
                // contact, or to a supplier.
                Rule::exists('contacts', 'id')
                    ->where('business_id', $businessId)
                    ->whereIn('type', ['customer', 'both'])
                    ->whereNull('deleted_at'),
            ],

            'references.*.reference_datetime' => 'nullable|string|max:40',

            'references.*.is_vehicle' => 'required|in:0,1',

            'references.*.reference_no' => 'required|string|max:191',

            // Either a product sub-category id or the "not_known" sentinel.
            // Membership of the Fuel category is checked in the service, which
            // is the only place that knows how that category is resolved.
            'references.*.fuel_type' => 'nullable|string|max:50',
        ];
    }

    public function attributes(): array
    {
        return [
            'references.*.customer_id' => 'customer',
            'references.*.reference_no' => 'customer reference',
            'references.*.is_vehicle' => 'reference is a vehicle',
            'references.*.fuel_type' => 'fuel type',
        ];
    }

    public function messages(): array
    {
        return [
            'references.required' => 'Add at least one customer reference before saving.',
            'references.*.customer_id.required' => 'Select a customer for every reference.',
            'references.*.customer_id.exists' => 'One of the selected customers is not valid for this business.',
            'references.*.reference_no.required' => 'Enter the customer reference.',
        ];
    }

    /**
     * The validated rows, normalised for the service.
     *
     * @return array<int, array>
     */
    public function referenceRows(): array
    {
        $rows = [];

        foreach ((array) $this->input('references', []) as $row) {
            $rows[] = [
                'customer_id' => (int) ($row['customer_id'] ?? 0),
                'reference_datetime' => $row['reference_datetime'] ?? null,
                'is_vehicle' => (int) ($row['is_vehicle'] ?? 0) === 1,
                'reference_no' => (string) ($row['reference_no'] ?? ''),
                'fuel_type' => $row['fuel_type'] ?? null,
            ];
        }

        return $rows;
    }
}
