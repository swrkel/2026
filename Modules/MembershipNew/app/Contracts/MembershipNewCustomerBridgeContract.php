<?php

namespace Modules\MembershipNew\app\Contracts;

interface MembershipNewCustomerBridgeContract
{
    public function findOrCreateCentralCustomer(array $customerData): array;

    public function linkCentralCustomerToBusiness(array $linkData): array;

    public function recordBusinessLedger(array $ledgerData): array;
}
