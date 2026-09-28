<?php

namespace Modules\POS\Http\Controllers\RouteClosures;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\AdvancedSaleController;
use Modules\POS\Http\Controllers\AssetController;
use Modules\POS\Http\Controllers\CashDrawerController;
use Modules\POS\Http\Controllers\ConfigurationCenterController;
use Modules\POS\Http\Controllers\CustomerController;
use Modules\POS\Http\Controllers\DashboardController;
use Modules\POS\Http\Controllers\InventorySetupController;
use Modules\POS\Http\Controllers\KitchenDisplayController;
use Modules\POS\Http\Controllers\LiveSupportController;
use Modules\POS\Http\Controllers\OfflineSyncController;
use Modules\POS\Http\Controllers\PageFix\AdvancedSalesPageController;
use Modules\POS\Http\Controllers\PageFix\KitchenPageController;
use Modules\POS\Http\Controllers\PageFix\ReturnsPageController;
use Modules\POS\Http\Controllers\PageFix\ShiftsPageController;
use Modules\POS\Http\Controllers\PostLiveStabilizationController;
use Modules\POS\Http\Controllers\ProductController;
use Modules\POS\Http\Controllers\ProductionReadinessController;
use Modules\POS\Http\Controllers\ProductionStabilizationController;
use Modules\POS\Http\Controllers\PurchaseController;
use Modules\POS\Http\Controllers\RegisterController;
use Modules\POS\Http\Controllers\Reports\POSReportController;
use Modules\POS\Http\Controllers\ReturnController;
use Modules\POS\Http\Controllers\SaleController;
use Modules\POS\Http\Controllers\SalesCartController;
use Modules\POS\Http\Controllers\SalesWorkspaceController;
use Modules\POS\Http\Controllers\SettingsController;
use Modules\POS\Http\Controllers\ShiftController;
use Modules\POS\Http\Controllers\StandaloneAuditController;

/**
 * MA-002 - route closures moved out of Modules/POS/Routes/web.php.
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
class WebRouteController
{
    public function handle1(string $path)
    {
        return redirect('/pos-module/' . trim($path, '/'));
    }
}
