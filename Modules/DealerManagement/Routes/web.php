<?php

use Illuminate\Support\Facades\Route;
use Modules\DealerManagement\Http\Middleware\EnsureDistributionEnabled;
use Modules\DealerManagement\Http\Middleware\EnsureDealerAuthenticated;
use Modules\DealerManagement\Http\Middleware\DealerPermission;
use Modules\DealerManagement\Http\Controllers\Portal\AuthController;
use Modules\DealerManagement\Http\Controllers\Portal\DashboardController;
use Modules\DealerManagement\Http\Controllers\Portal\UserController;
use Modules\DealerManagement\Http\Controllers\Portal\OutletController;
use Modules\DealerManagement\Http\Controllers\Portal\StockController;
use Modules\DealerManagement\Http\Controllers\Portal\OrderController;
use Modules\DealerManagement\Http\Controllers\Portal\DeliveryController;
use Modules\DealerManagement\Http\Controllers\Portal\ReturnController;
use Modules\DealerManagement\Http\Controllers\Portal\NotificationController;
use Modules\DealerManagement\Http\Controllers\Portal\ReportController;
use Modules\DealerManagement\Http\Controllers\Portal\RoleController;
use Modules\DealerManagement\Http\Controllers\Portal\ReorderController;
use Modules\DealerManagement\Http\Controllers\Admin\DealerController;
use Modules\DealerManagement\Http\Controllers\Admin\SyncController;
use Modules\DealerManagement\Http\Controllers\Admin\OperationsController;

Route::prefix('dealer')->name('dealermanagement.')->middleware([EnsureDistributionEnabled::class])->group(function(){
    Route::get('login',[AuthController::class,'showLogin'])->name('login');
    Route::post('login',[AuthController::class,'login'])->name('login.submit');
    Route::middleware([EnsureDealerAuthenticated::class])->group(function(){
        Route::post('logout',[AuthController::class,'logout'])->name('logout');
        Route::get('/',[DashboardController::class,'index'])->name('portal.dashboard');
        Route::get('dashboard',[DashboardController::class,'index'])->name('portal.dashboard.alias');
        Route::get('profile/password',[AuthController::class,'passwordForm'])->name('portal.profile.password');
        Route::post('profile/password',[AuthController::class,'passwordUpdate'])->name('portal.profile.password.update');
        Route::get('profile/login-code',[AuthController::class,'loginCodeForm'])->name('portal.profile.login-code');
        Route::post('profile/login-code',[AuthController::class,'loginCodeUpdate'])->name('portal.profile.login-code.update');

        Route::get('stock',[StockController::class,'index'])->middleware(DealerPermission::class.':stock.view')->name('portal.stock.index');
        Route::get('stock/update',[StockController::class,'updateForm'])->middleware(DealerPermission::class.':stock.update')->name('portal.stock.update.form');
        Route::post('stock/update',[StockController::class,'submitUpdate'])->middleware(DealerPermission::class.':stock.update')->name('portal.stock.update.submit');
        Route::get('stock/history',[StockController::class,'history'])->middleware(DealerPermission::class.':stock.history')->name('portal.stock.history');

        Route::get('orders',[OrderController::class,'index'])->middleware(DealerPermission::class.':orders.view')->name('portal.orders.index');
        Route::get('orders/create',[OrderController::class,'create'])->middleware(DealerPermission::class.':orders.create')->name('portal.orders.create');
        Route::post('orders',[OrderController::class,'store'])->middleware(DealerPermission::class.':orders.create')->name('portal.orders.store');
        Route::get('deliveries',[DeliveryController::class,'index'])->middleware(DealerPermission::class.':deliveries.view')->name('portal.deliveries.index');
        Route::get('returns',[ReturnController::class,'index'])->middleware(DealerPermission::class.':returns.view')->name('portal.returns.index');
        Route::get('notifications',[NotificationController::class,'index'])->middleware(DealerPermission::class.':notifications.view')->name('portal.notifications.index');
        Route::post('notifications/{id}/read',[NotificationController::class,'read'])->middleware(DealerPermission::class.':notifications.view')->name('portal.notifications.read');
        Route::get('reports',[ReportController::class,'index'])->middleware(DealerPermission::class.':reports.view')->name('portal.reports.index');

        Route::get('roles',[RoleController::class,'index'])->middleware(DealerPermission::class.':users.manage')->name('portal.roles.index');
        Route::post('roles',[RoleController::class,'store'])->middleware(DealerPermission::class.':users.manage')->name('portal.roles.store');
        Route::get('reorder-rules',[ReorderController::class,'index'])->middleware(DealerPermission::class.':settings.manage')->name('portal.reorder.index');
        Route::post('reorder-rules',[ReorderController::class,'save'])->middleware(DealerPermission::class.':settings.manage')->name('portal.reorder.save');

        Route::get('users',[UserController::class,'index'])->middleware(DealerPermission::class.':users.view')->name('portal.users.index');
        Route::post('users',[UserController::class,'store'])->middleware(DealerPermission::class.':users.manage')->name('portal.users.store');
        Route::post('users/{id}/reset-password',[UserController::class,'resetPassword'])->middleware(DealerPermission::class.':users.manage')->name('portal.users.reset-password');
        Route::post('users/{id}/toggle',[UserController::class,'toggle'])->middleware(DealerPermission::class.':users.manage')->name('portal.users.toggle');
        Route::get('outlets',[OutletController::class,'index'])->middleware(DealerPermission::class.':outlets.view')->name('portal.outlets.index');
        Route::post('outlets',[OutletController::class,'store'])->middleware(DealerPermission::class.':outlets.manage')->name('portal.outlets.store');
    });
});

Route::prefix('dealer-management')->name('dealermanagement.admin.')->middleware(['web','auth'])->group(function(){
    Route::get('/', [OperationsController::class,'dashboard'])->name('dashboard');
    Route::get('stock',[OperationsController::class,'stock'])->name('stock.index');
    Route::get('stock-history',[OperationsController::class,'history'])->name('stock.history');
    Route::get('hub-sales',[OperationsController::class,'hubSales'])->name('hub-sales.index');
    Route::get('stock-updates',[OperationsController::class,'stockUpdates'])->name('stock.updates');
    Route::get('reorder-planning',[OperationsController::class,'reorder'])->name('reorder.index');
    Route::get('orders',[OperationsController::class,'orders'])->name('orders.index');
    Route::get('deliveries',[OperationsController::class,'deliveries'])->name('deliveries.index');
    Route::get('returns',[OperationsController::class,'returns'])->name('returns.index');
    Route::get('notifications',[OperationsController::class,'notifications'])->name('notifications.index');
    Route::get('reports',[OperationsController::class,'reports'])->name('reports.index');
    Route::get('dealers',[DealerController::class,'index'])->name('dealers.index');
    Route::get('customer-search',[DealerController::class,'customerSearch'])->name('customers.search');
    Route::get('outlets',[OperationsController::class,'outlets'])->name('outlets.index');
    Route::get('users',[OperationsController::class,'users'])->name('users.index');
    Route::get('roles',[OperationsController::class,'roles'])->name('roles.index');
    Route::post('dealers',[DealerController::class,'store'])->name('dealers.store');
    Route::get('distribution-sync',[SyncController::class,'index'])->name('sync.index');
    Route::post('distribution-sync',[SyncController::class,'run'])->name('sync.run');
});

// -----------------------------------------------------------------------------
// Multi-Distributor Dealer Hub - one login for all linked distributors.
// -----------------------------------------------------------------------------
Route::prefix('dealer-hub')->name('dealermanagement.hub.')->group(function(){
    Route::get('login',[\Modules\DealerManagement\Http\Controllers\Portal\HubAuthController::class,'showLogin'])->name('login');
    Route::post('login',[\Modules\DealerManagement\Http\Controllers\Portal\HubAuthController::class,'login'])->name('login.submit');
    Route::middleware([\Modules\DealerManagement\Http\Middleware\EnsureHubAuthenticated::class])->group(function(){
        Route::post('logout',[\Modules\DealerManagement\Http\Controllers\Portal\HubAuthController::class,'logout'])->name('logout');
        Route::get('/',[\Modules\DealerManagement\Http\Controllers\Portal\HubDashboardController::class,'index'])->name('dashboard');
        Route::get('dashboard',[\Modules\DealerManagement\Http\Controllers\Portal\HubDashboardController::class,'index'])->name('dashboard.alias');
        Route::get('profile/password',[\Modules\DealerManagement\Http\Controllers\Portal\HubAuthController::class,'passwordForm'])->name('profile.password');
        Route::post('profile/password',[\Modules\DealerManagement\Http\Controllers\Portal\HubAuthController::class,'passwordUpdate'])->name('profile.password.update');
        Route::get('profile/login-code',[\Modules\DealerManagement\Http\Controllers\Portal\HubAuthController::class,'loginCodeForm'])->name('profile.login-code');
        Route::post('profile/login-code',[\Modules\DealerManagement\Http\Controllers\Portal\HubAuthController::class,'loginCodeUpdate'])->name('profile.login-code.update');
        Route::get('distributors',[\Modules\DealerManagement\Http\Controllers\Portal\HubConnectionController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.connections.manage')->name('distributors.index');
        Route::post('distributors/approve',[\Modules\DealerManagement\Http\Controllers\Portal\HubConnectionController::class,'approve'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.connections.manage')->name('distributors.approve');
        Route::post('distributors/request',[\Modules\DealerManagement\Http\Controllers\Portal\HubConnectionController::class,'request'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.connections.manage')->name('distributors.request');
        Route::post('refresh',[\Modules\DealerManagement\Http\Controllers\Portal\HubConnectionController::class,'refresh'])->name('refresh');
        Route::get('stock',[\Modules\DealerManagement\Http\Controllers\Portal\HubStockController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':stock.view')->name('stock.index');
        Route::get('product-sources',[\Modules\DealerManagement\Http\Controllers\Portal\HubProductController::class,'sources'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':settings.manage')->name('products.sources');
        Route::post('product-sources',[\Modules\DealerManagement\Http\Controllers\Portal\HubProductController::class,'save'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':settings.manage')->name('products.sources.save');
        Route::get('reorder',[\Modules\DealerManagement\Http\Controllers\Portal\HubReorderController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':stock.view')->name('reorder.index');
        Route::get('daily-sales',[\Modules\DealerManagement\Http\Controllers\Portal\HubSalesController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.sales.create')->name('sales.create');
        Route::post('daily-sales',[\Modules\DealerManagement\Http\Controllers\Portal\HubSalesController::class,'store'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.sales.create')->name('sales.store');
        Route::get('sales-history',[\Modules\DealerManagement\Http\Controllers\Portal\HubSalesController::class,'history'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.sales.create')->name('sales.history');
        Route::get('orders',[\Modules\DealerManagement\Http\Controllers\Portal\HubOrderController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':orders.view')->name('orders.index');
        Route::get('orders/create',[\Modules\DealerManagement\Http\Controllers\Portal\HubOrderController::class,'create'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.orders.create')->name('orders.create');
        Route::post('orders',[\Modules\DealerManagement\Http\Controllers\Portal\HubOrderController::class,'store'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.orders.create')->name('orders.store');
        Route::get('deliveries',[\Modules\DealerManagement\Http\Controllers\Portal\HubActivityController::class,'deliveries'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':deliveries.view')->name('deliveries.index');
        Route::get('returns',[\Modules\DealerManagement\Http\Controllers\Portal\HubActivityController::class,'returns'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':returns.view')->name('returns.index');
        Route::get('notifications',[\Modules\DealerManagement\Http\Controllers\Portal\HubActivityController::class,'notifications'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':notifications.view')->name('notifications.index');
        Route::get('reports',[\Modules\DealerManagement\Http\Controllers\Portal\HubReportController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':reports.view')->name('reports.index');
        Route::get('outlets',[\Modules\DealerManagement\Http\Controllers\Portal\HubOutletController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':outlets.manage')->name('outlets.index');
        Route::post('outlets',[\Modules\DealerManagement\Http\Controllers\Portal\HubOutletController::class,'store'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':outlets.manage')->name('outlets.store');
        Route::get('users',[\Modules\DealerManagement\Http\Controllers\Portal\HubUserController::class,'index'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.users.manage')->name('users.index');
        Route::post('users',[\Modules\DealerManagement\Http\Controllers\Portal\HubUserController::class,'store'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.users.manage')->name('users.store');
        Route::post('users/{id}/reset-password',[\Modules\DealerManagement\Http\Controllers\Portal\HubUserController::class,'reset'])->middleware(\Modules\DealerManagement\Http\Middleware\HubPermission::class.':hub.users.manage')->name('users.reset');
    });
});

Route::prefix('dealer-management')->name('dealermanagement.admin.')->middleware(['web','auth'])->group(function(){
    Route::get('dealer-hub',[\Modules\DealerManagement\Http\Controllers\Admin\HubAdminController::class,'index'])->name('hub.index');
    Route::post('dealer-hub/create',[\Modules\DealerManagement\Http\Controllers\Admin\HubAdminController::class,'createHubDealer'])->name('hub.create');
    Route::post('dealer-hub/invite',[\Modules\DealerManagement\Http\Controllers\Admin\HubAdminController::class,'invite'])->name('hub.invite');
    Route::post('dealer-hub/connections/{id}/approve',[\Modules\DealerManagement\Http\Controllers\Admin\HubAdminController::class,'approveRequest'])->name('hub.approve-request');
    Route::post('dealer-hub/map-outlet',[\Modules\DealerManagement\Http\Controllers\Admin\HubAdminController::class,'mapOutlet'])->name('hub.map-outlet');
});
