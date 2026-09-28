<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

/**
 * Compatibility controller for host/core sidebars that generate the
 * user-management URL with action(RestaurantNew\\ManageUserController@index).
 *
 * RestaurantNew does not replace the application's existing user-management
 * screen. Prefer an existing host route/action and fall back safely so a
 * missing compatibility target can never break the global sidebar.
 */
class ManageUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    public function index(): RedirectResponse
    {
        foreach ([
            'users.index',
            'user.index',
            'manage-user.index',
            'manage-users.index',
            'user-management.index',
            'user_management.index',
        ] as $routeName) {
            if (Route::has($routeName)) {
                return redirect()->route($routeName);
            }
        }

        foreach ([
            'App\\Http\\Controllers\\ManageUserController@index',
            'App\\Http\\Controllers\\UserController@index',
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

    /**
     * Compatibility alias for host/core sidebars that call
     * action(RestaurantNew\\ManageUserController@list).
     */
    public function list(): RedirectResponse
    {
        return $this->index();
    }
}
