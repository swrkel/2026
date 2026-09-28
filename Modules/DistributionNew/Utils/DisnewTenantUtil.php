<?php

namespace Modules\DistributionNew\Utils;

use Illuminate\Support\Facades\Auth;

class DisnewTenantUtil
{
    public static function businessId(): ?int
    {
        return session('business.id') ?? session('business_id') ?? optional(Auth::user())->business_id;
    }

    public static function locationId(): ?int
    {
        return request('location_id') ?? session('business_location_id') ?? session('location_id');
    }

    public static function userId(): ?int
    {
        return optional(Auth::user())->id;
    }
}
