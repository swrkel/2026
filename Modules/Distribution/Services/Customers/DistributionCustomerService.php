<?php

namespace Modules\Distribution\Services\Customers;

use Modules\Distribution\Entities\Core\Contact;

/**
 * Distribution-owned customer/contact service.
 *
 * This keeps Distribution controllers depending on module-local classes instead
 * of calling main ERP Contact model directly. It intentionally preserves the
 * same contacts table/query behaviour to avoid changing working functionality.
 */
class DistributionCustomerService
{
    public function customerQuery(int $businessId)
    {
        return Contact::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at');
    }

    public function listCustomers(int $businessId, array $columns = ['id', 'name', 'mobile', 'landline', 'address_line_1', 'landmark', 'address'])
    {
        return $this->customerQuery($businessId)
            ->select($columns)
            ->orderBy('name')
            ->get();
    }

    public function dropdown(int $businessId, bool $prependNone = false)
    {
        return Contact::customersDropdown($businessId, $prependNone);
    }

    public function find(int $id)
    {
        return Contact::find($id);
    }

    public function findOrFail(int $id)
    {
        return Contact::findOrFail($id);
    }

    public function findForBusiness(int $businessId, int $id)
    {
        return $this->customerQuery($businessId)->where('id', $id)->first();
    }

    public function defaultAddress($customer): string
    {
        if (!$customer) {
            return '';
        }

        return (string) ($customer->landmark ?? $customer->address_line_1 ?? $customer->address ?? '');
    }

    public function defaultContactNo($customer): string
    {
        if (!$customer) {
            return '';
        }

        return (string) ($customer->mobile ?? $customer->landline ?? '');
    }
}
