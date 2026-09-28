<?php

namespace Modules\Chequer\Utils;

class FinanceTenantScope
{
    public static function businessId(): ?int
    {
        return session('user.business_id') ?: session('business.id');
    }

    public static function locationId(): ?int
    {
        return request()->get('location_id') ?: session('business_location_id');
    }
}
