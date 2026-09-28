<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

/**
 * Compatibility controller for host/core sidebars that generate the
 * report configuration URL with
 * action(RestaurantNew\\ReportConfigurationsController@index).
 *
 * RestaurantNew does not replace the application's existing report
 * configuration screen. Prefer an existing host route/action when present;
 * otherwise fall back to RestaurantNew settings instead of breaking every
 * page that renders the global sidebar.
 */
class ReportConfigurationsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    public function index(): RedirectResponse
    {
        foreach ([
            'report-configurations.index',
            'report_configurations.index',
            'reports.configurations.index',
            'report.configuration.index',
            'report-config.index',
        ] as $routeName) {
            if (Route::has($routeName)) {
                return redirect()->route($routeName);
            }
        }

        foreach ([
            'App\\Http\\Controllers\\ReportConfigurationsController@index',
            'App\\Http\\Controllers\\ReportConfigurationController@index',
        ] as $action) {
            if (Route::getRoutes()->getByAction($action)) {
                return redirect()->action($action);
            }
        }

        if (Route::has('restaurant-new.settings.index')) {
            return redirect()->route('restaurant-new.settings.index');
        }

        if (Route::has('restaurantnew.index')) {
            return redirect()->route('restaurantnew.index');
        }

        return redirect()->to(url('/restaurant-new'));
    }
}
