<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Compatibility controller for host/core sidebars that generate the
 * barcode page URL with action(RestaurantNew\\BarcodeController@index).
 *
 * RestaurantNew does not replace the system barcode/label feature; this
 * action forwards to the existing host barcode page.
 */
class BarcodeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    public function index(): RedirectResponse
    {
        return redirect()->to(url('/barcodes'));
    }
}
