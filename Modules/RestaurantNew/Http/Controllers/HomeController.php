<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Modules\RestaurantNew\Services\DashboardService;

/**
 * Backward-compatible RestaurantNew entry controller.
 *
 * The host application's sidebar resolves this module with Laravel's
 * action() helper using HomeController@index. Keep this controller/action
 * available even though the actual dashboard implementation lives in
 * DashboardController.
 */
class HomeController extends DashboardController
{
    public function index(DashboardService $service)
    {
        return parent::index($service);
    }
}
