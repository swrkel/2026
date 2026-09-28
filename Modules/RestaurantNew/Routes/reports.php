<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\ReportController;

Route::prefix(config('restaurantnew.route_prefix', 'restaurant-new') . '/reports')
    ->name('restaurantnew.reports.')
    ->middleware(['web', 'auth', 'restaurantnew.business.scope'])
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/daily-summary', [ReportController::class, 'dailySummary'])->name('daily-summary');
        Route::get('/item-sales', [ReportController::class, 'itemSales'])->name('item-sales');
        Route::get('/category-sales', [ReportController::class, 'categorySales'])->name('category-sales');
        Route::get('/waiter-sales', [ReportController::class, 'waiterSales'])->name('waiter-sales');
        Route::get('/table-sales', [ReportController::class, 'tableSales'])->name('table-sales');
        Route::get('/cancelled-void', [ReportController::class, 'cancelledVoid'])->name('cancelled-void');
        Route::get('/payments', [ReportController::class, 'payments'])->name('payments');
        Route::get('/tax-service', [ReportController::class, 'taxService'])->name('tax-service');
    });
