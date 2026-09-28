<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Compatibility controller for host/core sidebars that generate the
 * Invoice Schemes URL with action(RestaurantNew\\InvoiceSchemeController@index).
 *
 * RestaurantNew does not own invoice-scheme configuration; the compatibility
 * action forwards to the existing system Invoice Schemes page.
 */
class InvoiceSchemeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    public function index(): RedirectResponse
    {
        return redirect()->to(url('/invoice-schemes'));
    }
}
