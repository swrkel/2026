<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;

class DisnewCustomerLookupService
{
    public function listForBusiness(int $businessId)
    {
        return DB::table('customers')->where('business_id', $businessId)->orderBy('name')->select('id', 'name', 'mobile', 'contact_id')->get();
    }

    public function find(int $businessId, int $customerId): ?object
    {
        return DB::table('customers')->where('business_id', $businessId)->where('id', $customerId)->first();
    }
}
