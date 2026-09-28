<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\DashboardController;
use Modules\POS\Http\Controllers\ConfigurationCenterController;
use Modules\POS\Http\Controllers\RegisterController;
use Modules\POS\Http\Controllers\ShiftController;
use Modules\POS\Http\Controllers\CashDrawerController;
use Modules\POS\Http\Controllers\AssetController;
use Modules\POS\Http\Controllers\SaleController;
use Modules\POS\Http\Controllers\SalesWorkspaceController;
use Modules\POS\Http\Controllers\SalesCartController;
use Modules\POS\Http\Controllers\ProductController;
use Modules\POS\Http\Controllers\CustomerController;
use Modules\POS\Http\Controllers\ReturnController;
use Modules\POS\Http\Controllers\AdvancedSaleController;
use Modules\POS\Http\Controllers\KitchenDisplayController;
use Modules\POS\Http\Controllers\SettingsController;
use Modules\POS\Http\Controllers\StandaloneAuditController;
use Modules\POS\Http\Controllers\PurchaseController;
use Modules\POS\Http\Controllers\InventorySetupController;
use Modules\POS\Http\Controllers\LiveSupportController;
use Modules\POS\Http\Controllers\ProductionStabilizationController;
use Modules\POS\Http\Controllers\ProductionReadinessController;
use Modules\POS\Http\Controllers\PostLiveStabilizationController;
use Modules\POS\Http\Controllers\OfflineSyncController;
use Modules\POS\Http\Controllers\Reports\POSReportController;
use Modules\POS\Http\Controllers\PageFix\ReturnsPageController;
use Modules\POS\Http\Controllers\PageFix\AdvancedSalesPageController;
use Modules\POS\Http\Controllers\PageFix\KitchenPageController;
use Modules\POS\Http\Controllers\PageFix\ShiftsPageController;


// Legacy direct page aliases. These render the page directly instead of
// returning an empty redirect response to sidebar/AJAX navigation handlers.
Route::middleware(['web', 'auth'])->prefix('pos')->group(function () {
    Route::get('/returns', ReturnsPageController::class);
    Route::get('/advanced-sales', AdvancedSalesPageController::class);
    Route::get('/kitchen', KitchenPageController::class);
    Route::get('/shifts', ShiftsPageController::class);
});

// Backward compatibility aliases. Main visible POS URL is /pos-module.
Route::middleware('web')->group(function () {
    Route::redirect('/pos', '/pos-module');
    Route::get('/pos/{path}', [\Modules\POS\Http\Controllers\RouteClosures\WebRouteController::class, 'handle1'])->where('path', '.*');

    Route::redirect('/pos-dashboard', '/pos-module');
    Route::redirect('/point-of-sale', '/pos-module');
});

Route::group(['middleware' => ['web'], 'prefix' => 'pos-module', 'as' => 'pos.'], function () {
    Route::get('/assets/{type}/{file}', [AssetController::class, 'show'])->name('assets');
});

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'pos-module', 'as' => 'pos.'], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/status', [DashboardController::class, 'status'])->name('status');


    Route::get('/advanced-sales', AdvancedSalesPageController::class)->name('advanced_sales.index');
    Route::post('/advanced-sales/search-products', [AdvancedSaleController::class, 'searchProducts'])->name('advanced_sales.search_products');
    Route::post('/advanced-sales/hold', [AdvancedSaleController::class, 'hold'])->name('advanced_sales.hold');
    Route::post('/advanced-sales/merge', [AdvancedSaleController::class, 'merge'])->name('advanced_sales.merge');
    Route::get('/kitchen', KitchenPageController::class)->name('kitchen.index');

    Route::get('/configuration-center', [ConfigurationCenterController::class, 'index'])->name('configuration.index');
    Route::get('/configuration-center/{section}', [ConfigurationCenterController::class, 'section'])->name('configuration.section');

    Route::resource('registers', RegisterController::class)->except(['destroy']);
    Route::post('/registers/{register}/toggle-status', [RegisterController::class, 'toggleStatus'])->name('registers.toggle_status');

    Route::get('/shifts', ShiftsPageController::class)->name('shifts.index');
    Route::get('/shifts/current', [ShiftController::class, 'current'])->name('shifts.current');
    Route::get('/shifts/open', [ShiftController::class, 'openForm'])->name('shifts.open_form');
    Route::post('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::get('/shifts/{session}/close', [ShiftController::class, 'closeForm'])->name('shifts.close_form');
    Route::post('/shifts/{session}/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('/shifts/{session}/summary', [ShiftController::class, 'summary'])->name('shifts.summary');



    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/workspace', [SalesWorkspaceController::class, 'index'])->name('sales.workspace');
    Route::get('/sales/workspace/products', [SalesWorkspaceController::class, 'products'])->name('sales.workspace.products');
    Route::get('/sales/workspace/customers', [SalesWorkspaceController::class, 'customers'])->name('sales.workspace.customers');
    Route::get('/sales/workspace/price-check', [SalesWorkspaceController::class, 'priceCheck'])->name('sales.workspace.price_check');
    Route::get('/sales/list', [SaleController::class, 'list'])->name('sales.list');
    Route::get('/sales/search-products', [SaleController::class, 'searchProducts'])->name('sales.search_products');
    Route::post('/sales/barcode', [SaleController::class, 'barcode'])->name('sales.barcode');
    Route::post('/sales/checkout', [SaleController::class, 'checkout'])->name('sales.checkout');
    Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');


    Route::get('/returns', ReturnsPageController::class)->name('returns.index');
    Route::get('/returns/create', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');
    Route::get('/returns/{return}/receipt', [ReturnController::class, 'receipt'])->name('returns.receipt');
    Route::post('/returns/{return}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
    Route::get('/exchanges', [ReturnController::class, 'exchanges'])->name('exchanges.index');
    Route::get('/exchanges/create', [ReturnController::class, 'createExchange'])->name('exchanges.create');
    Route::post('/exchanges', [ReturnController::class, 'storeExchange'])->name('exchanges.store');
    Route::get('/returns/export/csv', [ReturnController::class, 'exportCsv'])->name('returns.export_csv');
    Route::post('/sales/{sale}/void', [ReturnController::class, 'voidSale'])->name('sales.void');

    Route::get('/sales-cart', [SalesCartController::class, 'show'])->name('sales.cart.show');
    Route::post('/sales-cart/line', [SalesCartController::class, 'addLine'])->name('sales.cart.add_line');
    Route::put('/sales-cart/line/{line}', [SalesCartController::class, 'updateLine'])->name('sales.cart.update_line');
    Route::delete('/sales-cart/line/{line}', [SalesCartController::class, 'removeLine'])->name('sales.cart.remove_line');
    Route::post('/sales-cart/customer', [SalesCartController::class, 'setCustomer'])->name('sales.cart.customer');
    Route::post('/sales-cart/hold', [SalesCartController::class, 'hold'])->name('sales.cart.hold');
    Route::post('/sales-cart/suspend', [SalesCartController::class, 'suspend'])->name('sales.cart.suspend');
    Route::post('/sales-cart/{cart}/resume', [SalesCartController::class, 'resume'])->name('sales.cart.resume');
    Route::delete('/sales-cart', [SalesCartController::class, 'clear'])->name('sales.cart.clear');



    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('purchases', PurchaseController::class)->only(['index','create','store','show','destroy']);
    Route::get('/inventory-setup', [InventorySetupController::class, 'index'])->name('inventory_setup.index');
    Route::post('/inventory-setup/{type}', [InventorySetupController::class, 'store'])->name('inventory_setup.store');
    Route::delete('/inventory-setup/{type}/{id}', [InventorySetupController::class, 'destroy'])->name('inventory_setup.destroy');
    Route::get('/barcodes', [InventorySetupController::class, 'barcodes'])->name('barcodes.index');

    Route::resource('customers', CustomerController::class)->except(['destroy']);
    Route::post('/customers/{customer}/payment', [CustomerController::class, 'payment'])->name('customers.payment');
    Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
    Route::get('/stock-adjustment', [ProductController::class, 'stockAdjustForm'])->name('products.stock_adjust_form');
    Route::post('/stock-adjustment', [ProductController::class, 'stockAdjust'])->name('products.stock_adjust');
    Route::get('/stock-movements', [ProductController::class, 'movements'])->name('products.movements');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
    Route::get('/settings/receipt-designer', [SettingsController::class, 'receiptDesigner'])->name('settings.receipt_designer');
    Route::get('/settings/barcode-designer', [SettingsController::class, 'barcodeDesigner'])->name('settings.barcode_designer');
    Route::get('/settings/terminal', [SettingsController::class, 'terminalSettings'])->name('settings.terminal');
    Route::get('/settings/hardware', [SettingsController::class, 'hardwareSettings'])->name('settings.hardware');
    Route::get('/settings/security', [SettingsController::class, 'securitySettings'])->name('settings.security');
    Route::get('/settings/number-series', [SettingsController::class, 'numberSeries'])->name('settings.number_series');
    Route::get('/standalone-audit', [StandaloneAuditController::class, 'index'])->name('standalone_audit');
    Route::get('/live-testing-support', [LiveSupportController::class, 'index'])->name('live_testing_support');
    Route::get('/production-stabilization', [ProductionStabilizationController::class, 'index'])->name('production_stabilization');
    Route::get('/production-readiness', [ProductionReadinessController::class, 'index'])->name('production_readiness');

    Route::get('/post-live-stabilization', [PostLiveStabilizationController::class, 'index'])->name('post_live_stabilization.index');

    Route::get('/offline-sync', [OfflineSyncController::class, 'index'])->name('offline_sync.index');
    Route::get('/offline-sync/manifest', [OfflineSyncController::class, 'manifest'])->name('offline_sync.manifest');
    Route::post('/offline-sync/queue', [OfflineSyncController::class, 'queue'])->name('offline_sync.queue');
    Route::post('/offline-sync/sync-now', [OfflineSyncController::class, 'syncNow'])->name('offline_sync.sync_now');
    Route::get('/offline-sync/pending', [OfflineSyncController::class, 'pending'])->name('offline_sync.pending');
    Route::get('/offline-sync/offline-sales', [OfflineSyncController::class, 'offlineSales'])->name('offline_sync.offline_sales');
    Route::get('/offline-sync/cache/products', [OfflineSyncController::class, 'cacheProducts'])->name('offline_sync.cache.products');
    Route::get('/offline-sync/cache/customers', [OfflineSyncController::class, 'cacheCustomers'])->name('offline_sync.cache.customers');
    Route::get('/offline-sync/cache/settings', [OfflineSyncController::class, 'cacheSettings'])->name('offline_sync.cache.settings');
    Route::get('/offline-sync/cache/stock', [OfflineSyncController::class, 'cacheStock'])->name('offline_sync.cache.stock');
    Route::get('/offline-sync/cache/all', [OfflineSyncController::class, 'cacheAll'])->name('offline_sync.cache.all');
    Route::post('/offline-sync/preflight', [OfflineSyncController::class, 'preflight'])->name('offline_sync.preflight');
    Route::get('/offline-sync/conflicts', [OfflineSyncController::class, 'conflicts'])->name('offline_sync.conflicts');
    Route::get('/offline-sync/conflict-manager', [OfflineSyncController::class, 'conflictManager'])->name('offline_sync.conflict_manager');
    Route::post('/offline-sync/conflicts/{id}/retry', [OfflineSyncController::class, 'retryConflict'])->name('offline_sync.conflicts.retry');
    Route::post('/offline-sync/conflicts/{id}/resolve', [OfflineSyncController::class, 'resolveConflict'])->name('offline_sync.conflicts.resolve');
    Route::post('/offline-sync/conflicts/retry-all', [OfflineSyncController::class, 'retryAllConflicts'])->name('offline_sync.conflicts.retry_all');

    // S383 - Enterprise Background Synchronization Engine
    Route::get('/offline-sync/background-engine', [OfflineSyncController::class, 'backgroundEngine'])->name('offline_sync.background_engine');
    Route::get('/offline-sync/heartbeat', [OfflineSyncController::class, 'heartbeat'])->name('offline_sync.heartbeat');
    Route::post('/offline-sync/batch-next', [OfflineSyncController::class, 'batchNext'])->name('offline_sync.batch_next');
    Route::post('/offline-sync/ack-batch', [OfflineSyncController::class, 'ackBatch'])->name('offline_sync.ack_batch');
    Route::get('/offline-sync/progress', [OfflineSyncController::class, 'progress'])->name('offline_sync.progress');
    Route::post('/offline-sync/network-sample', [OfflineSyncController::class, 'networkSample'])->name('offline_sync.network_sample');

    // S384 - Enterprise Monitoring & Multi-Terminal Synchronization
    Route::get('/offline-sync/monitoring-center', [OfflineSyncController::class, 'monitoringCenter'])->name('offline_sync.monitoring_center');
    Route::get('/offline-sync/monitoring/status', [OfflineSyncController::class, 'monitoringStatus'])->name('offline_sync.monitoring.status');
    Route::post('/offline-sync/device-register', [OfflineSyncController::class, 'deviceRegister'])->name('offline_sync.device_register');
    Route::get('/offline-sync/devices', [OfflineSyncController::class, 'devices'])->name('offline_sync.devices');
    Route::post('/offline-sync/devices/{deviceUuid}/trust', [OfflineSyncController::class, 'trustDevice'])->name('offline_sync.devices.trust');
    Route::post('/offline-sync/devices/{deviceUuid}/block', [OfflineSyncController::class, 'blockDevice'])->name('offline_sync.devices.block');


    Route::get('/cash-drawer', [CashDrawerController::class, 'index'])->name('cash_drawer.index');
    Route::get('/cash-drawer/cash-in', [CashDrawerController::class, 'cashInForm'])->name('cash_drawer.cash_in_form');
    Route::post('/cash-drawer/cash-in', [CashDrawerController::class, 'cashIn'])->name('cash_drawer.cash_in');
    Route::get('/cash-drawer/cash-out', [CashDrawerController::class, 'cashOutForm'])->name('cash_drawer.cash_out_form');
    Route::post('/cash-drawer/cash-out', [CashDrawerController::class, 'cashOut'])->name('cash_drawer.cash_out');
    Route::get('/cash-drawer/count', [CashDrawerController::class, 'countForm'])->name('cash_drawer.count_form');
    Route::post('/cash-drawer/count', [CashDrawerController::class, 'count'])->name('cash_drawer.count');


    Route::get('/reports', [POSReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{report}', [POSReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/{report}', [POSReportController::class, 'show'])->name('reports.show');
});
