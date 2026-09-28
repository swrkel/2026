<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\PageFix\AdvancedSalesPageController;
use Modules\POS\Http\Controllers\PageFix\KitchenPageController;
use Modules\POS\Http\Controllers\PageFix\ReturnsPageController;
use Modules\POS\Http\Controllers\PageFix\ShiftsPageController;

if (app()->bound('pos.pagefix_v5_routes_loaded')) {
    return;
}
app()->instance('pos.pagefix_v5_routes_loaded', true);

/*
| POS V5 page recovery routes
| Loaded last so the four affected GET pages always resolve to their
| independent, render-safe controllers. Existing POST and detail routes stay
| in Routes/web.php and remain unchanged.
*/
Route::middleware(['web', 'auth'])->prefix('pos-module')->name('pos.')->group(function () {
    Route::get('/returns', ReturnsPageController::class)->name('returns.index');
    Route::get('/advanced-sales', AdvancedSalesPageController::class)->name('advanced_sales.index');
    Route::get('/kitchen', KitchenPageController::class)->name('kitchen.index');
    Route::get('/shifts', ShiftsPageController::class)->name('shifts.index');
});

Route::middleware(['web', 'auth'])->prefix('pos')->group(function () {
    Route::get('/returns', ReturnsPageController::class);
    Route::get('/advanced-sales', AdvancedSalesPageController::class);
    Route::get('/kitchen', KitchenPageController::class);
    Route::get('/shifts', ShiftsPageController::class);
});
