<?php

namespace Modules\MembershipNew\app\Integration\Examples;

use Modules\MembershipNew\app\Integration\MembershipNewCentralCustomerBridge;

class CustomerModuleMembershipNewBridgeExample
{
    public function afterCustomerSaved(object $customer): array
    {
        // Example only. Adapt field names to the real Contacts/Customers module.

        return app(MembershipNewCentralCustomerBridge::class)->registerOrLinkCustomer([
            'business_id' => $customer->business_id,
            'local_customer_id' => $customer->id,
            'first_name' => $customer->first_name ?? $customer->name ?? null,
            'last_name' => $customer->last_name ?? null,
            'mobile' => $customer->mobile ?? null,
            'email' => $customer->email ?? null,
            'nic' => $customer->nic_number ?? $customer->nic ?? null,
            'address' => $customer->address ?? null,
        ]);
    }
}
