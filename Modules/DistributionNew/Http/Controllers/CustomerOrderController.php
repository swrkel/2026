<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\CustomerPortal\DisnewCustomerOrderAccessService;
use Modules\DistributionNew\Services\DisnewSalesOrderService;

class CustomerOrderController extends Controller
{
    public function create(Request $request, $token, DisnewCustomerOrderAccessService $access)
    {
        $accessToken = $access->validateToken($token);
        abort_if(!$accessToken, 403, 'Invalid or expired order access link.');
        return view('distributionnew::customer_orders.create', compact('accessToken', 'token'));
    }

    public function store(Request $request, $token, DisnewCustomerOrderAccessService $access, DisnewSalesOrderService $orders)
    {
        $accessToken = $access->validateToken($token);
        abort_if(!$accessToken, 403, 'Invalid or expired order access link.');

        $payload = $request->all();
        $payload['business_id'] = $accessToken->business_id;
        $payload['customer_id'] = $accessToken->customer_id;
        $payload['created_by_type'] = 'customer';
        $orders->create($payload);

        $accessToken->update(['is_used' => 1, 'used_at' => now()]);
        return view('distributionnew::customer_orders.thank_you');
    }
}
