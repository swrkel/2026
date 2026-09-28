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

use Illuminate\Support\Facades\Route;
use Modules\RealTimeEntries\Http\Controllers\RealTimeEntriesController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context', 'realtimeentries.access'])->prefix('real-time-entries')->name('realtime.')->group(function () {
    // Main index page
    Route::get('/', [RealTimeEntriesController::class, 'index'])->name('index');

    // Additional submenu pages
    Route::match(['get', 'post'], '/real-time-payments', [RealTimeEntriesController::class, 'index'])->name('real-time-payments');
    Route::get('/other-sales', [RealTimeEntriesController::class, 'otherSales'])->name('other-sales');
    Route::get('/list-other-sales', [RealTimeEntriesController::class, 'otherSalesList'])->name('list-other-sales');
    Route::get('/get-shifts-by-operator', [RealTimeEntriesController::class, 'getShiftsByOperator'])->name('get-shifts-by-operator');
    Route::get('/meters-with-payments', [RealTimeEntriesController::class, 'metersWithPayments'])->name('meters-with-payments');
    Route::get('/payment-summary', [RealTimeEntriesController::class, 'paymentSummary'])->name('payment-summary');
    Route::get('/real-time-settings', [RealTimeEntriesController::class, 'setting_dash'])->name('real-time-settings');

    // Saving endpoints
    Route::post('/save-cash', [RealTimeEntriesController::class, 'saveCash'])->name('save-cash');
    Route::post('/save-card', [RealTimeEntriesController::class, 'saveCard'])->name('save-card');
    Route::post('/save-credit-sale', [RealTimeEntriesController::class, 'saveCreditSale'])->name('save-credit-sale');
    Route::post('/save-sales-income', [RealTimeEntriesController::class, 'saveSalesIncome'])->name('save-sales-income');
    Route::post('/update-settlement-number', [RealTimeEntriesController::class, 'updateSettlementNumber'])->name('update-settlement-number');
    Route::post('/store-settings', action: [RealTimeEntriesController::class, 'store_settings'])->name('store-settings');
});
