<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Modules\Distribution\Http\Controllers\DistributionDailySummaryController;
use Modules\Distribution\Http\Controllers\DistributionInvoiceController;
use Modules\Distribution\Http\Controllers\DistributionRouteUserMapController;
use Modules\Distribution\Http\Controllers\DistributionSalesOrderController;
use Modules\Distribution\Http\Controllers\SalesOrders\DistributionSalesOrderPaymentController;
use Modules\Distribution\Http\Controllers\SettingController;
use Modules\Distribution\Http\Controllers\DistributionLoadingController;
use Modules\Distribution\Http\Controllers\VatDistributionInvoiceController;
use Modules\Distribution\Http\Controllers\Invoices\DistributionInvoicePaymentController;


Route::group(['middleware' => ['web', 'auth', 'language', 'tenant.context'], 'prefix' => 'distribution'], function () {
    Route::resource('/areas', 'DistributionAreasController');
    Route::resource('/provinces', 'DistributionProvincesController');
    Route::resource('/districts', 'DistributionDistrictsController');
    Route::resource('/routes', 'DistributionRoutesController')->names('distribution.routes');
    Route::resource('/meter', 'DistributionVehicleMetersController');
    //  Route::resource('/invoice', 'DistributionInvoiceController');
    Route::resource('/loading', 'DistributionLoadingController')->names('distribution.loading');
    // Route::resource('/free_issue', 'DistributionFreeIssueController');
    Route::get('/districtdropdown/{id}', 'DistributionRoutesController@getProvincesDistricts');
    Route::get('/areadropdown/{id}', 'DistributionRoutesController@getDistrictAreas');

    Route::resource('/settings', 'SettingController')->names('distribution.settings');
    Route::resource('/vehicles', 'DistributionVehiclesController');
    Route::post('/vehicles/check-number', 'DistributionVehiclesController@checkVehicleNumber')->name('distribution.vehicle_check');
    Route::resource('/prefix-numbering', 'DistributionNumberingPrefixController');
    Route::get('/route-user-maps', [DistributionRouteUserMapController::class, 'index'])->name('distribution.route_user_maps.index');
    Route::post('/route-user-maps', [DistributionRouteUserMapController::class, 'store'])->name('distribution.route_user_maps.store');
    Route::post('/route-user-maps/{id}/toggle-status', [DistributionRouteUserMapController::class, 'toggleStatus'])->name('distribution.route_user_maps.toggle_status');
    Route::post('/route-user-maps/sales-rep/{sales_rep_id}/toggle-status', [DistributionRouteUserMapController::class, 'toggleStatusBySalesRep'])->name('distribution.route_user_maps.toggle_status_by_sales_rep');
    Route::put('/route-user-maps/sales-rep/{sales_rep_id}', [DistributionRouteUserMapController::class, 'updateBySalesRep'])->name('distribution.route_user_maps.update_by_sales_rep');
    Route::delete('/route-user-maps/{id}', [DistributionRouteUserMapController::class, 'destroy'])->name('distribution.route_user_maps.destroy');
    Route::delete('/route-user-maps/sales-rep/{sales_rep_id}', [DistributionRouteUserMapController::class, 'destroyBySalesRep'])->name('distribution.route_user_maps.destroy_by_sales_rep');
    Route::get('/route-user-maps/routes-by-sales-rep', [DistributionRouteUserMapController::class, 'routesBySalesRep'])->name('distribution.route_user_maps.routes_by_sales_rep');

    Route::get('/vehicle-meters/{id}/view', 'DistributionVehicleMetersController@showDailySummary');

    Route::resource('/vehicle-meters', 'DistributionVehicleMetersController');

    Route::get('/discounts/subcategories', 'DiscountController@getSubCategories');
    Route::get('/discounts/products', 'DiscountController@getProducts')->name('distribution.discounts.products');
    Route::get('/discounts/product-units', 'DiscountController@getProductUnits')->name('distribution.discounts.product-units');
    Route::resource('/discounts', 'DiscountController');

    Route::get('/invoices', [DistributionInvoiceController::class, 'index'])
        ->name('distribution.invoices.index');

    Route::get('/invoices/create', [DistributionInvoiceController::class, 'create'])
        ->name('distribution.invoices.create');

    Route::post('/invoices/store', [DistributionInvoiceController::class, 'store'])
        ->name('distribution.invoices.store');
        
    Route::get('/invoices/{id}/edit', [DistributionInvoiceController::class, 'edit'])
        ->name('distribution.invoices.edit');
        
    Route::put('/invoices/{id}', [DistributionInvoiceController::class, 'update'])
        ->name('distribution.invoices.update');
    
    // These routes must come before /invoices/{id} to avoid route conflicts
    Route::get('/invoices/products', [DistributionInvoiceController::class, 'getProductsByCategory'])->name('distribution.invoices.products');
    Route::get('/invoices/product-info', [DistributionInvoiceController::class, 'productInfo']);
    Route::get('/invoices/free-issues-check', [DistributionInvoiceController::class, 'getFreeIssues'])->name('distribution.invoices.free_issues_check');
    Route::get('/invoices/customer-info/{id}', [DistributionInvoiceController::class, 'customerInfo'])
        ->name('distribution.invoices.customerInfo');
    Route::get('/get-price', [DistributionInvoiceController::class, 'getProductPrice']);
        
    Route::post('/invoices/{id}/shipping-status', [DistributionInvoiceController::class, 'updateShippingStatus'])
        ->name('distribution.invoices.shipping_status');
    Route::get('/invoices/{id}/duplicate', [DistributionInvoiceController::class, 'duplicate'])
        ->name('distribution.invoices.duplicate');
    Route::delete('/invoices/{id}', [DistributionInvoiceController::class, 'destroy'])
        ->name('distribution.invoices.destroy');
    Route::get('/invoices/{id}', [DistributionInvoiceController::class, 'show'])
        ->name('distribution.invoices.show');

    // New List Dis. Invoices Page
    Route::get('/list-invoices', [\Modules\Distribution\Http\Controllers\DistributionInvoiceListController::class, 'index'])->name('distribution.list_invoices.index');
    Route::get('/list-invoices/notes/{id}', [\Modules\Distribution\Http\Controllers\DistributionInvoiceListController::class, 'getNotes']);
    Route::get('/list-invoices/activities/{id}', [\Modules\Distribution\Http\Controllers\DistributionInvoiceListController::class, 'getActivityLog']);
    Route::get('/invoices/{id}/payments', [DistributionInvoicePaymentController::class, 'show'])->name('distribution.invoices.payments');

    Route::get('/sales-orders', [DistributionSalesOrderController::class, 'index'])->name('distribution.sales_orders.index');
    Route::get('/sales-orders/create', [DistributionSalesOrderController::class, 'create'])->name('distribution.sales_orders.create');
    Route::post('/sales-orders', [DistributionSalesOrderController::class, 'store'])->name('distribution.sales_orders.store');
    Route::get('/sales-orders/{id}/edit', [DistributionSalesOrderController::class, 'edit'])->name('distribution.sales_orders.edit');
    Route::put('/sales-orders/{id}', [DistributionSalesOrderController::class, 'update'])->name('distribution.sales_orders.update');
    Route::post('/sales-orders/{id}/shipping-status', [DistributionSalesOrderController::class, 'updateShippingStatus'])
        ->name('distribution.sales_orders.shipping_status');
    Route::post('/sales-orders/{id}/status', [DistributionSalesOrderController::class, 'updateStatus'])
        ->name('distribution.sales_orders.status');
    Route::delete('/sales-orders/{id}', [DistributionSalesOrderController::class, 'destroy'])->name('distribution.sales_orders.destroy');
    Route::get('/sales-orders/{id}/duplicate', [DistributionSalesOrderController::class, 'duplicate'])->name('distribution.sales_orders.duplicate');
    Route::get('/sales-orders/{id}/print', [DistributionSalesOrderController::class, 'print'])->name('distribution.sales_orders.print');
    Route::get('/sales-orders/routes-by-user', [DistributionSalesOrderController::class, 'getRoutesByUser'])->name('distribution.sales_orders.routes_by_user');
    Route::get('/sales-orders/{id}/show-url', [DistributionSalesOrderController::class, 'showSalesOrderUrl'])->name('distribution.sales_orders.show_url');
    Route::get('/sales-orders/{id}/activities', [DistributionSalesOrderController::class, 'getActivityLog'])->name('distribution.sales_orders.activities');
    Route::get('/sales-orders/{id}/payments', [DistributionSalesOrderPaymentController::class, 'show'])->name('distribution.sales_orders.payments');
    Route::get('/sales-orders/{id}', [DistributionSalesOrderController::class, 'show'])->name('distribution.sales_orders.show');


    Route::get('daily-summary-sheet', [DistributionDailySummaryController::class,'index'])->name('distribution.daily_summary.index');
    Route::get('daily-summary-sheet/create', [DistributionDailySummaryController::class, 'create'])->name('distribution.daily_summary.create');
    Route::post('daily-summary-sheet/store', [DistributionDailySummaryController::class, 'store'])->name('distribution.daily_summary.store');

    // AJAX routes
    Route::get('daily-summary-sheet/bills', [DistributionDailySummaryController::class, 'fetchBills'])->name('distribution.daily_summary.bills');
    Route::get('daily-summary-sheet/bill-details/{id}', [DistributionDailySummaryController::class, 'fetchBillDetails'])->name('distribution.daily_summary.bill_details');
    Route::get('daily-summary-sheet/free-issues', [DistributionDailySummaryController::class, 'getInvoiceFreeIssues'])->name('distribution.daily_summary.free_issues');
    Route::get('daily-summary-sheet/free-issue-columns', [DistributionDailySummaryController::class, 'getFreeIssueColumns'])->name('distribution.daily_summary.free_issue_columns');

    Route::get('daily-summary-sheet/vehicles', [DistributionDailySummaryController::class, 'fetchVehicles'])->name('distribution.daily_summary.vehicles');

    // autosave & restore
    Route::post('daily-summary-sheet/autosave', [DistributionDailySummaryController::class, 'autosave'])->name('distribution.daily_summary.autosave');
    Route::get('daily-summary-sheet/restore/{id}', [DistributionDailySummaryController::class, 'restoreDraft'])->name('distribution.daily_summary.restore');
    Route::post('daily-summary-sheet/stock-status', [DistributionDailySummaryController::class, 'stockStatus'])->name('distribution.daily_summary.stock_status');
    Route::get('daily-summary-sheet/loading-info', [DistributionDailySummaryController::class, 'loadingInfo'])->name('distribution.daily_summary.loading_info');

    // print
    Route::get('daily-summary-sheet/print/{id}', [DistributionDailySummaryController::class, 'print'])->name('distribution.daily_summary.print');

    Route::get('loadings', [DistributionLoadingController::class, 'index'])->name('distribution.loadings.index');
    Route::get('loadings/create', [DistributionLoadingController::class, 'create'])->name('distribution.loadings.create');
    Route::post('loadings/store', [DistributionLoadingController::class, 'store'])->name('distribution.loadings.store');
    Route::get('loadings/subcategories', [DistributionLoadingController::class, 'getSubcategories'])
        ->name('distribution.loadings.subcategories');

    Route::get('loadings/products', [DistributionLoadingController::class, 'getProducts'])
        ->name('distribution.loadings.products');

    Route::get('loadings/vehicle-stock', [DistributionLoadingController::class, 'getVehicleStock'])
        ->name('distribution.loadings.vehicle_stock');

// NOW THE DYNAMIC ROUTES
    Route::get('loadings/{id}', [DistributionLoadingController::class, 'show'])
        ->name('distribution.loadings.show');

    Route::get('loadings/{id}/edit', [DistributionLoadingController::class, 'edit'])
        ->name('distribution.loadings.edit');

    Route::post('loadings/{id}/update', [DistributionLoadingController::class, 'update'])
        ->name('distribution.loadings.update');

    Route::delete('loadings/{id}/delete', [DistributionLoadingController::class, 'destroy'])
        ->name('distribution.loadings.destroy');
    
    Route::get('loadings/print/{loading}', [DistributionLoadingController::class, 'print'])
    ->name('distribution.loadings.print');

    //free issue tab in settings page routes
    Route::get('/free-issues', 'DistributionFreeIssueController@index')->name('free-issues.index');
    Route::get('/free-issues', 'DistributionFreeIssueController@index')->name('distribution.free-issues.index');
    Route::get('/free-issues/create', 'DistributionFreeIssueController@create');
    Route::post('/free-issues/store', 'DistributionFreeIssueController@store')->name('free-issues.store');
    Route::post('/free-issues/store', 'DistributionFreeIssueController@store')->name('distribution.free-issues.store');
    
    Route::get('/free-issues/{id}/edit', 'DistributionFreeIssueController@edit')->name('free-issues.edit');
    Route::get('/free-issues/{id}/edit', 'DistributionFreeIssueController@edit')->name('distribution.free-issues.edit');
    Route::put('/free-issues/{id}', 'DistributionFreeIssueController@update')->name('free-issues.update');
    Route::put('/free-issues/{id}', 'DistributionFreeIssueController@update')->name('distribution.free-issues.update');

    Route::post('/free-issues/draft-add', 'DistributionFreeIssueController@addDraft')->name('draft-add');
    Route::post('/free-issues/toggle/{id}', 'DistributionFreeIssueController@toggleStatus');

    Route::get('/free-issues/logs/{id}', 'DistributionFreeIssueController@logs');

    // These getSubCategories and getProducts were loosely mapped in original code
    Route::get('/subcategories/{ids}', 'DistributionFreeIssueController@getSubCategories');
    Route::get('/products', 'DistributionFreeIssueController@getProducts');

    Route::get('get-product-unit/{id}', 'DistributionFreeIssueController@getProductUnit')->name('distribution.get-product-unit');


    Route::get('settings/tab/{tab}', 'SettingController@loadTab')->name('distribution.settings.tab');

    
    Route::get('/free-issues/user-details/{id}', 'DistributionFreeIssueController@getUserDetails');
    
    Route::get('invoices/{id}/print', [DistributionInvoiceController::class, 'printInvoice'])->name('distribution.invoices.print');

    // VAT Distribution Invoice Routes
    Route::get('/vat-invoices/get-prefix/{id}', [VatDistributionInvoiceController::class, 'getPrefixes'])->name('distribution.vat-invoices.get-prefix');
    Route::get('/vat-invoices', [VatDistributionInvoiceController::class, 'index'])->name('distribution.vat-invoices.index');
    Route::get('/vat-invoices/create', [VatDistributionInvoiceController::class, 'create'])->name('distribution.vat-invoices.create');
    Route::post('/vat-invoices/store', [VatDistributionInvoiceController::class, 'store'])->name('distribution.vat-invoices.store');
    Route::get('/vat-invoices/{id}/edit', [VatDistributionInvoiceController::class, 'edit'])->name('distribution.vat-invoices.edit');
    Route::put('/vat-invoices/{id}', [VatDistributionInvoiceController::class, 'update'])->name('distribution.vat-invoices.update');
    Route::post('/vat-invoices/{id}/shipping-status', [VatDistributionInvoiceController::class, 'updateShippingStatus'])->name('distribution.vat-invoices.shipping_status');
    Route::get('/vat-invoices/{id}/duplicate', [VatDistributionInvoiceController::class, 'duplicate'])->name('distribution.vat-invoices.duplicate');
    Route::delete('/vat-invoices/{id}', [VatDistributionInvoiceController::class, 'destroy'])->name('distribution.vat-invoices.destroy');
    Route::get('/vat-invoices/{id}', [VatDistributionInvoiceController::class, 'show'])->name('distribution.vat-invoices.show');
});
