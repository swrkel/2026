<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\DashboardController;
use Modules\RestaurantNew\Http\Controllers\SettingsController;
use Modules\RestaurantNew\Http\Controllers\DiningAreaController;
use Modules\RestaurantNew\Http\Controllers\TableController;
use Modules\RestaurantNew\Http\Controllers\KitchenSectionController;
use Modules\RestaurantNew\Http\Controllers\OrderTypeController;
use Modules\RestaurantNew\Http\Controllers\NumberingController;
use Modules\RestaurantNew\Http\Controllers\MenuCategoryController;
use Modules\RestaurantNew\Http\Controllers\MenuItemController;
use Modules\RestaurantNew\Http\Controllers\OrderController;
use Modules\RestaurantNew\Http\Controllers\KotController;
use Modules\RestaurantNew\Http\Controllers\BillController;
use Modules\RestaurantNew\Http\Controllers\RestaurantPosController;
use Modules\RestaurantNew\Http\Controllers\RestaurantKitchenController;
use Modules\RestaurantNew\Http\Controllers\RestaurantBillingController;
use Modules\RestaurantNew\Http\Controllers\InventoryController;
use Modules\RestaurantNew\Http\Controllers\RecipeController;
use Modules\RestaurantNew\Http\Controllers\StaffController;
use Modules\RestaurantNew\Http\Controllers\ShiftController;
use Modules\RestaurantNew\Http\Controllers\StaffReportController;
use Modules\RestaurantNew\Http\Controllers\DeliveryController;
use Modules\RestaurantNew\Http\Controllers\DeliveryReportController;
use Modules\RestaurantNew\Http\Controllers\SaleController;
use Modules\RestaurantNew\Http\Controllers\KitchenScreenController;
use Modules\RestaurantNew\Http\Controllers\AdvancedPosController;
use Modules\RestaurantNew\Http\Controllers\TableOperationController;
use Modules\RestaurantNew\Http\Controllers\KitchenProductionController;
use Modules\RestaurantNew\Http\Controllers\KitchenRoutingController;

/*
 |--------------------------------------------------------------------------
 | RestaurantNew consolidated core routes
 |--------------------------------------------------------------------------
 | Preserves the working route groups from stages 001-013 that were lost
 | when later parcels overwrote Routes/web.php.
 */

Route::middleware(['web', 'auth', 'restaurantnew.business.scope'])
    ->prefix(config('restaurantnew.route_prefix', 'restaurant-new'))
    ->name('restaurant-new.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/index', [DashboardController::class, 'index'])->name('index');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::resource('/dining-areas', DiningAreaController::class)->except(['show']);
        Route::resource('/tables', TableController::class)->except(['show']);
        Route::resource('/kitchen-sections', KitchenSectionController::class)->except(['show']);
        Route::resource('/order-types', OrderTypeController::class)->except(['show']);

        Route::get('/numbering', [NumberingController::class, 'index'])->name('numbering.index');
        Route::get('/numbering/{id}/edit', [NumberingController::class, 'edit'])->name('numbering.edit');
        Route::put('/numbering/{id}', [NumberingController::class, 'update'])->name('numbering.update');

        Route::resource('/menu-categories', MenuCategoryController::class)->except(['show']);
        Route::post('/menu-categories/{menu_category}/toggle', [MenuCategoryController::class, 'toggle'])->name('menu-categories.toggle');
        Route::resource('/menu-items', MenuItemController::class)->except(['show']);
        Route::post('/menu-items/{menu_item}/toggle', [MenuItemController::class, 'toggle'])->name('menu-items.toggle');
        Route::get('/menu-items/{menu_item}/recipes', [MenuItemController::class, 'recipes'])->name('menu-items.recipes');
        Route::post('/menu-items/{menu_item}/recipes', [MenuItemController::class, 'saveRecipes'])->name('menu-items.recipes.save');

        Route::get('/orders/data', [OrderController::class, 'data'])->name('orders.data');
        Route::resource('/orders', OrderController::class);
        Route::resource('/kots', KotController::class);
        Route::post('/kots/{order}/print', [KotController::class, 'print'])->name('kots.print');
        Route::resource('/bills', BillController::class);
        Route::post('/bills/{order}/finalize', [BillController::class, 'finalize'])->name('bills.finalize');
    });

Route::middleware(['web', 'auth', 'restaurantnew.business.scope'])
    ->prefix(config('restaurantnew.route_prefix', 'restaurant-new'))
    ->name('restaurantnew.')
    ->group(function () {
        // Compatibility entry used by host module menus that resolve <module-alias>.index.
        Route::get('/home', [DashboardController::class, 'index'])->name('index');

        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/', [RestaurantPosController::class, 'index'])->name('index');
            Route::get('/running-orders', [RestaurantPosController::class, 'runningOrders'])->name('running-orders');
            Route::post('/orders', [RestaurantPosController::class, 'store'])->name('orders.store');
            Route::post('/orders/{order}/items', [RestaurantPosController::class, 'addItem'])->name('orders.items.store');
            Route::post('/orders/{order}/payments', [RestaurantPosController::class, 'addPayment'])->name('orders.payments.store');
            Route::post('/orders/{order}/close', [RestaurantPosController::class, 'close'])->name('orders.close');
        });

        Route::prefix('kitchen')->name('kitchen.')->group(function () {
            Route::get('/', [RestaurantKitchenController::class, 'index'])->name('index');
            Route::get('queue', [RestaurantKitchenController::class, 'queue'])->name('queue');
            Route::post('orders/{order}/tickets', [RestaurantKitchenController::class, 'createFromOrder'])->name('orders.tickets.store');
            Route::post('tickets/{ticket}/status', [RestaurantKitchenController::class, 'updateStatus'])->name('tickets.status');
            Route::get('tickets/{ticket}/print', [RestaurantKitchenController::class, 'print'])->name('print');
        });

        Route::get('billing', [RestaurantBillingController::class, 'index'])->name('billing.index');
        Route::get('billing/create', [RestaurantBillingController::class, 'create'])->name('billing.create');
        Route::post('billing', [RestaurantBillingController::class, 'store'])->name('billing.store');
        Route::get('billing/{bill}', [RestaurantBillingController::class, 'show'])->name('billing.show');
        Route::get('billing/{bill}/receipt', [RestaurantBillingController::class, 'receipt'])->name('billing.receipt');
        Route::post('billing/{bill}/payment', [RestaurantBillingController::class, 'addPayment'])->name('billing.payment');
        Route::post('billing/{bill}/void', [RestaurantBillingController::class, 'void'])->name('billing.void');
        Route::post('billing/{bill}/refund', [RestaurantBillingController::class, 'refund'])->name('billing.refund');

        Route::get('inventory/ingredients', [InventoryController::class, 'index'])->name('inventory.ingredients');
        Route::post('inventory/ingredients', [InventoryController::class, 'storeIngredient'])->name('inventory.ingredients.store');
        Route::post('inventory/categories', [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
        Route::post('inventory/receive', [InventoryController::class, 'receive'])->name('inventory.receive');
        Route::get('inventory/stocks', [InventoryController::class, 'stocks'])->name('inventory.stocks');
        Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('inventory/wastage', [InventoryController::class, 'wastage'])->name('inventory.wastage');
        Route::get('recipes', [RecipeController::class, 'index'])->name('recipes.index');
        Route::post('recipes', [RecipeController::class, 'store'])->name('recipes.store');
        Route::post('recipes/lines', [RecipeController::class, 'storeLine'])->name('recipes.lines.store');

        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
        Route::post('shifts/{shift}/cash-movement', [ShiftController::class, 'cashMovement'])->name('shifts.cash_movement');
        Route::post('shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
        Route::post('shifts/{shift}/service-charge', [ShiftController::class, 'distributeServiceCharge'])->name('shifts.service_charge');
        Route::get('reports/staff-performance', [StaffReportController::class, 'performance'])->name('reports.staff_performance');
        Route::get('reports/shift-summary', [StaffReportController::class, 'shifts'])->name('reports.shift_summary');

        Route::get('delivery', [DeliveryController::class, 'index'])->name('delivery.index');
        Route::post('delivery/zones', [DeliveryController::class, 'storeZone'])->name('delivery.zones.store');
        Route::post('delivery/riders', [DeliveryController::class, 'storeRider'])->name('delivery.riders.store');
        Route::post('delivery/addresses', [DeliveryController::class, 'storeAddress'])->name('delivery.addresses.store');
        Route::post('delivery/orders', [DeliveryController::class, 'store'])->name('delivery.orders.store');
        Route::get('delivery/{delivery}', [DeliveryController::class, 'show'])->name('delivery.show');
        Route::post('delivery/{delivery}/assign-rider', [DeliveryController::class, 'assignRider'])->name('delivery.assign_rider');
        Route::post('delivery/{delivery}/status', [DeliveryController::class, 'status'])->name('delivery.status');
        Route::get('reports/delivery-summary', [DeliveryReportController::class, 'summary'])->name('reports.delivery_summary');
        Route::get('reports/delivery-riders', [DeliveryReportController::class, 'riders'])->name('reports.delivery_riders');

        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('sales/{sale}/print-bill', [SaleController::class, 'printBill'])->name('sales.bill.print');
        Route::get('kitchen/screen', [KitchenScreenController::class, 'index'])->name('kitchen.screen');
        Route::post('kitchen/queue/{queue}/status', [KitchenScreenController::class, 'status'])->name('kitchen.status');
        Route::get('kitchen/queue/{queue}/print-kot', [KitchenScreenController::class, 'printKot'])->name('kitchen.kot.print');

        Route::get('pos/advanced', [AdvancedPosController::class, 'advanced'])->name('pos.advanced');
        Route::post('orders/{order}/hold', [AdvancedPosController::class, 'hold'])->name('orders.hold');
        Route::post('held-orders/{heldOrder}/resume', [AdvancedPosController::class, 'resume'])->name('orders.resume');
        Route::post('orders/{order}/split-bill', [AdvancedPosController::class, 'split'])->name('orders.split_bill');
        Route::post('orders/{order}/multi-payments', [AdvancedPosController::class, 'payments'])->name('orders.multi_payments');
        Route::post('orders/{order}/transfer-table', [AdvancedPosController::class, 'transferTable'])->name('orders.transfer_table');
        Route::post('orders/{order}/change-waiter', [AdvancedPosController::class, 'changeWaiter'])->name('orders.change_waiter');
        Route::post('orders/{order}/merge', [AdvancedPosController::class, 'merge'])->name('orders.merge');
        Route::get('tables/operations', [TableOperationController::class, 'index'])->name('tables.operations');

        Route::prefix('kitchen')->name('kitchen.')->group(function () {
            Route::get('production-board', [KitchenProductionController::class, 'board'])->name('production.board');
            Route::get('production-data', [KitchenProductionController::class, 'data'])->name('production.data');
            Route::post('production/{queue}/preparing', [KitchenProductionController::class, 'preparing'])->name('production.preparing');
            Route::post('production/{queue}/ready', [KitchenProductionController::class, 'ready'])->name('production.ready');
            Route::post('production/{queue}/served', [KitchenProductionController::class, 'served'])->name('production.served');
            Route::post('production/{queue}/priority', [KitchenProductionController::class, 'priority'])->name('production.priority');
            Route::get('routing', [KitchenRoutingController::class, 'index'])->name('routing.index');
            Route::post('routing', [KitchenRoutingController::class, 'store'])->name('routing.store');
        });
    });
