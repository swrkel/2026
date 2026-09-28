<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;

class ProfileController extends BaseDealerApiController
{
    public function show(Request $request)
    {
        $customer = $this->customer($request);

        return $this->success([
            'id' => (int) $customer->id,
            'contact_id' => $customer->contact_id,
            'name' => $customer->name,
            'supplier_business_name' => $customer->supplier_business_name,
            'mobile' => $customer->mobile,
            'alternate_number' => $customer->alternate_number,
            'landline' => $customer->landline,
            'email' => $customer->email,
            'address' => trim(($customer->address ?? '') . ' ' . ($customer->address_2 ?? '') . ' ' . ($customer->address_3 ?? '')),
            'city' => $customer->city,
            'state' => $customer->state,
            'country' => $customer->country,
            'credit_limit' => (float) ($customer->credit_limit ?? 0),
            'summary' => $this->portalSummary($this->businessId($request), $this->customerId($request)),
        ]);
    }
}
