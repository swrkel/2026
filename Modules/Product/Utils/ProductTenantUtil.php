<?php

namespace Modules\Product\Utils;

class ProductTenantUtil
{
    public function businessId(): int
    {
        return (int) session('user.business_id');
    }

    public function locationId(): ?int
    {
        return session('business.default_location_id') ?: session('user.default_location_id');
    }
}
