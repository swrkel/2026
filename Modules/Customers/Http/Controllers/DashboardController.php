<?php

namespace Modules\Customers\Http\Controllers;

/**
 * Backward-compatible dashboard controller.
 * Existing routes or cached links that still resolve DashboardController@index
 * continue to work while CUS_SEP_007 moves dashboard ownership to the new
 * CustomerDashboardController.
 */
class DashboardController extends CustomerDashboardController
{
}
