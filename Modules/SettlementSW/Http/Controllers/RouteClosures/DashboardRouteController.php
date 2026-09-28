<?php

namespace Modules\SettlementSW\Http\Controllers\RouteClosures;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Modules\SettlementSW\Http\Controllers\SettlementSwCreditSaleController;
use Modules\SettlementSW\Http\Controllers\SettlementSwCustomerPaymentController;
use Modules\SettlementSW\Http\Controllers\SettlementSwDashboardController;
use Modules\SettlementSW\Http\Controllers\SettlementSwExpenseController;
use Modules\SettlementSW\Http\Controllers\SettlementSwMeterSalesController;
use Modules\SettlementSW\Http\Controllers\SettlementSwOtherIncomeController;
use Modules\SettlementSW\Http\Controllers\SettlementSwOtherSalesController;
use Modules\SettlementSW\Http\Controllers\SettlementSwTempController;

/**
 * MA-002 - route closures moved out of Modules/SettlementSW/Routes/dashboard.php.
 *
 * WHY: Laravel cannot run `php artisan route:cache` while ANY route is defined
 * with a closure. This installation has 8,613 routes across 349 files, and
 * without the cache every one is parsed and compiled on EVERY request,
 * including the login page. That is the multi-second delay.
 *
 * Only 35 closures across 14 files were blocking it.
 *
 * The method bodies are BYTE-IDENTICAL to the closures they replace. Nothing
 * was rewritten - the code simply lives in a class so the route can be cached.
 */
class DashboardRouteController
{
    public function handle1()
    { return response()->json(['success' => 1]); }
}
