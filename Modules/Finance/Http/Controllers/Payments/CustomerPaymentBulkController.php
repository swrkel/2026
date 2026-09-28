<?php

namespace Modules\Finance\Http\Controllers\Payments;

use Illuminate\Routing\Controller;

/**
 * Finance bulk customer payments controller.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE.
 *
 * This class previously declared
 *     extends App\Http\Controllers\CustomerPaymentBulkController
 * which pulled 377 lines of core controller code into the Finance module.
 *
 * Only ONE action is routed to it - index, via
 *     Modules/Finance/Routes/payments.php:7
 *         Route::any('payments/bulk', [...::class, 'index'])
 *             ->name('payments.bulk.index');
 *
 * And core's index() is EMPTY. Its entire body is a single `//` comment. So
 * the 377 inherited lines existed to serve a method that does nothing, and no
 * other method was ever reachable through a route.
 *
 * The empty index is reproduced here so the route still resolves exactly as
 * before - the page renders nothing now and rendered nothing before. Behaviour
 * is identical; the module simply no longer depends on that core class.
 *
 * If this screen is ever meant to DO something, that is a feature to build
 * here rather than inherit.
 *
 * Finance bridge controllers remaining: 9 -> 8.
 */
class CustomerPaymentBulkController extends Controller
{
    /**
     * Reproduces core's index() exactly: it has no body.
     */
    public function index()
    {
        //
    }
}
