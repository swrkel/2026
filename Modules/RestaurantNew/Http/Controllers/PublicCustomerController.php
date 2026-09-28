<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewQrMenu;
use Modules\RestaurantNew\Services\RestaurantCustomerExperienceService;

class PublicCustomerController extends Controller
{
    public function menu(string $token)
    {
        $menu = RestaurantNewQrMenu::where('public_token', $token)->where('is_active', 1)->firstOrFail();
        return view('restaurantnew::customer.public-menu', compact('menu'));
    }

    public function orderStatus(string $token, RestaurantCustomerExperienceService $service)
    {
        $status = $service->customerOrderStatus($token);
        abort_if(!$status, 404);

        return view('restaurantnew::customer.order-status', $status);
    }
}
