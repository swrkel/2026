<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Dashboard\PurchaseDashboardController;
use Modules\Purchase\Http\Controllers\Dashboard\PurchaseDashboardWidgetController;

Route::prefix('dashboard')->as('dashboard.')->group(function () {
    Route::get('/', [PurchaseDashboardController::class, 'index'])->name('index');
    Route::get('/widgets/summary', [PurchaseDashboardWidgetController::class, 'summary'])->name('widgets.summary');
    Route::get('/widgets/outstanding', [PurchaseDashboardWidgetController::class, 'outstanding'])->name('widgets.outstanding');
    Route::get('/widgets/recent-purchases', [PurchaseDashboardWidgetController::class, 'recentPurchases'])->name('widgets.recent-purchases');
});
