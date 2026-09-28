<?php
namespace Modules\AutoService\Services;

class AutoServiceContext
{
    public function businessId()
    {
        try {
            if (session()->has('user.business_id')) return session('user.business_id');
            if (auth()->check() && isset(auth()->user()->business_id)) return auth()->user()->business_id;
        } catch (\Throwable $e) {}
        return null;
    }

    public function locationId()
    {
        try {
            if (session()->has('user.business_location_id')) return session('user.business_location_id');
            if (session()->has('business_location_id')) return session('business_location_id');
        } catch (\Throwable $e) {}
        return null;
    }
}
