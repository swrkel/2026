<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    protected function businessId()
    {
        return request()->session()->get('user.business_id');
    }

    protected function userId()
    {
        return auth()->id();
    }

    protected function locationId()
    {
        return request()->get('location_id') ?: request()->session()->get('user.default_location_id');
    }
}
