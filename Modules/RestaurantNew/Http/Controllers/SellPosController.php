<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Compatibility controller for host/core modules that still generate the
 * RestaurantNew POS URL with action(SellPosController@create).
 *
 * The active RestaurantNew POS remains RestaurantPosController@index.
 */
class SellPosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('restaurantnew.pos.index');
    }

    /**
     * Kept as an additional compatibility entry for older host menu code.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('restaurantnew.pos.index');
    }
}
