<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

/**
 * Compatibility controller for host/core sidebars that generate the
 * printer setup URL with action(RestaurantNew\\PrinterController@index).
 *
 * RestaurantNew does not replace the application's existing printer
 * configuration screen. Resolve a known host printer route when available;
 * otherwise fall back to RestaurantNew settings instead of throwing a 500.
 */
class PrinterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    public function index(): RedirectResponse
    {
        // Prefer existing named printer routes when the host application has one.
        foreach ([
            'printers.index',
            'printer.index',
            'business-printers.index',
            'restaurant.printers.index',
        ] as $routeName) {
            if (Route::has($routeName)) {
                return redirect()->route($routeName);
            }
        }

        // Fall back to a safe RestaurantNew page rather than breaking the sidebar.
        if (Route::has('restaurant-new.settings.index')) {
            return redirect()->route('restaurant-new.settings.index');
        }

        if (Route::has('restaurantnew.index')) {
            return redirect()->route('restaurantnew.index');
        }

        return redirect()->to(url('/restaurant-new'));
    }
}
