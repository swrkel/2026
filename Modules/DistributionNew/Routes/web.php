<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\CollectionController;
use Modules\DistributionNew\Http\Controllers\CreditNoteController;
use Modules\DistributionNew\Http\Controllers\CustomerOrderController;
use Modules\DistributionNew\Http\Controllers\CustomerPortalController;
use Modules\DistributionNew\Http\Controllers\DashboardController;
use Modules\DistributionNew\Http\Controllers\DeliveryController;
use Modules\DistributionNew\Http\Controllers\DeploymentLogController;
use Modules\DistributionNew\Http\Controllers\DisnewApprovalController;
use Modules\DistributionNew\Http\Controllers\DisnewBusinessLimitController;
use Modules\DistributionNew\Http\Controllers\DisnewDashboardStage8Controller;
use Modules\DistributionNew\Http\Controllers\DisnewLifecycleController;
use Modules\DistributionNew\Http\Controllers\DisnewProofController;
use Modules\DistributionNew\Http\Controllers\DisnewTripController;
use Modules\DistributionNew\Http\Controllers\DisnewWarehouseController;
use Modules\DistributionNew\Http\Controllers\LoadingController;
use Modules\DistributionNew\Http\Controllers\LoadingPlanController;
use Modules\DistributionNew\Http\Controllers\NotificationPreferenceController;
use Modules\DistributionNew\Http\Controllers\ReportController;
use Modules\DistributionNew\Http\Controllers\ReturnController;
use Modules\DistributionNew\Http\Controllers\RoleDashboardController;
use Modules\DistributionNew\Http\Controllers\RouteController;
use Modules\DistributionNew\Http\Controllers\SalesInvoiceController;
use Modules\DistributionNew\Http\Controllers\SalesOrderController;
use Modules\DistributionNew\Http\Controllers\SalesRepController;
use Modules\DistributionNew\Http\Controllers\SalesRepPortalController;
use Modules\DistributionNew\Http\Controllers\SettingsController;
use Modules\DistributionNew\Http\Controllers\SettlementController;
use Modules\DistributionNew\Http\Controllers\SmsController;
use Modules\DistributionNew\Http\Controllers\StoreVehicleStockController;
use Modules\DistributionNew\Http\Controllers\SuperAdminVehicleLimitController;
use Modules\DistributionNew\Http\Controllers\TerritoryController;
use Modules\DistributionNew\Http\Controllers\UnloadingController;
use Modules\DistributionNew\Http\Controllers\VehicleController;
use Modules\DistributionNew\Http\Controllers\VehicleStockController;
use Modules\DistributionNew\Http\Controllers\Audit\DisnewAuditHealthController;
use Modules\DistributionNew\Http\Controllers\Install\DisnewInstallationController;
use Modules\DistributionNew\Http\Controllers\Reports\OperationalReportController;
use Modules\DistributionNew\Http\Controllers\Reports\ReturnsReportController;

/*
|--------------------------------------------------------------------------
| Distribution New UI Routes - DISNEW_012 stabilized
|--------------------------------------------------------------------------
| These routes are intentionally fully qualified to avoid 404s caused by
| module namespace differences in different Laravel/nwidart versions.
*/

Route::prefix('distribution-new')->name('distributionnew.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('dashboard/operations', [DisnewDashboardStage8Controller::class, 'index'])->name('dashboard.operations');
    Route::get('dashboard/role', [RoleDashboardController::class, 'index'])->name('dashboard.role');

    Route::resource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{salesOrder}/create-invoice', [SalesInvoiceController::class, 'createFromOrder'])->name('sales-orders.create-invoice');
    Route::post('sales-orders/{salesOrder}/status', [DisnewLifecycleController::class, 'update'])->name('sales-orders.status.update');
    Route::resource('sales-invoices', SalesInvoiceController::class)->only(['index', 'show']);

    Route::resource('vehicles', VehicleController::class)->except(['show', 'destroy']);
    Route::resource('territories', TerritoryController::class)->only(['index', 'store']);
    Route::resource('routes', RouteController::class)->only(['index', 'store']);
    Route::post('routes/{route}/customers', [RouteController::class, 'assignCustomers'])->name('routes.customers.assign');
    Route::resource('sales-reps', SalesRepController::class)->only(['index', 'store']);

    Route::resource('warehouses', DisnewWarehouseController::class)->only(['index', 'store']);
    Route::resource('trips', DisnewTripController::class)->only(['index', 'store']);
    Route::resource('loading-plans', LoadingPlanController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('loading-plans/{loadingPlan}/approve', [LoadingPlanController::class, 'approve'])->name('loading-plans.approve');
    Route::post('loading-plans/{loadingPlan}/convert', [LoadingPlanController::class, 'convert'])->name('loading-plans.convert');
    Route::resource('loading', LoadingController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('loading/{loading}/complete', [LoadingController::class, 'complete'])->name('loading.complete');
    Route::resource('unloading', UnloadingController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('unloading/{unloading}/complete', [UnloadingController::class, 'complete'])->name('unloading.complete');
    Route::post('delivery-proofs', [DisnewProofController::class, 'store'])->name('delivery-proofs.store');

    Route::get('vehicle-stock', [VehicleStockController::class, 'index'])->name('vehicle-stock.index');
    Route::get('vehicle-store-stock', [StoreVehicleStockController::class, 'index'])->name('vehicle-store-stock.index');
    Route::get('store-vehicle-stock', [StoreVehicleStockController::class, 'index'])->name('store-vehicle-stock.index');

    Route::resource('deliveries', DeliveryController::class)->only(['index', 'show']);
    Route::post('deliveries/{delivery}/mark-delivered', [DeliveryController::class, 'markDelivered'])->name('deliveries.mark-delivered');
    Route::resource('returns', ReturnController::class)->only(['index','create','store','show']);
    Route::post('returns/{return}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
    Route::post('returns/{return}/credit-note', [ReturnController::class, 'createCreditNote'])->name('returns.credit-note');
    Route::resource('credit-notes', CreditNoteController::class)->only(['index','show']);
    Route::post('credit-notes/{creditNote}/approve', [CreditNoteController::class, 'approve'])->name('credit-notes.approve');

    Route::resource('collections', CollectionController::class)->only(['index','create','store']);
    Route::post('collections/{collection}/confirm', [CollectionController::class, 'confirm'])->name('collections.confirm');
    Route::resource('settlements', SettlementController::class)->only(['index','create','store']);
    Route::post('settlements/{settlement}/finalize', [SettlementController::class, 'finalize'])->name('settlements.finalize');

    Route::get('sales-rep-portal', [SalesRepPortalController::class, 'dashboard'])->name('sales-rep-portal.dashboard');
    Route::get('sales-rep-portal/route-today', [SalesRepPortalController::class, 'routeToday'])->name('sales-rep-portal.route-today');
    Route::get('sales-rep-portal/orders', [SalesRepPortalController::class, 'orders'])->name('sales-rep-portal.orders');
    Route::get('sales-rep-portal/collections', [SalesRepPortalController::class, 'collections'])->name('sales-rep-portal.collections');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/sms-officers', [SettingsController::class, 'saveSmsOfficers'])->name('settings.sms-officers.save');
    Route::get('settings/sms-officer-groups', [SettingsController::class, 'smsOfficerGroups'])->name('settings.sms-officer-groups');
    Route::post('settings/sms-officer-groups', [SettingsController::class, 'saveSmsOfficerGroups'])->name('settings.sms-officer-groups.save');
    Route::get('settings/notification-preferences', [NotificationPreferenceController::class, 'index'])->name('settings.notification-preferences');
    Route::post('settings/notification-preferences', [NotificationPreferenceController::class, 'store'])->name('settings.notification-preferences.store');
    Route::get('sms/templates', [SmsController::class, 'templates'])->name('sms.templates');
    Route::post('sms/templates', [SmsController::class, 'saveTemplate'])->name('sms.templates.save');
    Route::get('sms/logs', [SmsController::class, 'logs'])->name('sms.logs');

    Route::get('approvals', [DisnewApprovalController::class, 'index'])->name('approvals.index');
    Route::post('approvals/{id}/approve', [DisnewApprovalController::class, 'approve'])->name('approvals.approve');
    Route::get('audit/health', [DisnewAuditHealthController::class, 'index'])->name('audit.health');
    Route::post('audit/health/run', [DisnewAuditHealthController::class, 'run'])->name('audit.health.run');
    Route::get('install/checklist', [DisnewInstallationController::class, 'index'])->name('install.checklist');
    Route::post('install/checklist/{id}/complete', [DisnewInstallationController::class, 'complete'])->name('install.checklist.complete');
    Route::get('deployment-logs', [DeploymentLogController::class, 'index'])->name('deployment-logs.index');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/operational/daily', [OperationalReportController::class, 'daily'])->name('reports.operational.daily');
    Route::get('reports/operational/vehicle-stock', [OperationalReportController::class, 'vehicleStock'])->name('reports.operational.vehicle-stock');
    Route::get('reports/returns-summary', [ReturnsReportController::class, 'index'])->name('reports.returns-summary');
});

Route::prefix('distribution-new/customer')->name('distributionnew.customer-portal.')->withoutMiddleware(['auth'])->group(function () {
    Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('orders', [CustomerPortalController::class, 'orders'])->name('orders');
    Route::post('orders', [CustomerPortalController::class, 'storeOrder'])->name('orders.store');
});

Route::prefix('distribution-new/customer-order')->name('distributionnew.customer-order.')->withoutMiddleware(['auth'])->group(function () {
    Route::get('{token}', [CustomerOrderController::class, 'create'])->name('create');
    Route::post('{token}', [CustomerOrderController::class, 'store'])->name('store');
});

Route::prefix('superadmin/distribution-new')->name('superadmin.distributionnew.')->group(function () {
    Route::get('business/{businessId}/vehicle-limit', [SuperAdminVehicleLimitController::class, 'edit'])->name('vehicle-limit.edit');
    Route::put('business/{businessId}/vehicle-limit', [SuperAdminVehicleLimitController::class, 'update'])->name('vehicle-limit.update');
    Route::get('business/{businessId}/limits', [DisnewBusinessLimitController::class, 'edit'])->name('limits.edit');
    Route::put('business/{businessId}/limits', [DisnewBusinessLimitController::class, 'update'])->name('limits.update');
});

// DISNEW 024 Production Stabilization routes
if (file_exists(__DIR__.'/stage24_production_stabilization.php')) { require __DIR__.'/stage24_production_stabilization.php'; }

// DISNEW 025 Server Testing routes
if (file_exists(__DIR__.'/stage25_server_testing.php')) { require __DIR__.'/stage25_server_testing.php'; }
