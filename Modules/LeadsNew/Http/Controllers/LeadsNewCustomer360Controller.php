<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\LeadsNew\Services\LeadsNewCustomer360Service;

class LeadsNewCustomer360Controller extends Controller
{
    public function show(int $leadId, LeadsNewCustomer360Service $service)
    {
        $businessId = request()->session()->get('user.business_id');
        return view('leadsnew::customer360.show', $service->profile($businessId, $leadId));
    }
}
